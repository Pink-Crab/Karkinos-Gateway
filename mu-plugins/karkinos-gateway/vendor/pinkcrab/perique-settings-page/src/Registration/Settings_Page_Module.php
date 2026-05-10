<?php

declare( strict_types=1 );

/**
 * Main module for the Settings Page.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Registration;

use PinkCrab\Loader\Hook_Loader;
use PinkCrab\Perique\Interfaces\Module;
use PinkCrab\Perique\Application\App_Config;
use PinkCrab\Perique\Interfaces\DI_Container;
use PinkCrab\Perique_Admin_Menu\Hooks as Admin_Menu_Hooks;
use PinkCrab\Perique_Admin_Menu\Registry\Group_Page_Registry;
use PinkCrab\Perique_Settings_Page\Page\Settings_Page;
use PinkCrab\Perique_Settings_Page\Rest\Picker_Rest_Controller;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Repository;
use PinkCrab\Perique_Settings_Page\Setting\Repository\WP_Options_Settings_Repository;

class Settings_Page_Module implements Module {

	/** @inheritDoc */
	public function get_middleware(): ?string {
		return Settings_Page_Middleware::class;
	}

	/** @inheritDoc */
	public function pre_boot( App_Config $config, Hook_Loader $loader, DI_Container $di_container ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceBeforeLastUsed
		// Default binding for the Setting_Repository interface. Consumers
		// override per Settings class by adding a `substitutions` rule, e.g.:
		//   $di->addRule( My_Settings::class, [
		//       'substitutions' => [
		//           Setting_Repository::class => Other_Repo::class,
		//       ],
		//   ] );
		$di_container->addRule(
			Setting_Repository::class,
			array( 'instanceOf' => WP_Options_Settings_Repository::class )
		);
	}

	/** @inheritDoc */
	public function pre_register( App_Config $config, Hook_Loader $loader, DI_Container $di_container ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceBeforeLastUsed
		$loader->action( 'rest_api_init', array( new Picker_Rest_Controller(), 'register' ) );

		// Subscribe to admin-menu's GROUPS_PROCESSED hook so Settings_Page subclasses
		// declared only inside an Abstract_Group's $pages (never reaching our middleware
		// process()) still get DI rules wired before Page_Dispatcher materialises them.
		// add_action() rather than $loader->action() — the loader's queue isn't flushed
		// until after process_middleware() finishes, by which time GROUPS_PROCESSED has
		// already fired from Page_Middleware::tear_down() and a queued listener would
		// have missed it.
		add_action(
			Admin_Menu_Hooks::GROUPS_PROCESSED,
			function ( Group_Page_Registry $registry ) use ( $di_container ): void {
				foreach ( array_keys( $registry->all_for_subclass( Settings_Page::class ) ) as $page_class ) {
					/** @var class-string<Settings_Page> $page_class */
					$page_instance = $di_container->create( $page_class );
					if ( ! $page_instance instanceof Settings_Page ) {
						continue;
					}
					Settings_Page_Middleware::wire_settings_page_rules(
						$di_container,
						$page_class,
						$page_instance->settings_class_name()
					);
				}
			}
		);
	}

	/** @inheritDoc */
	public function post_register( App_Config $config, Hook_Loader $loader, DI_Container $di_container ): void {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceBeforeLastUsed
}
