/**
 * Layout Kitchen Sink e2e.
 *
 * Pairs with tests/e2e/mu-plugins/fixtures/Test_Layout_Kitchen_Sink_Settings.php.
 *
 * Pure rendering assertions — no submission, no persistence. Each test
 * pins one layout variant's DOM structure so regressions in the
 * Element_Mapper layout renderers are caught.
 */

const { test, expect } = require( '../../fixtures' );

const PAGE_URL = '/wp-admin/admin.php?page=layout-kitchen-sink';

test.describe( 'Layout Kitchen Sink', () => {
	test.beforeEach( async ( { page } ) => {
		await page.goto( PAGE_URL );
	} );

	test( 'page loads with title and form', async ( { page } ) => {
		await expect(
			page.getByRole( 'heading', { name: 'Layout Kitchen Sink' } )
		).toBeVisible();
		await expect(
			page.locator( 'form#form-layout-kitchen-sink' )
		).toBeVisible();
	} );

	// ─────────────────────────────────────────────────────────────────
	// Row
	// ─────────────────────────────────────────────────────────────────
	test.describe( 'Row', () => {
		test( 'plain row renders with equal 1fr columns', async ( { page } ) => {
			// Find the row that contains row_p1.
			const row = page
				.locator( '.pc-form__row', {
					has: page.locator( 'input[name="row_p1"]' ),
				} )
				.first();
			await expect( row ).toBeVisible();

			// Two children → two 1fr columns.
			const style = ( await row.getAttribute( 'style' ) ) || '';
			expect( style ).toContain( 'grid-template-columns:1fr 1fr' );
			expect( style ).toContain( 'display:grid' );

			await expect( row.locator( 'input[name="row_p1"]' ) ).toBeVisible();
			await expect( row.locator( 'input[name="row_p2"]' ) ).toBeVisible();
		} );

		test( 'sized row applies 1fr 2fr 1fr', async ( { page } ) => {
			const row = page
				.locator( '.pc-form__row', {
					has: page.locator( 'input[name="row_s1"]' ),
				} )
				.first();
			const style = ( await row.getAttribute( 'style' ) ) || '';
			expect( style ).toContain( 'grid-template-columns:1fr 2fr 1fr' );

			await expect( row.locator( 'input[name="row_s1"]' ) ).toBeVisible();
			await expect( row.locator( 'input[name="row_s2"]' ) ).toBeVisible();
			await expect( row.locator( 'input[name="row_s3"]' ) ).toBeVisible();
		} );

		test( 'aligned row sets align-items and custom gap', async ( { page } ) => {
			const row = page
				.locator( '.pc-form__row', {
					has: page.locator( 'input[name="row_a1"]' ),
				} )
				.first();
			const style = ( await row.getAttribute( 'style' ) ) || '';
			expect( style ).toContain( 'align-items:center' );
			expect( style ).toContain( 'gap:24px' );
		} );
	} );

	// ─────────────────────────────────────────────────────────────────
	// Grid
	// ─────────────────────────────────────────────────────────────────
	test.describe( 'Grid', () => {
		test( '2-column grid renders with the right modifier class and columns', async ( {
			page,
		} ) => {
			const grid = page
				.locator( '.pc-form__grid.pc-form__grid--2', {
					has: page.locator( 'input[name="grid2_1"]' ),
				} )
				.first();
			await expect( grid ).toBeVisible();

			const style = ( await grid.getAttribute( 'style' ) ) || '';
			expect( style ).toContain( 'grid-template-columns:repeat(2,1fr)' );

			// All four children inside.
			for ( const key of [ 'grid2_1', 'grid2_2', 'grid2_3', 'grid2_4' ] ) {
				await expect(
					grid.locator( `input[name="${ key }"]` )
				).toBeVisible();
			}
		} );

		test( '3-column grid renders with repeat(3,1fr)', async ( { page } ) => {
			const grid = page
				.locator( '.pc-form__grid.pc-form__grid--3', {
					has: page.locator( 'input[name="grid3_1"]' ),
				} )
				.first();
			const style = ( await grid.getAttribute( 'style' ) ) || '';
			expect( style ).toContain( 'grid-template-columns:repeat(3,1fr)' );

			for ( const key of [ 'grid3_1', 'grid3_2', 'grid3_3' ] ) {
				await expect(
					grid.locator( `input[name="${ key }"]` )
				).toBeVisible();
			}
		} );
	} );

	// ─────────────────────────────────────────────────────────────────
	// Stack
	// ─────────────────────────────────────────────────────────────────
	test( 'stack renders as column flex with custom gap', async ( { page } ) => {
		const stack = page
			.locator( '.pc-form__stack', {
				has: page.locator( 'input[name="stack_1"]' ),
			} )
			.first();
		await expect( stack ).toBeVisible();

		const style = ( await stack.getAttribute( 'style' ) ) || '';
		expect( style ).toContain( 'display:flex' );
		expect( style ).toContain( 'flex-direction:column' );
		expect( style ).toContain( 'gap:8px' );

		await expect( stack.locator( 'input[name="stack_1"]' ) ).toBeVisible();
		await expect( stack.locator( 'input[name="stack_2"]' ) ).toBeVisible();
	} );

	// ─────────────────────────────────────────────────────────────────
	// Divider
	// ─────────────────────────────────────────────────────────────────
	test( 'divider renders as hr.pc-form__divider', async ( { page } ) => {
		await expect( page.locator( 'hr.pc-form__divider' ) ).toBeVisible();

		// Fields before and after exist on either side.
		await expect( page.locator( 'input[name="sep_before"]' ) ).toBeVisible();
		await expect( page.locator( 'input[name="sep_after"]' ) ).toBeVisible();
	} );

	// ─────────────────────────────────────────────────────────────────
	// Notices (info / warning / error / success)
	// ─────────────────────────────────────────────────────────────────
	test.describe( 'Notice', () => {
		for ( const [ level, message ] of [
			[ 'info', 'This is an info notice.' ],
			[ 'warning', 'This is a warning notice.' ],
			[ 'error', 'This is an error notice.' ],
			[ 'success', 'This is a success notice.' ],
		] ) {
			test( `${ level } notice renders with the correct level modifier and message`, async ( {
				page,
			} ) => {
				const notice = page.locator(
					`.pc-form__notice.pc-form__notice--${ level }`
				);
				await expect( notice ).toBeVisible();
				await expect( notice ).toContainText( message );
			} );
		}
	} );

	// ─────────────────────────────────────────────────────────────────
	// Section (collapsible + collapsed + titles)
	// ─────────────────────────────────────────────────────────────────
	test.describe( 'Section', () => {
		test( 'section with title renders h3.pc-form__section-title', async ( {
			page,
		} ) => {
			await expect(
				page.locator( '.pc-form__section-title', {
					hasText: 'Rows and Stack',
				} )
			).toBeVisible();
		} );

		test( 'section description renders below the title', async ( { page } ) => {
			await expect(
				page.locator( '.pc-form__section-description', {
					hasText: 'Row and Stack layout variants',
				} )
			).toBeVisible();
		} );

		test( 'collapsible section has data-collapsible attribute', async ( {
			page,
		} ) => {
			const section = page
				.locator( '.pc-form__section[data-collapsible="true"]', {
					has: page.locator( 'input[name="coll_1"]' ),
				} )
				.first();
			await expect( section ).toBeVisible();
			// Not collapsed by default — body visible.
			const body = section.locator( '.pc-form__section-body' );
			await expect( body ).toBeVisible();
		} );

		test( 'collapsed section has data-collapsed and body starts hidden', async ( {
			page,
		} ) => {
			const section = page
				.locator( '.pc-form__section[data-collapsed="true"]', {
					has: page.locator( 'input[name="coll_hidden"]' ),
				} )
				.first();
			await expect( section ).toBeAttached();
			// Body has inline style display:none so it won't be visible.
			const body = section.locator( '.pc-form__section-body' );
			await expect( body ).toHaveAttribute( 'style', /display:\s*none/ );
		} );
	} );

	// ─────────────────────────────────────────────────────────────────
	// Nested layouts
	// ─────────────────────────────────────────────────────────────────
	test( 'nested Section > Row > (Grid + Stack) renders all children', async ( {
		page,
	} ) => {
		const nestedSection = page
			.locator( '.pc-form__section', {
				has: page.locator( 'input[name="nested_1"]' ),
			} )
			.first();
		await expect( nestedSection ).toBeVisible();

		// Row with 2fr 1fr sizing inside the section.
		const row = nestedSection.locator( '.pc-form__row' ).first();
		const rowStyle = ( await row.getAttribute( 'style' ) ) || '';
		expect( rowStyle ).toContain( 'grid-template-columns:2fr 1fr' );

		// Grid and Stack inside the row.
		const grid = row.locator( '.pc-form__grid.pc-form__grid--2' );
		await expect( grid ).toBeVisible();
		await expect( grid.locator( 'input[name="nested_1"]' ) ).toBeVisible();
		await expect( grid.locator( 'input[name="nested_2"]' ) ).toBeVisible();

		const stack = row.locator( '.pc-form__stack' );
		await expect( stack ).toBeVisible();
		await expect( stack.locator( 'input[name="nested_3"]' ) ).toBeVisible();
		await expect( stack.locator( 'input[name="nested_4"]' ) ).toBeVisible();
	} );
} );
