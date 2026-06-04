<?php
/**
 * DTO for a row in the dispatch queue.
 *
 * Pure data — no persistence, no behaviour. Built by Dispatch_Queue::find() /
 * next() from a kg_dispatch_jobs row.
 *
 * The queue has exactly two states, derived from a single column: a job has
 * either been dispatched (`dispatched_at` set) or not (`dispatched_at` NULL).
 * There is no in_flight/claim concept — one-at-a-time is enforced remotely by
 * Karkinos answering "busy" while it holds its own lock.
 *
 * @package Karkinos\Gateway\Dispatch
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Dispatch;

final class Dispatch_Job {

	/**
	 * Construct directly only in tests / factories. Production code should
	 * use Dispatch_Job::from_row() so DB casts stay in one place.
	 */
	public function __construct(
		public readonly int $id,
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
	 * Has this job been dispatched (successfully or terminally rejected)?
	 *
	 * @return bool True once dispatched_at is stamped.
	 */
	public function is_dispatched(): bool {
		return null !== $this->dispatched_at;
	}

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
