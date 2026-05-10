<?php
/**
 * E2E fixture: Layout Kitchen Sink.
 *
 * Separate from the main Kitchen Sink so layout structure assertions
 * don't interfere with the field-level assertions. Exercises every
 * layout variant (Row, Grid, Stack, Section flavours, Divider,
 * Notice) plus a nested case.
 *
 * Values are not seeded — refresh_settings() will null every field,
 * which is fine because these tests only assert DOM structure.
 */

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Setting\Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Collection;
use PinkCrab\Perique_Settings_Page\Setting\Repository\WP_Options_Settings_Repository;
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Section;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Row;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Grid;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Stack;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Divider;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Notice;

class Test_Layout_Kitchen_Sink_Settings extends Abstract_Settings {

	public const OPTION_KEY = 'layout_kitchen_sink_settings';

	public function __construct() {
		parent::__construct( new WP_Options_Settings_Repository() );
	}

	protected function fields( Setting_Collection $settings ): Setting_Collection {
		return $settings->push(

			// ─────────────── Rows + Stack ───────────────
			Section::of(
				// Plain Row — two equal columns.
				Row::of(
					Text::new( 'row_p1' )->set_label( 'Plain Row A' ),
					Text::new( 'row_p2' )->set_label( 'Plain Row B' ),
				),

				// Sized Row — 1fr / 2fr / 1fr.
				Row::of(
					Text::new( 'row_s1' )->set_label( 'Sized A' ),
					Text::new( 'row_s2' )->set_label( 'Sized B (wide)' ),
					Text::new( 'row_s3' )->set_label( 'Sized C' ),
				)->sizes( 1, 2, 1 ),

				// Aligned Row — centered vertically.
				Row::of(
					Text::new( 'row_a1' )->set_label( 'Aligned A' ),
					Text::new( 'row_a2' )->set_label( 'Aligned B' ),
				)->align( 'center' )->gap( '24px' ),

				// Stack — vertical flex.
				Stack::of(
					Text::new( 'stack_1' )->set_label( 'Stack One' ),
					Text::new( 'stack_2' )->set_label( 'Stack Two' ),
				)->gap( '8px' ),
			)->title( 'Rows and Stack' )
			 ->description( 'Row and Stack layout variants.' ),

			// ─────────────── Grids ───────────────
			Section::of(
				Grid::of(
					Text::new( 'grid2_1' )->set_label( 'Grid2 1' ),
					Text::new( 'grid2_2' )->set_label( 'Grid2 2' ),
					Text::new( 'grid2_3' )->set_label( 'Grid2 3' ),
					Text::new( 'grid2_4' )->set_label( 'Grid2 4' ),
				)->columns( 2 ),

				Grid::of(
					Text::new( 'grid3_1' )->set_label( 'Grid3 1' ),
					Text::new( 'grid3_2' )->set_label( 'Grid3 2' ),
					Text::new( 'grid3_3' )->set_label( 'Grid3 3' ),
				)->columns( 3 ),
			)->title( 'Grids' ),

			// ─────────────── Divider ───────────────
			Section::of(
				Text::new( 'sep_before' )->set_label( 'Before Divider' ),
				Divider::make(),
				Text::new( 'sep_after' )->set_label( 'After Divider' ),
			)->title( 'Divider' ),

			// ─────────────── Notices ───────────────
			Section::of(
				Notice::info( 'This is an info notice.' ),
				Notice::warning( 'This is a warning notice.' ),
				Notice::error( 'This is an error notice.' ),
				Notice::success( 'This is a success notice.' ),
			)->title( 'Notices' ),

			// ─────────────── Collapsible Section ───────────────
			Section::of(
				Text::new( 'coll_1' )->set_label( 'Inside collapsible' ),
			)->title( 'Collapsible' )
			 ->collapsible(),

			// ─────────────── Collapsed Section ───────────────
			Section::of(
				Text::new( 'coll_hidden' )->set_label( 'Hidden on load' ),
			)->title( 'Starts Collapsed' )
			 ->collapsible()
			 ->collapsed(),

			// ─────────────── Nested Layouts ───────────────
			Section::of(
				Row::of(
					Grid::of(
						Text::new( 'nested_1' )->set_label( 'Nested 1' ),
						Text::new( 'nested_2' )->set_label( 'Nested 2' ),
					)->columns( 2 ),
					Stack::of(
						Text::new( 'nested_3' )->set_label( 'Nested 3' ),
						Text::new( 'nested_4' )->set_label( 'Nested 4' ),
					),
				)->sizes( 2, 1 ),
			)->title( 'Nested' )
			 ->description( 'Section > Row (sized) > Grid + Stack.' ),
		);
	}

	protected function is_grouped(): bool {
		return true;
	}

	public function group_key(): string {
		return self::OPTION_KEY;
	}
}
