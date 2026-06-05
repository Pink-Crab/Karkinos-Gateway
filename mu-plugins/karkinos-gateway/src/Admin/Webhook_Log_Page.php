<?php
/**
 * Admin viewer for the daily webhook log files.
 *
 * A read-only Menu_Page (under Settings) that lists the days with a log file
 * and renders the parsed JSONL deliveries for the selected day, newest first.
 * Data comes from Webhook_Log_Reader; the day is chosen via the `kg_date`
 * query arg, validated against the set of days that actually have a file.
 *
 * @package Karkinos\Gateway\Admin
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Admin;

use Karkinos\Gateway\Logging\Webhook_Log_Reader;
use PinkCrab\Perique_Admin_Menu\Page\Menu_Page;
use PinkCrab\Perique_Admin_Menu\Page\Page;

class Webhook_Log_Page extends Menu_Page {

	/** Max records rendered for one day. */
	private const RECORD_LIMIT = 500;

	/** @var ?string Sits under Settings → (this page). */
	protected ?string $parent_slug = 'options-general.php';

	/** @var string Menu slug / ?page= value. */
	protected string $page_slug = 'karkinos-webhook-log';

	/** @var string Sidebar label. */
	protected string $menu_title = 'Karkinos Webhook Log';

	/** @var string Browser title and page heading. */
	protected string $page_title = 'Karkinos Webhook Log';

	/** @var string Admin-only. */
	protected string $capability = 'manage_options';

	/** @var string Resolved to views/admin/webhook-log.php. */
	protected string $view_template = 'admin/webhook-log';

	/**
	 * Constructor.
	 *
	 * @param Webhook_Log_Reader $reader Supplies the day list + parsed records.
	 */
	public function __construct( private Webhook_Log_Reader $reader ) {}

	/**
	 * Fires on this page's load (before render). Resolves the selected day and
	 * loads its records into the view data.
	 *
	 * @param Page $page The page being loaded (unused).
	 *
	 * @return void
	 */
	public function load( Page $page ): void {
		unset( $page );

		$days = $this->reader->days();

		// Read-only display filter, capability-gated — no state change, no nonce.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$requested = isset( $_GET['kg_date'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['kg_date'] ) ) : '';
		$selected  = in_array( $requested, $days, true ) ? $requested : ( $days[0] ?? '' );

		$this->view_data = array(
			'page_slug' => $this->page_slug,
			'days'      => $days,
			'selected'  => $selected,
			'records'   => '' !== $selected ? $this->reader->read( $selected, self::RECORD_LIMIT ) : array(),
		);
	}
}
