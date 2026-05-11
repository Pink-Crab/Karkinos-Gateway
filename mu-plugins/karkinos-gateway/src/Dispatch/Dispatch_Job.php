<?php
/**
 * DTO for a row in the dispatch queue.
 *
 * Pure data — no persistence, no behaviour. Built by Dispatch_Queue::find() /
 * claim_next() from a kg_dispatch_jobs row. Holds the status / priority
 * constants that the rest of the queue stack references.
 *
 * @package Karkinos\Gateway\Dispatch
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Dispatch;

final class Dispatch_Job {

	public const STATUS_PENDING   = 'pending';
	public const STATUS_IN_FLIGHT = 'in_flight';
	public const STATUS_DONE      = 'done';
	public const STATUS_FAILED    = 'failed';

	/** @var list<string> Allow-list of statuses for callers that want to validate input. */
	public const STATUSES = array(
		self::STATUS_PENDING,
		self::STATUS_IN_FLIGHT,
		self::STATUS_DONE,
		self::STATUS_FAILED,
	);

	/** Default priority assigned to freshly-enqueued jobs (FIFO). */
	public const PRIORITY_DEFAULT = 0;

	/** Priority set by bump() — overtakes everything pending at default. */
	public const PRIORITY_BUMPED = 1000;

	/**
	 * Construct directly only in tests / factories. Production code should
	 * use Dispatch_Job::from_row() so DB casts stay in one place.
	 */
	public function __construct(
		public readonly int $id,
		public readonly int $priority,
		public readonly string $status,
		public readonly string $source,
		public readonly string $event,
		public readonly string $delivery_id,
		public readonly string $target_url,
		public readonly string $payload,
		public readonly string $created_at,
		public readonly ?string $dispatched_at,
		public readonly int $response_status,
		public readonly string $response_body,
		public readonly string $error
	) {}

	/**
	 * Hydrate from a raw $wpdb->get_row(..., ARRAY_A) result. Missing keys
	 * default to safe zero-values so partial rows don't blow up.
	 *
	 * @param array<string, mixed> $row
	 *
	 * @return self
	 */
	public static function from_row( array $row ): self {
		return new self(
			id:              (int) ( $row['id'] ?? 0 ),
			priority:        (int) ( $row['priority'] ?? 0 ),
			status:          (string) ( $row['status'] ?? self::STATUS_PENDING ),
			source:          (string) ( $row['source'] ?? '' ),
			event:           (string) ( $row['event'] ?? '' ),
			delivery_id:     (string) ( $row['delivery_id'] ?? '' ),
			target_url:      (string) ( $row['target_url'] ?? '' ),
			payload:         (string) ( $row['payload'] ?? '' ),
			created_at:      (string) ( $row['created_at'] ?? '' ),
			dispatched_at:   isset( $row['dispatched_at'] ) ? (string) $row['dispatched_at'] : null,
			response_status: (int) ( $row['response_status'] ?? 0 ),
			response_body:   (string) ( $row['response_body'] ?? '' ),
			error:           (string) ( $row['error'] ?? '' ),
		);
	}
}
