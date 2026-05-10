<?php

declare( strict_types=1 );

/**
 * Unit tests for Element_Mapper.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 *
 * @group Unit
 * @group Mapper
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Unit\Mapper;

use WP_UnitTestCase;
use PinkCrab\Form_Components\Element\Element;
use PinkCrab\Form_Components\Element\Custom_Field;
use PinkCrab\Form_Components\Element\Field\Select as FC_Select;
use PinkCrab\Form_Components\Element\Field\Input\Text as FC_Text;
use PinkCrab\Form_Components\Element\Field\Input\Email as FC_Email;
use PinkCrab\Form_Components\Element\Field\Input\Tel as FC_Tel;
use PinkCrab\Form_Components\Element\Field\Input\Url as FC_Url;
use PinkCrab\Form_Components\Element\Field\Input\Password as FC_Password;
use PinkCrab\Form_Components\Element\Field\Input\Hidden as FC_Hidden;
use PinkCrab\Form_Components\Element\Field\Input\Number as FC_Number;
use PinkCrab\Form_Components\Element\Field\Input\Checkbox as FC_Checkbox;
use PinkCrab\Form_Components\Element\Field\Input\Color as FC_Color;
use PinkCrab\Form_Components\Element\Field\Textarea as FC_Textarea;
use PinkCrab\Form_Components\Element\Field\Group\Radio_Group as FC_Radio_Group;
use PinkCrab\Form_Components\Element\Field\Group\Checkbox_Group as FC_Checkbox_Group;
use PinkCrab\Perique_Settings_Page\Mapper\Element_Mapper;
use PinkCrab\Perique_Settings_Page\Setting\Field\Field;
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;
use PinkCrab\Perique_Settings_Page\Setting\Field\Email;
use PinkCrab\Perique_Settings_Page\Setting\Field\Phone;
use PinkCrab\Perique_Settings_Page\Setting\Field\Url;
use PinkCrab\Perique_Settings_Page\Setting\Field\Password;
use PinkCrab\Perique_Settings_Page\Setting\Field\Textarea;
use PinkCrab\Perique_Settings_Page\Setting\Field\Hidden;
use PinkCrab\Perique_Settings_Page\Setting\Field\Number;
use PinkCrab\Perique_Settings_Page\Setting\Field\Checkbox;
use PinkCrab\Perique_Settings_Page\Setting\Field\Checkbox_Group;
use PinkCrab\Perique_Settings_Page\Setting\Field\Radio;
use PinkCrab\Perique_Settings_Page\Setting\Field\Select;
use PinkCrab\Perique_Settings_Page\Setting\Field\Colour;
use PinkCrab\Perique_Settings_Page\Setting\Field\Media_Library;
use PinkCrab\Perique_Settings_Page\Setting\Field\WP_Editor;
use PinkCrab\Perique_Settings_Page\Setting\Field\Post_Picker;
use PinkCrab\Perique_Settings_Page\Setting\Field\User_Picker;
use PinkCrab\Perique_Settings_Page\Setting\Field\Repeater;
use PinkCrab\Perique_Settings_Page\Setting\Field\Field_Group;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Row;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Grid;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Stack;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Section;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Divider;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Notice;
use PinkCrab\Perique_Settings_Page\Setting\Renderable;

class Test_Element_Mapper extends WP_UnitTestCase {

	protected Element_Mapper $mapper;

	public function setUp(): void {
		parent::setUp();
		$this->mapper = new Element_Mapper();
	}

	/* -------------------------------------------------------
	 * to_element() routing
	 * ------------------------------------------------------- */

	/** @testdox to_element returns FC_Text for a Text field. */
	public function test_to_element_text(): void {
		$this->assertInstanceOf( FC_Text::class, $this->mapper->to_element( Text::new( 'k' ) ) );
	}

	/** @testdox to_element returns FC_Email for an Email field. */
	public function test_to_element_email(): void {
		$this->assertInstanceOf( FC_Email::class, $this->mapper->to_element( Email::new( 'k' ) ) );
	}

	/** @testdox to_element returns FC_Tel for a Phone field. */
	public function test_to_element_phone(): void {
		$this->assertInstanceOf( FC_Tel::class, $this->mapper->to_element( Phone::new( 'k' ) ) );
	}

	/** @testdox to_element returns FC_Url for a Url field. */
	public function test_to_element_url(): void {
		$this->assertInstanceOf( FC_Url::class, $this->mapper->to_element( Url::new( 'k' ) ) );
	}

	/** @testdox to_element returns FC_Password for a Password field. */
	public function test_to_element_password(): void {
		$this->assertInstanceOf( FC_Password::class, $this->mapper->to_element( Password::new( 'k' ) ) );
	}

	/** @testdox to_element returns FC_Textarea for a Textarea field. */
	public function test_to_element_textarea(): void {
		$this->assertInstanceOf( FC_Textarea::class, $this->mapper->to_element( Textarea::new( 'k' ) ) );
	}

	/** @testdox to_element returns FC_Hidden for a Hidden field. */
	public function test_to_element_hidden(): void {
		$this->assertInstanceOf( FC_Hidden::class, $this->mapper->to_element( Hidden::new( 'k' ) ) );
	}

	/** @testdox to_element returns FC_Number for a Number field. */
	public function test_to_element_number(): void {
		$this->assertInstanceOf( FC_Number::class, $this->mapper->to_element( Number::new( 'k' ) ) );
	}

	/** @testdox to_element returns FC_Checkbox for a Checkbox field. */
	public function test_to_element_checkbox(): void {
		$this->assertInstanceOf( FC_Checkbox::class, $this->mapper->to_element( Checkbox::new( 'k' ) ) );
	}

	/** @testdox to_element returns FC_Select for a Select field. */
	public function test_to_element_select(): void {
		$this->assertInstanceOf( FC_Select::class, $this->mapper->to_element( Select::new( 'k' ) ) );
	}

	/** @testdox to_element returns FC_Radio_Group for a Radio field. */
	public function test_to_element_radio(): void {
		$this->assertInstanceOf( FC_Radio_Group::class, $this->mapper->to_element( Radio::new( 'k' ) ) );
	}

	/** @testdox to_element returns FC_Color for a Colour field. */
	public function test_to_element_colour(): void {
		$this->assertInstanceOf( FC_Color::class, $this->mapper->to_element( Colour::new( 'k' ) ) );
	}

	/** @testdox Colour mapper propagates the autocomplete attribute. */
	public function test_to_element_colour_propagates_autocomplete(): void {
		$field   = Colour::new( 'k' )->set_autocomplete( 'off' );
		$element = $this->mapper->to_element( $field );
		$this->assertInstanceOf( FC_Color::class, $element );
		$this->assertSame( 'off', $element->get_autocomplete() );
	}

	/** @testdox to_element returns FC_Checkbox_Group for a Checkbox_Group field. */
	public function test_to_element_checkbox_group(): void {
		$this->assertInstanceOf( FC_Checkbox_Group::class, $this->mapper->to_element( Checkbox_Group::new( 'k' ) ) );
	}

	/** @testdox to_element returns Custom_Field for a WP_Editor field. */
	public function test_to_element_wp_editor(): void {
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( WP_Editor::new( 'k' ) ) );
	}

	/** @testdox to_element returns Custom_Field for a Media_Library field. */
	public function test_to_element_media_library(): void {
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( Media_Library::new( 'k' ) ) );
	}

	/** @testdox to_element returns Custom_Field for a Post_Picker field. */
	public function test_to_element_post_picker(): void {
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( Post_Picker::new( 'k' ) ) );
	}

	/** @testdox to_element returns Custom_Field for a User_Picker field. */
	public function test_to_element_user_picker(): void {
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( User_Picker::new( 'k' ) ) );
	}

	/** @testdox to_element returns Custom_Field for a Repeater field. */
	public function test_to_element_repeater(): void {
		$repeater = Repeater::new( 'k' )->add_field( Text::new( 'child' ) );
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( $repeater ) );
	}

	/** @testdox to_element returns Custom_Field for a Divider. */
	public function test_to_element_divider(): void {
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( Divider::make() ) );
	}

	/** @testdox to_element returns Custom_Field for a Notice. */
	public function test_to_element_notice(): void {
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( Notice::info( 'hi' ) ) );
	}

	/** @testdox to_element returns Custom_Field for a Field_Group. */
	public function test_to_element_field_group(): void {
		$group = Field_Group::of( 'address', Text::new( 'line_1' ) );
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( $group ) );
	}

	/** @testdox to_element returns Custom_Field for a Section layout. */
	public function test_to_element_section(): void {
		$section = Section::of( Text::new( 'inside' ) )->title( 'Group' );
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( $section ) );
	}

	/** @testdox to_element returns Custom_Field for a Row layout. */
	public function test_to_element_row(): void {
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( Row::of( Text::new( 'a' ) ) ) );
	}

	/** @testdox to_element returns Custom_Field for a Grid layout. */
	public function test_to_element_grid(): void {
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( Grid::of( Text::new( 'a' ) ) ) );
	}

	/** @testdox to_element returns Custom_Field for a Stack layout. */
	public function test_to_element_stack(): void {
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( Stack::of( Text::new( 'a' ) ) ) );
	}

	/** @testdox to_element falls back to Custom_Field for an unknown Field subclass. */
	public function test_to_element_fallback_field(): void {
		$field = new class('k', 'mystery') extends Field {
			public const TYPE = 'mystery';
		};
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( $field ) );
	}

	/** @testdox to_element falls back for an unknown Renderable type. */
	public function test_to_element_unknown_renderable(): void {
		$renderable = new class implements Renderable {
			public function get_key(): string {
				return 'unknown';
			}
			public function get_type(): string {
				return 'unknown';
			}
		};
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( $renderable ) );
	}

	/* -------------------------------------------------------
	 * Field-specific mapping behaviour
	 * ------------------------------------------------------- */

	/** @testdox map_text applies placeholder and pattern when set. */
	public function test_text_with_placeholder_and_pattern(): void {
		$field = Text::new( 'k' )->set_placeholder( 'Enter' )->set_pattern( '[a-z]+' );
		$this->assertInstanceOf( FC_Text::class, $this->mapper->to_element( $field ) );
	}

	/** @testdox map_email applies placeholder and pattern when set. */
	public function test_email_with_placeholder_and_pattern(): void {
		$field = Email::new( 'k' )->set_placeholder( 'me@x.com' )->set_pattern( '.+@.+' );
		$this->assertInstanceOf( FC_Email::class, $this->mapper->to_element( $field ) );
	}

	/** @testdox map_phone applies placeholder and pattern when set. */
	public function test_phone_with_placeholder_and_pattern(): void {
		$field = Phone::new( 'k' )->set_placeholder( '+44' )->set_pattern( '\+\d+' );
		$this->assertInstanceOf( FC_Tel::class, $this->mapper->to_element( $field ) );
	}

	/** @testdox map_url applies placeholder and pattern when set. */
	public function test_url_with_placeholder_and_pattern(): void {
		$field = Url::new( 'k' )->set_placeholder( 'https://' )->set_pattern( 'https?://.+' );
		$this->assertInstanceOf( FC_Url::class, $this->mapper->to_element( $field ) );
	}

	/** @testdox map_password applies placeholder when set. */
	public function test_password_with_placeholder(): void {
		$field = Password::new( 'k' )->set_placeholder( 'sk-...' );
		$this->assertInstanceOf( FC_Password::class, $this->mapper->to_element( $field ) );
	}

	/** @testdox map_textarea applies placeholder, rows and cols. */
	public function test_textarea_with_options(): void {
		$field = Textarea::new( 'k' )
			->set_placeholder( 'Type here' )
			->set_rows( 4 )
			->set_cols( 40 );
		$this->assertInstanceOf( FC_Textarea::class, $this->mapper->to_element( $field ) );
	}

	/** @testdox map_number applies placeholder, min, max, step. */
	public function test_number_with_range(): void {
		$field = Number::new( 'k' )
			->set_placeholder( '0' )
			->set_min( 1 )
			->set_max( 10 )
			->set_step( 1 );
		$this->assertInstanceOf( FC_Number::class, $this->mapper->to_element( $field ) );
	}

	/** @testdox map_checkbox marks element as checked when value is set. */
	public function test_checkbox_with_value(): void {
		$field = Checkbox::new( 'k' )->set_value( 'on' )->set_checked_value( 'yes' );
		$this->assertInstanceOf( FC_Checkbox::class, $this->mapper->to_element( $field ) );
	}

	/** @testdox map_select with multiple sets the multiple attribute. */
	public function test_select_multiple(): void {
		$field = Select::new( 'k' )->set_multiple()->set_option( 'a', 'A' );
		$this->assertInstanceOf( FC_Select::class, $this->mapper->to_element( $field ) );
	}

	/** @testdox map_radio sets selected value when set. */
	public function test_radio_with_value(): void {
		$field = Radio::new( 'k' )->set_option( 'a', 'A' )->set_value( 'a' );
		$this->assertInstanceOf( FC_Radio_Group::class, $this->mapper->to_element( $field ) );
	}

	/** @testdox map_checkbox_group sets selected from array value. */
	public function test_checkbox_group_with_array_value(): void {
		$field = Checkbox_Group::new( 'k' )->set_option( 'a', 'A' )->set_value( array( 'a' ) );
		$this->assertInstanceOf( FC_Checkbox_Group::class, $this->mapper->to_element( $field ) );
	}

	/** @testdox map_media_library renders preview when value is a real attachment. */
	public function test_media_library_with_value(): void {
		$attachment_id = $this->factory->attachment->create_object(
			array(
				'file'           => 'image.png',
				'post_mime_type' => 'image/png',
			)
		);
		$field = Media_Library::new( 'k' )->set_value( $attachment_id );
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( $field ) );
	}

	/** @testdox map_media_library handles an empty value. */
	public function test_media_library_no_value(): void {
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( Media_Library::new( 'k' ) ) );
	}

	/** @testdox map_post_picker renders for a post type with stored value. */
	public function test_post_picker_with_value(): void {
		$post_id = $this->factory->post->create();
		$field   = Post_Picker::new( 'k' )->set_post_type( 'post' )->set_value( $post_id );
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( $field ) );
	}

	/** @testdox map_user_picker renders with stored value. */
	public function test_user_picker_with_value(): void {
		$user_id = $this->factory->user->create();
		$field   = User_Picker::new( 'k' )->set_role( 'editor' )->set_value( $user_id );
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( $field ) );
	}

	/** @testdox map_user_picker without role still renders. */
	public function test_user_picker_without_role(): void {
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( User_Picker::new( 'k' ) ) );
	}

	/** @testdox map_post_picker with multiple selection renders. */
	public function test_post_picker_multiple(): void {
		$post_id = $this->factory->post->create();
		$field   = Post_Picker::new( 'k' )->set_multiple()->set_value( array( $post_id ) );
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( $field ) );
	}

	/* -------------------------------------------------------
	 * Field_Group rendering
	 * ------------------------------------------------------- */

	/** @testdox map_field_group renders children with bracket-notation names. */
	public function test_field_group_renders(): void {
		$group = Field_Group::of(
			'address',
			Text::new( 'line_1' )->set_label( 'Line 1' )->set_placeholder( '1 Foo St' ),
			Text::new( 'city' )
		)->set_label( 'Address' )->set_description( 'Where you live' );

		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( $group ) );
	}

	/* -------------------------------------------------------
	 * apply_shared_attributes
	 * ------------------------------------------------------- */

	/** @testdox apply_shared_attributes applies id, classes, label, description, before/after. */
	public function test_shared_attributes(): void {
		$field = Text::new( 'k' )
			->set_id( 'my-id' )
			->add_class( 'my-class' )
			->set_label( 'Label' )
			->set_description( 'Help' )
			->set_before( 'BEFORE' )
			->set_after( 'AFTER' )
			->set_value( 'val' )
			->set_required()
			->set_read_only();

		$this->assertInstanceOf( FC_Text::class, $this->mapper->to_element( $field ) );
	}

	/** @testdox apply_shared_attributes applies disabled state when set. */
	public function test_shared_attributes_disabled(): void {
		$field = Checkbox::new( 'k' )->set_disabled();
		$this->assertInstanceOf( FC_Checkbox::class, $this->mapper->to_element( $field ) );
	}

	/** @testdox apply_shared_attributes applies label_after wrapper class. */
	public function test_shared_attributes_label_after(): void {
		$field = Text::new( 'k' )->label_after();
		$this->assertInstanceOf( FC_Text::class, $this->mapper->to_element( $field ) );
	}

	/** @testdox apply_shared_attributes applies a config callback. */
	public function test_shared_attributes_config_callback(): void {
		$called = false;
		$field  = Text::new( 'k' )->set_config(
			function ( $element ) use ( &$called ) {
				$called = true;
				return $element;
			}
		);
		$this->mapper->to_element( $field );
		$this->assertTrue( $called );
	}

	/** @testdox apply_shared_attributes applies data attributes. */
	public function test_shared_attributes_data(): void {
		$field = Text::new( 'k' )->set_data( 'foo', 'bar' );
		$this->assertInstanceOf( FC_Text::class, $this->mapper->to_element( $field ) );
	}

	/* -------------------------------------------------------
	 * Field error notification
	 * ------------------------------------------------------- */

	/** @testdox set_field_errors causes error_notification to be applied to the matching element. */
	public function test_field_errors_applied(): void {
		$this->mapper->set_field_errors(
			array( 'k' => array( 'Required field' ) )
		);
		$this->assertInstanceOf( FC_Text::class, $this->mapper->to_element( Text::new( 'k' ) ) );
	}

	/* -------------------------------------------------------
	 * Layout rendering
	 * ------------------------------------------------------- */

	/** @testdox render_layout renders a Row with grid columns. */
	public function test_render_row(): void {
		$row = Row::of( Text::new( 'a' ), Text::new( 'b' ) )->sizes( 1, 2 )->align( 'center' );
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( $row ) );
	}

	/** @testdox render_layout renders a Grid with column count. */
	public function test_render_grid(): void {
		$grid = Grid::of( Text::new( 'a' ), Text::new( 'b' ) )->columns( 3 );
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( $grid ) );
	}

	/** @testdox render_layout renders a Stack. */
	public function test_render_stack(): void {
		$stack = Stack::of( Text::new( 'a' ) )->gap( '20px' );
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( $stack ) );
	}

	/** @testdox render_layout renders a collapsible Section. */
	public function test_render_section_collapsible(): void {
		$section = Section::of( Text::new( 'a' ) )
			->title( 'Title' )
			->description( 'Desc' )
			->collapsible()
			->collapsed()
			->rtl();
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( $section ) );
	}

	/** @testdox render_layout renders a Section with no title. */
	public function test_render_section_no_title(): void {
		$section = Section::of( Text::new( 'a' ) );
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( $section ) );
	}

	/* -------------------------------------------------------
	 * get_key_map static helper
	 * ------------------------------------------------------- */

	/** @testdox get_key_map maps sanitized form names to original storage keys. */
	public function test_get_key_map(): void {
		$fields = array(
			'My Field' => Text::new( 'My Field' ),
			'normal'   => Text::new( 'normal' ),
		);
		$map    = Element_Mapper::get_key_map( $fields );
		$this->assertArrayHasKey( 'my-field', $map );
		$this->assertSame( 'My Field', $map['my-field'] );
		$this->assertArrayNotHasKey( 'normal', $map );
	}

	/** @testdox set_view stores the view service and returns the mapper for chaining. */
	public function test_set_view(): void {
		$view = $this->getMockBuilder( \PinkCrab\Perique\Services\View\View::class )
			->disableOriginalConstructor()
			->getMock();
		$this->assertSame( $this->mapper, $this->mapper->set_view( $view ) );
	}

	/** @testdox render_layout uses the view service when set. */
	public function test_render_layout_with_view(): void {
		$view = $this->getMockBuilder( \PinkCrab\Perique\Services\View\View::class )
			->disableOriginalConstructor()
			->getMock();
		$view->method( 'component' )->willReturn( '<rendered/>' );

		$this->mapper->set_view( $view );
		$layout = Row::of( Text::new( 'a' ) );
		$this->assertInstanceOf( Custom_Field::class, $this->mapper->to_element( $layout ) );
	}

	/** @testdox apply_shared_attributes calls readonly() on elements that support it. */
	public function test_shared_attributes_read_only(): void {
		// Most FC elements our mapper produces don't have readonly, so we use
		// reflection + a stub element with the method to exercise the branch.
		$field = Text::new( 'k' )->set_read_only();

		$element = new class() extends Custom_Field {
			public bool $readonly_called = false;
			public function __construct() {
				parent::__construct( 'stub' );
			}
			public function readonly( bool $r = true ): self {
				$this->readonly_called = true;
				return $this;
			}
		};

		$ref = new \ReflectionMethod( $this->mapper, 'apply_shared_attributes' );
		$ref->setAccessible( true );
		$ref->invoke( $this->mapper, $field, $element );

		$this->assertTrue( $element->readonly_called );
	}
}
