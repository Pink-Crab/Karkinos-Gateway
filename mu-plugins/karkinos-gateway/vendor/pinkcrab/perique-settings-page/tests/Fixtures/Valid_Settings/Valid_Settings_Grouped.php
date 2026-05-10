<?php

declare(strict_types=1);

/**
 * A valid settings class
 * Is grouped
 * Covers setting properties relating to the form.
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

use PinkCrab\Perique_Settings_Page\Setting\Field\Text;
use PinkCrab\Perique_Settings_Page\Setting\Field\Field;
use PinkCrab\Perique_Settings_Page\Setting\Field\Radio;
use PinkCrab\Perique_Settings_Page\Setting\Field\Colour;
use PinkCrab\Perique_Settings_Page\Setting\Field\Number;
use PinkCrab\Perique_Settings_Page\Setting\Field\Select;
use PinkCrab\Perique_Settings_Page\Setting\Field\Checkbox;
use PinkCrab\Perique_Settings_Page\Setting\Field\Repeater;
use PinkCrab\Perique_Settings_Page\Setting\Field\WP_Editor;
use PinkCrab\Perique_Settings_Page\Setting\Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Collection;
use PinkCrab\Perique_Settings_Page\Setting\Field\Media_Library;
use PinkCrab\Perique_Settings_Page\Setting\Field\Post_Picker;
use PinkCrab\Perique_Settings_Page\Setting\Field\Checkbox_Group;

class Valid_Settings_Grouped extends Abstract_Settings {

	/**
	 * Sets all the fields.
	 *
	 * @param Setting_Collection $settings
	 * @return Setting_Collection
	 */
	protected function fields( Setting_Collection $settings ): Setting_Collection {
		return $settings->push(
			//
			Number::new( 'number' )
				->set_label( 'Number Field' )
				->set_description( 'This is a field of digits' )
				->set_attribute( 'placeholder', 'Enter a number' )
				->set_min( 0 )
				->set_max( 10 )
				->set_step( 2 )
				->set_option( '0123456789', 'Some number' )
				->set_option( '2345434', '' ),
			//
			Text::new( 'text' )
				->set_label( 'String' )
				->set_description( 'Im for letters, well you can only put 1 letter, but still' )
				->set_placeholder( 'FOO' )
				->set_sanitize( 'sanitize_text_field' )
				->set_data( 'foo', 'bar' )
				->set_pattern( '[az]' )
				->set_attribute( 'title', 'Value can only be a single "a" or "z"' )
				->set_icon( 'https://miro.medium.com/max/1400/1*44799UW8y4KGlJb36fTD7Q.gif' )
				->set_option( 'pre selected', '' )
				->set_option( 'with optional', 'label' ),
			//
			Select::new( 'select' )
				->set_label( 'Multiple Select' )
				->set_option( 'A', 'Apple', 'Fruit' )
				->set_option( 'B', 'Banana', 'Fruit' )
				->set_option( 'F', 'Fish', 'Animal' )
				->set_data( 'foo', 'bar' )
				->set_option( 'Z', 'Zurp' )
				->set_multiple(),
			//
			Media_Library::new( 'media_upload' )
				->set_label( 'Some Upload' )
				->set_description( 'You can select an image here, it will save the media id to the DB.' ),
			//
			Checkbox::new( 'checkbox' )
				->set_label( 'some_checkbox' )
				->set_value( 'tree' )
				->set_checked_value( 'boo' )
				->set_data( 'foo', 'bar' )
				->set_flag( 'flag', 'baz' )
				->set_description( 'This is a single checkbox' ),
			//
			WP_Editor::new( 'wp_editor' )
				->set_label( 'WP_Editor' )
				->set_description( 'The wp editor' )
				->set_options(
					array(
						'media_buttons' => false,
					)
				),
			//
			Post_Picker::new( 'posts' )
				->set_label( 'Select a post' )
				->set_description( 'You can pick a post from the selection.' )
				->set_post_type( 'page' )
				->set_data( 'foo', 'bar' ),
			//
			Post_Picker::new( 'posts_multi' )
				->set_label( 'Select multiple posts' )
				->set_description( 'You can pick multiple posts.' )
				->set_post_type( 'page' )
				->set_multiple()
				->set_data( 'foo', 'bar' ),
			//
			Checkbox_Group::new( 'checkbox_group' )
				->set_label( 'Pick any checkboxes' )
				->set_description( 'You can pick as many or as little as you like' )
				->set_option( 'e', 'Option A' )
				->set_option( 'fgfdg', 'Option B' )
				->set_option( 'ffgsdfsdgdfg', 'Option C' )
				->set_option( 'fgfdgdfgdfgdfgfd', 'Option D' )
				->set_data( 'placeholder', 'Enter a number' ),
			//
			Radio::new( 'radio' )
				->set_label( 'Pick any radio' )
				->set_description( 'You can pick as many or as little as you like' )
				->set_option( 'a', 'Option A' )
				->set_option( 'b', 'Option B' )
				->set_option( 'c', 'Option C' )
				->set_option( 'd', 'Option D' ),
			//
			Colour::new( 'colour_picker' )
				->set_label( 'Colourful' )
				->set_description( 'This allows you to pick a colour' )
				->set_autocomplete( '#fff' ),
			//
			Repeater::new( 'repeater' )
				->set_add_to_group_label( 'Some Repeater' )
				->add_field(
					Number::new( 'RepNumber1' )
					->set_label( 'RepNumber1' )
				)
				->add_field(
					Number::new( 'RepNumber2' )
					->set_label( 'RepNumber2' )
					->set_description( 'You can pick as many or as little as you like' )
				)
				->add_field(
					Radio::new( 'RepRadio' )
					->set_label( 'Pick any radio' )
					->set_description( 'You can pick as many or as little as you like' )
					->set_option( 'a', 'Option A' )
					->set_option( 'b', 'Option B' )
					->set_option( 'c', 'Option C' )
					->set_option( 'd', 'Option D' )
				),
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
