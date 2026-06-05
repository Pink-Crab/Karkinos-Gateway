<?php
/**
 * Admin viewer for the dispatch queue, with a per-row remove action.
 *
 * A Menu_Page (under Settings) listing kg_dispatch_jobs rows newest first,
 * paginated. Each row can be removed via a nonce-protected POST handled in
 * load() (before any output), which deletes the job and redirects back.
 *
 * @package Karkinos\Gateway\Admin
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Admin;

use Karkinos\Gateway\Dispatch\Dispatch_Queue;
use PinkCrab\Perique_Admin_Menu\Page\Menu_Page;
use PinkCrab\Perique_Admin_Menu\Page\Page;

class Dispatch_Queue_Page extends Menu_Page {

	/** Rows shown per page. */
	private const PER_PAGE = 50;

	/** Nonce action for the remove form. */
	private const REMOVE_NONCE = 'kg_dispatch_queue_remove';

	/** @var ?string Sits under Settings. */
	protected ?string $parent_slug = 'options-general.php';

	/** @var string Menu slug / ?page= value. */
	protected string $page_slug = 'karkinos-dispatch-queue';

	/** @var string Sidebar label. */
	protected string $menu_title = 'Karkinos Dispatch Queue';

	/** @var string Browser title and page heading. */
	protected string $page_title = 'Karkinos Dispatch Queue';

	/** @var string Admin-only. */
	protected string $capability = 'manage_options';

	/** @var string Resolved to views/admin/dispatch-queue.php. */
	protected string $view_template = 'admin/dispatch-queue';

	/**
	 * Constructor.
	 *
	 * @param Dispatch_Queue $queue The queue being viewed.
	 */
	public function __construct( private Dispatch_Queue $queue ) {}

	/**
	 * Page load: handle a remove submission (then redirect), else build the
	 * paginated view data.
	 *
	 * @param Page $page The page being loaded (unused).
	 *
	 * @return void
	 */
	public function load( Page $page ): void {
		unset( $page );

		$this->maybe_handle_remove();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only paging filter.
		$page_num = isset( $_GET['kg_page'] ) ? max( 1, (int) $_GET['kg_page'] ) : 1;

		$total       = $this->queue->count_all();
		$total_pages = $total > 0 ? (int) ceil( $total / self::PER_PAGE ) : 1;
		$page_num    = min( $page_num, $total_pages );

		$this->view_data = array(
			'page_slug'    => $this->page_slug,
			'remove_nonce' => self::REMOVE_NONCE,
			'jobs'         => $this->queue->recent( self::PER_PAGE, ( $page_num - 1 ) * self::PER_PAGE ),
			'total'        => $total,
			'page'         => $page_num,
			'per_page'     => self::PER_PAGE,
			'total_pages'  => $total_pages,
		);
	}

	/**
	 * Process a remove POST: verify nonce + capability, delete the job, then
	 * redirect to a GET URL so a refresh can't resubmit. No-op for other
	 * requests. Runs in load(), before any output is sent.
	 *
	 * @return void
	 */
	private function maybe_handle_remove(): void {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by check_admin_referer below.
		if ( 'remove' !== ( $_POST['kg_action'] ?? '' ) ) {
			return;
		}

		check_admin_referer( self::REMOVE_NONCE );

		if ( current_user_can( $this->capability ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
			$job_id = isset( $_POST['kg_job_id'] ) ? (int) $_POST['kg_job_id'] : 0;
			if ( $job_id > 0 ) {
				$this->queue->delete( $job_id );
			}
		}

		$redirect = wp_get_referer();
		if ( ! is_string( $redirect ) || '' === $redirect ) {
			$redirect = admin_url( 'options-general.php?page=' . $this->page_slug );
		}

		wp_safe_redirect( $redirect );
		exit;
	}
}
