<?php
/**
 * Webhook log viewer template.
 *
 * Rendered by Webhook_Log_Page via the Perique View engine. `$this` is the
 * View, not the page. All values are escaped on output.
 *
 * @var string                        $page_slug   Menu slug.
 * @var string                        $page_url    Base URL of this admin page.
 * @var list<string>                  $days        Available days (YYYY-MM-DD), newest first.
 * @var string                        $selected    Currently selected day.
 * @var list<array<string, mixed>>    $records     Records on the current page, newest first.
 * @var int                           $total       Total records for the selected day.
 * @var int                           $page        Current page (1-based).
 * @var int                           $per_page    Records per page.
 * @var int                           $total_pages Number of pages.
 *
 * @package Karkinos\Gateway
 */

declare(strict_types=1);

// Renders a tri-state boolean: ✓ true, ✗ false, — null/absent.
$kg_yn = static function ( $value ): string {
	if ( null === $value ) {
		return '—';
	}
	return $value ? '✓' : '✗';
};
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Karkinos Webhook Log', 'karkinos-gateway' ); ?></h1>

	<?php if ( empty( $days ) ) : ?>

		<p><?php esc_html_e( 'No webhook deliveries have been logged yet.', 'karkinos-gateway' ); ?></p>

	<?php else : ?>

		<p>
			<strong><?php esc_html_e( 'Day:', 'karkinos-gateway' ); ?></strong>
			<?php foreach ( $days as $day ) : ?>
				<?php $kg_url = add_query_arg( 'kg_date', $day, $page_url ); ?>
				<a
					href="<?php echo esc_url( $kg_url ); ?>"
					class="<?php echo $day === $selected ? 'current' : ''; ?>"
					style="margin-right:.75em;<?php echo $day === $selected ? 'font-weight:700;text-decoration:none;' : ''; ?>"
				><?php echo esc_html( $day ); ?></a>
			<?php endforeach; ?>
		</p>

		<?php
		$kg_from = $total > 0 ? ( ( $page - 1 ) * $per_page ) + 1 : 0;
		$kg_to   = min( $page * $per_page, $total );
		$kg_nav  = paginate_links(
			array(
				'base'      => add_query_arg( 'kg_date', $selected, $page_url ) . '&kg_page=%#%',
				'format'    => '',
				'current'   => $page,
				'total'     => $total_pages,
				'prev_text' => __( '‹ Newer', 'karkinos-gateway' ),
				'next_text' => __( 'Older ›', 'karkinos-gateway' ),
			)
		);
		?>
		<p class="description">
			<?php
			printf(
				/* translators: 1: first record number, 2: last record number, 3: total records, 4: selected date. */
				esc_html__( 'Showing %1$d–%2$d of %3$d deliveries for %4$s, newest first.', 'karkinos-gateway' ),
				(int) $kg_from,
				(int) $kg_to,
				(int) $total,
				esc_html( $selected )
			);
			?>
		</p>

		<?php if ( is_string( $kg_nav ) && '' !== $kg_nav ) : ?>
			<div class="tablenav top"><div class="tablenav-pages"><?php echo wp_kses_post( $kg_nav ); ?></div></div>
		<?php endif; ?>

		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Time (UTC)', 'karkinos-gateway' ); ?></th>
					<th><?php esc_html_e( 'Event', 'karkinos-gateway' ); ?></th>
					<th><?php esc_html_e( 'Action', 'karkinos-gateway' ); ?></th>
					<th><?php esc_html_e( 'Repo', 'karkinos-gateway' ); ?></th>
					<th><?php esc_html_e( 'Actor', 'karkinos-gateway' ); ?></th>
					<th title="<?php esc_attr_e( 'Signature valid', 'karkinos-gateway' ); ?>"><?php esc_html_e( 'Sig', 'karkinos-gateway' ); ?></th>
					<th title="<?php esc_attr_e( 'Sender authorised', 'karkinos-gateway' ); ?>"><?php esc_html_e( 'Auth', 'karkinos-gateway' ); ?></th>
					<th><?php esc_html_e( 'Sent', 'karkinos-gateway' ); ?></th>
					<th><?php esc_html_e( 'Reason', 'karkinos-gateway' ); ?></th>
					<th><?php esc_html_e( 'Payload', 'karkinos-gateway' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $records ) ) : ?>
					<tr><td colspan="10"><?php esc_html_e( 'No deliveries for this day.', 'karkinos-gateway' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $records as $kg_record ) : ?>
						<?php $kg_json = (string) wp_json_encode( $kg_record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?>
						<tr>
							<td><?php echo esc_html( (string) ( $kg_record['ts'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $kg_record['event'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $kg_record['action'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $kg_record['repo'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $kg_record['actor'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( $kg_yn( $kg_record['signature_valid'] ?? null ) ); ?></td>
							<td><?php echo esc_html( $kg_yn( $kg_record['authorised'] ?? null ) ); ?></td>
							<td><?php echo esc_html( $kg_yn( $kg_record['dispatched'] ?? null ) ); ?></td>
							<td><?php echo esc_html( (string) ( $kg_record['dispatch_reason'] ?? '' ) ); ?></td>
							<td>
								<details>
									<summary><?php esc_html_e( 'view', 'karkinos-gateway' ); ?></summary>
									<pre style="max-width:60ch;overflow:auto;white-space:pre-wrap;"><?php echo esc_html( $kg_json ); ?></pre>
								</details>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<?php if ( is_string( $kg_nav ) && '' !== $kg_nav ) : ?>
			<div class="tablenav bottom"><div class="tablenav-pages"><?php echo wp_kses_post( $kg_nav ); ?></div></div>
		<?php endif; ?>

	<?php endif; ?>
</div>
