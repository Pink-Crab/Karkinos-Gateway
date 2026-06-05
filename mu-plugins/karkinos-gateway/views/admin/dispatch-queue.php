<?php
/**
 * Dispatch queue viewer template.
 *
 * Rendered by Dispatch_Queue_Page. `$this` is the View, not the page. All
 * values are escaped on output.
 *
 * @var string                                          $page_slug    Menu slug.
 * @var string                                          $remove_nonce Nonce action for the remove form.
 * @var list<\Karkinos\Gateway\Dispatch\Dispatch_Job>   $jobs         Jobs on the current page, newest first.
 * @var int                                             $total        Total jobs.
 * @var int                                             $page         Current page (1-based).
 * @var int                                             $per_page     Rows per page.
 * @var int                                             $total_pages  Number of pages.
 *
 * @package Karkinos\Gateway
 */

declare(strict_types=1);
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Karkinos Dispatch Queue', 'karkinos-gateway' ); ?></h1>

	<?php if ( 0 === $total ) : ?>

		<p><?php esc_html_e( 'The dispatch queue is empty.', 'karkinos-gateway' ); ?></p>

	<?php else : ?>

		<?php
		$kg_from = ( ( $page - 1 ) * $per_page ) + 1;
		$kg_to   = min( $page * $per_page, $total );
		$kg_nav  = paginate_links(
			array(
				'base'      => admin_url( 'options-general.php' ) . '?page=' . rawurlencode( $page_slug ) . '&kg_page=%#%',
				'format'    => '',
				'current'   => $page,
				'total'     => $total_pages,
				'prev_text' => __( '‹', 'karkinos-gateway' ),
				'next_text' => __( '›', 'karkinos-gateway' ),
			)
		);
		?>

		<p class="description">
			<?php
			printf(
				/* translators: 1: first row number, 2: last row number, 3: total jobs. */
				esc_html__( 'Showing %1$d–%2$d of %3$d jobs, newest first. "Pending" = not yet sent.', 'karkinos-gateway' ),
				(int) $kg_from,
				(int) $kg_to,
				(int) $total
			);
			?>
		</p>

		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'karkinos-gateway' ); ?></th>
					<th><?php esc_html_e( 'Created (UTC)', 'karkinos-gateway' ); ?></th>
					<th><?php esc_html_e( 'Event', 'karkinos-gateway' ); ?></th>
					<th><?php esc_html_e( 'Delivery', 'karkinos-gateway' ); ?></th>
					<th><?php esc_html_e( 'Status', 'karkinos-gateway' ); ?></th>
					<th><?php esc_html_e( 'HTTP', 'karkinos-gateway' ); ?></th>
					<th><?php esc_html_e( 'Error', 'karkinos-gateway' ); ?></th>
					<th><?php esc_html_e( 'Remove', 'karkinos-gateway' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $jobs as $kg_job ) : ?>
					<tr>
						<td><?php echo (int) $kg_job->id; ?></td>
						<td><?php echo esc_html( $kg_job->created_at ); ?></td>
						<td><?php echo esc_html( $kg_job->event ); ?></td>
						<td><?php echo esc_html( $kg_job->delivery_id ); ?></td>
						<td><?php echo $kg_job->is_dispatched() ? esc_html__( 'dispatched', 'karkinos-gateway' ) : esc_html__( 'pending', 'karkinos-gateway' ); ?></td>
						<td><?php echo 0 !== $kg_job->response_status ? (int) $kg_job->response_status : '—'; ?></td>
						<td><?php echo esc_html( $kg_job->error ); ?></td>
						<td>
							<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Remove this job from the queue?', 'karkinos-gateway' ) ); ?>');">
								<?php wp_nonce_field( $remove_nonce ); ?>
								<input type="hidden" name="kg_action" value="remove" />
								<input type="hidden" name="kg_job_id" value="<?php echo (int) $kg_job->id; ?>" />
								<button type="submit" class="button-link button-link-delete"><?php esc_html_e( 'Remove', 'karkinos-gateway' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( is_string( $kg_nav ) && '' !== $kg_nav ) : ?>
			<div class="tablenav bottom"><div class="tablenav-pages"><?php echo wp_kses_post( $kg_nav ); ?></div></div>
		<?php endif; ?>

	<?php endif; ?>
</div>
