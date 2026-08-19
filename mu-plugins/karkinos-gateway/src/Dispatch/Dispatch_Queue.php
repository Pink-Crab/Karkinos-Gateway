<?php
/**
 * FIFO queue of webhook deliveries waiting to be forwarded to Karkinos.
 *
 * Backed by the kg_dispatch_jobs custom table. Two states only, both derived
 * from one column:
 *
 *   - dispatched_at IS NULL  -> not yet sent (next() will return it)
 *   - dispatched_at set      -> handled (forwarded OK, or terminally rejected)
 *
 * There is no claim / in_flight / single-flight lock here. "One at a time" is
 * enforced by the worker asking Karkinos "are you busy?" before each send and
 * by Karkinos holding its own host-wide lock; the gateway just tracks whether
 * a job has been dispatched. A process that dies mid-send simply leaves
 * dispatched_at NULL, so the next tick re-sends it (Karkinos dedupes on the
 * delivery id).
 *
 * The table still carries `status`/`priority` columns from the original
 * schema; they are unused under this model and left in place (dropping them
 * would mean altering an already-applied migration).
 *
 * @package Karkinos\Gateway\Dispatch
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Dispatch;

use Karkinos\Gateway\Migration\Create_Dispatch_Jobs_Table;
use PinkCrab\Perique\Application\App_Config;

class Dispatch_Queue {

	/**
	 * Constructor.
	 *
	 * @param App_Config $app_config Source of truth for the queue's table name
	 *                               (resolved via db_tables('dispatch_jobs')).
	 */
	public function __construct( private App_Config $app_config ) {}

	/**
	 * Resolve the queue's table name from configuration.
	 *
	 * @return string Fully prefixed table name.
	 */
	private function table(): string {
		return $this->app_config->db_tables( Create_Dispatch_Jobs_Table::TABLE_ALIAS );
	}

	/**
	 * Insert a new, undispatched job.
	 *
	 * Caller-supplied scalars are sanitised at this boundary: source/event are
	 * slug-shaped, delivery_id is plain text, target_url is URL-cleaned and
	 * SSRF-checked. `payload` is the exact bytes to forward and is stored
	 * verbatim. dispatched_at is left NULL.
	 *
	 * `kind` selects the downstream protocol the worker uses; an unrecognised
	 * value is rejected outright rather than defaulted, so a typo can't send a
	 * job down the wrong wire.
	 *
	 * @param array{
	 *     payload:string,
	 *     kind?:string,
	 *     source?:string,
	 *     event?:string,
	 *     delivery_id?:string,
	 *     target_url?:string
	 * } $data Caller-supplied job fields.
	 *
	 * @return int Inserted job ID, or 0 if the insert failed.
	 */
	public function enqueue( array $data ): int {
		global $wpdb;

		$kind = (string) ( $data['kind'] ?? Dispatch_Job::KIND_KARKINOS );
		if ( ! in_array( $kind, Dispatch_Job::KINDS, true ) ) {
			return 0;
		}

		$raw_target_url = (string) ( $data['target_url'] ?? '' );
		$target_url     = esc_url_raw( $raw_target_url );

		// esc_url_raw silently strips any protocol outside WP's allow-list
		// (file://, javascript:, data:, …) to ''. Without this guard those
		// would slip through is_safe_target_url's empty-input fast path.
		if ( '' !== $raw_target_url && '' === $target_url ) {
			return 0;
		}

		if ( ! $this->is_safe_target_url( $target_url ) ) {
			return 0;
		}

		$row = array(
			'kind'        => $kind,
			'source'      => sanitize_key( $data['source'] ?? '' ),
			'event'       => sanitize_key( $data['event'] ?? '' ),
			'delivery_id' => sanitize_text_field( $data['delivery_id'] ?? '' ),
			'target_url'  => $target_url,
			'payload'     => (string) ( $data['payload'] ?? '' ),
			'created_at'  => current_time( 'mysql', true ),
		);

		$inserted = $wpdb->insert(
			$this->table(),
			$row,
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return false === $inserted ? 0 : (int) $wpdb->insert_id;
	}

	/**
	 * The oldest job not yet dispatched, or null if the queue is empty.
	 *
	 * Passing $kinds restricts the search to those kinds, so a target that is
	 * unconfigured or busy can be excluded without its jobs blocking the head
	 * of the queue for every other target.
	 *
	 * @param list<string> $kinds Kinds to consider; empty means any kind.
	 *
	 * @return Dispatch_Job|null
	 */
	public function next( array $kinds = array() ): ?Dispatch_Job {
		global $wpdb;

		$kinds = array_values( array_unique( array_intersect( $kinds, Dispatch_Job::KINDS ) ) );

		// Asking for every kind is the same as not filtering at all. The
		// queries stay literals (rather than composing an IN list) so each one
		// goes through $wpdb->prepare() intact — one query per wanted kind,
		// then the oldest candidate overall wins.
		if ( array() === $kinds || count( $kinds ) === count( Dispatch_Job::KINDS ) ) {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE dispatched_at IS NULL ORDER BY created_at ASC, id ASC LIMIT 1',
					$this->table()
				),
				ARRAY_A
			);
			return is_array( $row ) ? Dispatch_Job::from_row( $row ) : null;
		}

		$candidates = array();
		foreach ( $kinds as $kind ) {
			$job = $this->next_of_kind( $kind );
			if ( null !== $job ) {
				$candidates[] = $job;
			}
		}

		if ( array() === $candidates ) {
			return null;
		}

		usort(
			$candidates,
			static fn( Dispatch_Job $a, Dispatch_Job $b ): int =>
				array( $a->created_at, $a->id ) <=> array( $b->created_at, $b->id )
		);

		return $candidates[0];
	}

	/**
	 * The oldest undispatched job of one kind, or null.
	 *
	 * @param string $kind One of Dispatch_Job::KINDS.
	 *
	 * @return Dispatch_Job|null
	 */
	private function next_of_kind( string $kind ): ?Dispatch_Job {
		global $wpdb;

		if ( Dispatch_Job::KIND_KARKINOS === $kind ) {
			// Rows written before the kind column are Karkinos envelopes, so
			// NULL/'' has to match here too.
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM %i WHERE dispatched_at IS NULL AND ( kind = %s OR kind IS NULL OR kind = '' ) ORDER BY created_at ASC, id ASC LIMIT 1",
					$this->table(),
					Dispatch_Job::KIND_KARKINOS
				),
				ARRAY_A
			);
			return is_array( $row ) ? Dispatch_Job::from_row( $row ) : null;
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE dispatched_at IS NULL AND kind = %s ORDER BY created_at ASC, id ASC LIMIT 1',
				$this->table(),
				$kind
			),
			ARRAY_A
		);
		return is_array( $row ) ? Dispatch_Job::from_row( $row ) : null;
	}

	/**
	 * Load a job by primary key.
	 *
	 * @param int $id Job ID.
	 *
	 * @return Dispatch_Job|null The hydrated job, or null if not found.
	 */
	public function find( int $id ): ?Dispatch_Job {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $this->table(), $id ),
			ARRAY_A
		);
		return is_array( $row ) ? Dispatch_Job::from_row( $row ) : null;
	}

	/**
	 * Mark a job dispatched.
	 *
	 * Called for terminal outcomes only — a successful forward (2xx) or a
	 * permanent rejection (4xx). Transient outcomes (busy/5xx/transport error)
	 * are NOT marked: the job keeps its NULL dispatched_at and is retried on a
	 * later tick. Stamps dispatched_at and records the upstream response so the
	 * outcome is auditable.
	 *
	 * @param int    $id              Job ID.
	 * @param int    $response_status HTTP status returned by Karkinos (0 if none).
	 * @param string $response_body   Response bytes (truncated for storage).
	 * @param string $error           Optional short error note (e.g. for a 4xx reject).
	 *
	 * @return void
	 */
	public function mark_dispatched( int $id, int $response_status, string $response_body, string $error = '' ): void {
		global $wpdb;
		$wpdb->update(
			$this->table(),
			array(
				'dispatched_at'   => current_time( 'mysql', true ),
				'response_status' => $response_status,
				'response_body'   => $this->truncate( $response_body ),
				'error'           => $this->truncate( sanitize_text_field( $error ), 1000 ),
			),
			array( 'id' => $id ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Count of jobs still waiting to be dispatched.
	 *
	 * @return int
	 */
	public function pending_count(): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE dispatched_at IS NULL',
				$this->table()
			)
		);
	}

	/**
	 * Total number of jobs in the table, any state.
	 *
	 * @return int
	 */
	public function count_all(): int {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $this->table() ) );
	}

	/**
	 * A page of jobs, most recent first (by id). For the admin queue viewer.
	 *
	 * @param int $per_page Rows per page (min 1).
	 * @param int $offset   Zero-based row offset.
	 *
	 * @return list<Dispatch_Job>
	 */
	public function recent( int $per_page, int $offset ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i ORDER BY id DESC LIMIT %d OFFSET %d',
				$this->table(),
				max( 1, $per_page ),
				max( 0, $offset )
			),
			ARRAY_A
		);

		$jobs = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				if ( is_array( $row ) ) {
					$jobs[] = Dispatch_Job::from_row( $row );
				}
			}
		}
		return $jobs;
	}

	/**
	 * Delete a job by id (admin queue viewer "remove" action).
	 *
	 * @param int $id Job ID.
	 *
	 * @return bool True if a row was removed.
	 */
	public function delete( int $id ): bool {
		global $wpdb;
		$deleted = $wpdb->delete( $this->table(), array( 'id' => $id ), array( '%d' ) );
		return false !== $deleted && $deleted > 0;
	}

	/**
	 * Hard cap on stored text so a runaway upstream response can't bloat the
	 * row. UTF-8 character-boundary not guaranteed (substr is byte-based) —
	 * acceptable for log/audit text.
	 *
	 * @param string $text Input.
	 * @param int    $max  Maximum byte length before truncation.
	 *
	 * @return string Original if short enough, otherwise truncated with an ellipsis.
	 */
	private function truncate( string $text, int $max = 10000 ): string {
		return strlen( $text ) <= $max ? $text : substr( $text, 0, $max ) . '…';
	}

	/**
	 * SSRF guard for the URL the dispatch worker will POST to.
	 *
	 * Empty values pass (caller didn't specify a target). Non-empty values
	 * must be http/https and resolve, syntactically, to something other than
	 * localhost / private / reserved IP space. The home server's public IP
	 * passes; private/reserved ranges and metadata endpoints are rejected.
	 *
	 * @param string $url URL to evaluate. Already passed through esc_url_raw.
	 *
	 * @return bool True if the URL is empty or safe to keep; false to reject.
	 */
	private function is_safe_target_url( string $url ): bool {
		if ( '' === $url ) {
			return true;
		}

		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) ) {
			return false;
		}

		$scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return false;
		}

		$host = strtolower( (string) ( $parts['host'] ?? '' ) );
		if ( '' === $host ) {
			return false;
		}

		if ( in_array( $host, array( 'localhost', 'localhost.localdomain' ), true ) ) {
			return false;
		}

		if ( str_ends_with( $host, '.local' ) || str_ends_with( $host, '.localhost' ) ) {
			return false;
		}

		// IP literal? Strip IPv6 brackets first, then reject anything in
		// private (RFC 1918 / ULA) or reserved (loopback, link-local,
		// multicast, AWS metadata at 169.254.169.254) ranges.
		$ip_candidate = $host;
		if ( str_starts_with( $ip_candidate, '[' ) && str_ends_with( $ip_candidate, ']' ) ) {
			$ip_candidate = substr( $ip_candidate, 1, -1 );
		}
		if ( false !== filter_var( $ip_candidate, FILTER_VALIDATE_IP ) ) {
			return false !== filter_var(
				$ip_candidate,
				FILTER_VALIDATE_IP,
				FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
			);
		}

		return true;
	}
}
