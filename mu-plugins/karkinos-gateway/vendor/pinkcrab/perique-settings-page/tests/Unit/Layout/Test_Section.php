<?php

declare( strict_types=1 );

/**
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 *
 * @group Unit
 * @group Layout
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Unit\Layout;

use WP_UnitTestCase;
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Section;

class Test_Section extends WP_UnitTestCase {

	/** @testdox get_type returns "layout_section". */
	public function test_get_type(): void {
		$this->assertSame( 'layout_section', Section::of()->get_type() );
	}

	/** @testdox title() / get_title() round-trip. */
	public function test_title(): void {
		$section = Section::of()->title( 'My Section' );
		$this->assertSame( 'My Section', $section->get_title() );
	}

	/** @testdox Default title is empty. */
	public function test_default_title(): void {
		$this->assertSame( '', Section::of()->get_title() );
	}

	/** @testdox description() / get_description() round-trip. */
	public function test_description(): void {
		$section = Section::of()->description( 'A description' );
		$this->assertSame( 'A description', $section->get_description() );
	}

	/** @testdox Default description is empty. */
	public function test_default_description(): void {
		$this->assertSame( '', Section::of()->get_description() );
	}

	/** @testdox collapsible() defaults to true and stores the value. */
	public function test_collapsible(): void {
		$section = Section::of()->collapsible();
		$this->assertTrue( $section->is_collapsible() );
	}

	/** @testdox collapsible(false) clears collapsible. */
	public function test_not_collapsible(): void {
		$section = Section::of()->collapsible( false );
		$this->assertFalse( $section->is_collapsible() );
	}

	/** @testdox is_collapsible() defaults to false. */
	public function test_default_collapsible(): void {
		$this->assertFalse( Section::of()->is_collapsible() );
	}

	/** @testdox collapsed() implies collapsible. */
	public function test_collapsed_implies_collapsible(): void {
		$section = Section::of()->collapsed();
		$this->assertTrue( $section->is_collapsed() );
		$this->assertTrue( $section->is_collapsible() );
	}

	/** @testdox is_collapsed() defaults to false. */
	public function test_default_collapsed(): void {
		$this->assertFalse( Section::of()->is_collapsed() );
	}

	/** @testdox rtl() defaults to true. */
	public function test_rtl(): void {
		$section = Section::of()->rtl();
		$this->assertTrue( $section->is_rtl() );
	}

	/** @testdox is_rtl() defaults to false. */
	public function test_default_rtl(): void {
		$this->assertFalse( Section::of()->is_rtl() );
	}

	/** @testdox get_key() uses the title when set. */
	public function test_get_key_uses_title(): void {
		$section = Section::of()->title( 'My Page' );
		$this->assertSame( 'section_my-page', $section->get_key() );
	}

	/** @testdox get_key() falls back to auto-generated when no title. */
	public function test_get_key_falls_back(): void {
		$section = Section::of( Text::new( 'a' ) );
		$key     = $section->get_key();
		$this->assertStringContainsString( 'a', $key );
	}

	/** @testdox All fluent methods return static for chaining. */
	public function test_chains(): void {
		$section = Section::of();
		$this->assertSame( $section, $section->title( 't' ) );
		$this->assertSame( $section, $section->description( 'd' ) );
		$this->assertSame( $section, $section->collapsible() );
		$this->assertSame( $section, $section->collapsed() );
		$this->assertSame( $section, $section->rtl() );
	}
}
