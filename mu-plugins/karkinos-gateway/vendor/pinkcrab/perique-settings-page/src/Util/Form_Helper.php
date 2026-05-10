<?php

declare(strict_types=1);

/**
 * Series of helper functions regarding form and form handling
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

namespace PinkCrab\Perique_Settings_Page\Util;

class Form_Helper {

	/**
	 * Prints a success notice.
	 *
	 * @param string $contents
	 * @return void
	 */
	public static function success_notice( string $contents ): void {
		add_action(
			'admin_notices',
			function() use ( $contents ): void {
				printf(
					'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
					esc_html( $contents )
				);
			}
		);
	}

	/**
	 * Prints a error notice.
	 *
	 * @param string $contents
	 * @return void
	 */
	public static function error_notice( string $contents ): void {
		add_action(
			'admin_notices',
			function() use ( $contents ): void {
				printf(
					'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
					esc_html( $contents )
				);
			}
		);
	}

	/**
	 * Generates the nonce handle for a form, based on page slug.
	 *
	 * @param string $page_slug
	 * @return string
	 */
	public static function nonce_handle( string $page_slug ): string {
		return "perique_settings_page_{$page_slug}";
	}
}
