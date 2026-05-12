<?php
/**
 * Single-flight FIFO queue with priority bump.
 *
 * Backed by the kg_dispatch_jobs custom table. One job is in_flight at any
 * time; claim_next() returns null while busy. Atomic claim via
 * UPDATE … WHERE id = ? AND status = 'pending' — only the query whose
 * affected-rows = 1 wins, so two concurrent workers can't double-claim.
 *
 * Priority is an integer column; higher = sooner. Default 0, bumped 1000.
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
	 * Reads the prefixed name from config/settings.php under
	 * `db_tables[dispatch_jobs]` rather than hardcoding the prefix.
	 *
	 * @return string Fully prefixed wp_options-style table name.
	 */
	private function table(): string {
		return $this->app_config->db_tables( Create_Dispatch_Jobs_Table::TABLE_ALIAS );
	}

	/**
	 * Insert a new job in PENDING state.
	 *
	 * Caller-supplied scalars are sanitised at this boundary: source/event
	 * are slug-shaped, delivery_id is plain text, target_url is URL-cleaned,
	 * priority is clamped to >= 0. `payload` is the raw bytes to forward and
	 * is stored verbatim; $wpdb placeholders handle SQL escaping.
	 *
	 * @param array{
	 *     payload:string,
	 *     source?:string,
	 *     event?:string,
	 *     delivery_id?:string,
	 *     target_url?:string,
	 *     priority?:int
	 * } $data Caller-supplied job fields.
	 *
	 * @return int Inserted job ID, or 0 if the insert failed.
	 */
	public function enqueue( array $data ): int {
		global $wpdb;

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
			'priority'        => max( 0, (int) ( $data['priority'] ?? Dispatch_Job::PRIORITY_DEFAULT ) ),
			'status'          => Dispatch_Job::STATUS_PENDING,
			'source'          => sanitize_key( $data['source'] ?? '' ),
			'event'           => sanitize_key( $data['event'] ?? '' ),
			'delivery_id'     => sanitize_text_field( $data['delivery_id'] ?? '' ),
			'target_url'      => $target_url,
			'payload'         => (string) ( $data['payload'] ?? '' ),
			'created_at'      => current_time( 'mysql', true ),
			'response_status' => 0,
			'response_body'   => '',
			'error'           => '',
		);

		$inserted = $wpdb->insert(
			$this->table(),
			$row,
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		return false === $inserted ? 0 : (int) $wpdb->insert_id;
	}

	/**
	 * Atomically pick the highest-priority pending job and flip it to in_flight.
	 *
	 * Returns null if a job is already in_flight (single-flight lock) or if
	 * the pending set is empty. The UPDATE is gated by `AND status = pending`
	 * so two concurrent callers can't both win the same row.
	 *
	 * @return Dispatch_Job|null The claimed job, or null if nothing to do.
	 */
	public function claim_next(): ?Dispatch_Job {
		if ( $this->in_flight_count() > 0 ) {
			return null;
		}

		global $wpdb;

		// Candidate: highest priority, oldest first within same priority.
		$candidate_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM %i WHERE status = %s ORDER BY priority DESC, created_at ASC LIMIT 1',
				$this->table(),
				Dispatch_Job::STATUS_PENDING
			)
		);

		if ( 0 === $candidate_id ) {
			return null;
		}

		// Atomic claim: only the worker whose UPDATE actually flipped status wins.
		$claimed = (int) $wpdb->query(
			$wpdb->prepare(
				'UPDATE %i SET status = %s WHERE id = %d AND status = %s',
				$this->table(),
				Dispatch_Job::STATUS_IN_FLIGHT,
				$candidate_id,
				Dispatch_Job::STATUS_PENDING
			)
		);

		if ( 1 !== $claimed ) {
			return null;
		}

		return $this->find( $candidate_id );
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
	 * Mark a claimed job complete.
	 *
	 * Stores upstream's HTTP status code + the (truncated) response body and
	 * clears any prior error field. Releases the single-flight lock by
	 * transitioning out of in_flight.
	 *
	 * @param int    $id              Job ID — must be the one currently in_flight.
	 * @param int    $response_status HTTP status returned by the home server.
	 * @param string $response_body   Raw response bytes (truncated for storage).
	 *
	 * @return void
	 */
	public function mark_done( int $id, int $response_status, string $response_body ): void {
		global $wpdb;
		$wpdb->update(
			$this->table(),
			array(
				'status'          => Dispatch_Job::STATUS_DONE,
				'dispatched_at'   => current_time( 'mysql', true ),
				'response_status' => $response_status,
				'response_body'   => $this->truncate( $response_body ),
				'error'           => '',
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Mark a claimed job failed.
	 *
	 * Stores a short error message plus the upstream HTTP status/body if any
	 * response was received. Releases the single-flight lock.
	 *
	 * @param int    $id              Job ID — must be the one currently in_flight.
	 * @param string $error           Failure reason (sanitised, truncated to 1000 chars).
	 * @param int    $response_status HTTP status if any response arrived; 0 otherwise.
	 * @param string $response_body   Raw response bytes if any (truncated for storage).
	 *
	 * @return void
	 */
	public function mark_failed( int $id, string $error, int $response_status = 0, string $response_body = '' ): void {
		global $wpdb;
		$wpdb->update(
			$this->table(),
			array(
				'status'          => Dispatch_Job::STATUS_FAILED,
				'dispatched_at'   => current_time( 'mysql', true ),
				'response_status' => $response_status,
				'response_body'   => $this->truncate( $response_body ),
				'error'           => $this->truncate( sanitize_text_field( $error ), 1000 ),
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Raise a pending job's priority so it jumps the queue next.
	 *
	 * Only affects PENDING jobs — in_flight / done / failed jobs are not
	 * updated. Sets priority to Dispatch_Job::PRIORITY_BUMPED (1000), well
	 * above the default 0 so it overtakes everything else queued.
	 *
	 * @param int $id Job ID.
	 *
	 * @return bool True if exactly one pending row was updated; false otherwise
	 *              (job missing, not pending, or update failed).
	 */
	public function bump( int $id ): bool {
		global $wpdb;
		$affected = $wpdb->update(
			$this->table(),
			array( 'priority' => Dispatch_Job::PRIORITY_BUMPED ),
			array(
				'id'     => $id,
				'status' => Dispatch_Job::STATUS_PENDING,
			),
			array( '%d' ),
			array( '%d', '%s' )
		);
		return false !== $affected && $affected > 0;
	}

	/**
	 * Count rows currently in the in_flight state.
	 *
	 * Used by claim_next() as the single-flight lock check — > 0 means a
	 * worker is busy, skip dispatching another.
	 *
	 * @return int Row count.
	 */
	private function in_flight_count(): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE status = %s',
				$this->table(),
				Dispatch_Job::STATUS_IN_FLIGHT
			)
		);
	}

	/**
	 * Hard cap on stored text so a runaway upstream response can't bloat the
	 * row beyond MEDIUMTEXT-sane size. UTF-8 character-boundary not guaranteed
	 * (substr is byte-based) — acceptable for log/audit text.
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
	 * SSRF guard for the URL the dispatch worker will eventually POST to.
	 *
	 * Empty values pass (caller didn't specify a target). Non-empty values
	 * must be http/https and resolve, syntactically, to something other
	 * than localhost / private / reserved IP space — at enqueue time the
	 * worker hasn't run yet, so this is a coarse pre-flight check. The
	 * worker should re-check at request time (post-DNS resolution) since
	 * an attacker controlling DNS can flip a public hostname to a private
	 * IP between enqueue and dispatch.
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
