<?php

declare(strict_types=1);

/**
 * A valid settings class
 * Isn't grouped
 * Covers setting properties relating settings repository and form handling (callbacks etc)
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

namespace PinkCrab\Perique_Settings_Page\Tests\Fixtures\Valid_Settings;

use PinkCrab\FunctionConstructors\Numbers as Num;
use PinkCrab\FunctionConstructors\Comparisons as Comp;
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;
use PinkCrab\Perique_Settings_Page\Setting\Field\Radio;
use PinkCrab\Perique_Settings_Page\Setting\Field\Colour;
use PinkCrab\Perique_Settings_Page\Setting\Field\Number;
use PinkCrab\Perique_Settings_Page\Setting\Field\Select;
use PinkCrab\Perique_Settings_Page\Setting\Field\Checkbox;
use PinkCrab\FunctionConstructors\GeneralFunctions as Func;
use PinkCrab\Perique_Settings_Page\Setting\Field\WP_Editor;
use PinkCrab\Perique_Settings_Page\Setting\Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Collection;
use PinkCrab\Perique_Settings_Page\Setting\Field\Media_Library;
use PinkCrab\Perique_Settings_Page\Setting\Field\Post_Picker;
use PinkCrab\Perique_Settings_Page\Setting\Field\Checkbox_Group;

class Valid_Settings_Not_Grouped extends Abstract_Settings {

	public const FIELD_KEYS = array(
		'Number'         => 'number',
		'Text'           => 'text',
		'Select'         => 'select',
		'Media_Library'  => 'media_upload',
		'Checkbox'       => 'checkbox',
		'WP_Editor'      => 'wp_editor',
		'Post_Picker'  => 'posts',
		'Checkbox_Group' => 'checkbox_group',
		'Radio'          => 'radio',
		'Colour'         => 'colour_picker'
	);

	/**
	 * Sets all the fields.
	 *
	 * @param Setting_Collection $settings
	 * @return Setting_Collection
	 */
	protected function fields( Setting_Collection $settings ): Setting_Collection {

		return $settings->push(
			//
			Number::new( self::FIELD_KEYS['Number'] )
				->set_sanitize( Func\always( 15 ) )
				->set_validate( // Must be numeric and even.
					function( $value ): bool {
						return is_numeric( $value ) && (int) $value % 2 === 0;
					}
				),
			Text::new( self::FIELD_KEYS['Text'] )
				->set_sanitize( 'sanitize_text_field' ),
			//
			Select::new( self::FIELD_KEYS['Select'] )
				->set_sanitize( Func\always( 15 ) ),
			//
			Media_Library::new( self::FIELD_KEYS['Media_Library'] )
				->set_sanitize( Func\always( 15 ) ),
			//
			Checkbox::new( self::FIELD_KEYS['Checkbox'] )
				->set_sanitize( Func\always( 15 ) ),
			//
			WP_Editor::new( self::FIELD_KEYS['WP_Editor'] )
				->set_sanitize( Func\always( 15 ) ),
			//
			Post_Picker::new( self::FIELD_KEYS['Post_Picker'] )
				->set_sanitize( Func\always( 15 ) ),
			//
			Checkbox_Group::new( self::FIELD_KEYS['Checkbox_Group'] )
				->set_sanitize( Func\always( 15 ) ),
			//
			Radio::new( self::FIELD_KEYS['Radio'] )
				->set_sanitize( Func\always( 15 ) ),
			//
			Colour::new( self::FIELD_KEYS['Colour'] )
				->set_sanitize( Func\always( 15 ) )
		);
	}

	/**
	 * Denotes if the settings should be grouped under a single key.
	 *
	 * @return bool
	 */
	protected function is_grouped(): bool {
		return false;
	}

	/**
	 * Denotes the group key/prefix (if not grouped).
	 *
	 * @return string
	 */
	public function group_key(): string {
		return 'Valid_Settings_Not_Grouped';
	}
}
