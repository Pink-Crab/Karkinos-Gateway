/**
 * Repeater Kitchen Sink e2e.
 *
 * Pairs with tests/e2e/mu-plugins/fixtures/Test_Repeater_Kitchen_Sink_Settings.php.
 *
 * Coverage:
 *   - Page renders with N seeded rows (each row shows correct values)
 *   - Click "Add Link" → new empty row appears, sortorder updates
 *   - Click remove on a row → row disappears, sortorder updates
 *   - Submit with seeded rows → values persist as expected
 *   - Submit with rows reordered (via sortorder edit) → persisted in new order
 *   - Submit with zero rows (all removed) → empty repeater value
 *   - Drag-sort reorders rows via native HTML5 drag events
 */

const { test, expect } = require( '../../fixtures' );

const PAGE_URL  = '/wp-admin/admin.php?page=repeater-kitchen-sink';
const RESET_URL = `${ PAGE_URL }&repeater_reset=1`;
const DUMP      = '#repeater-stored';

/** Helper: read the JSON dump after navigation. */
const readDump = async ( page ) => {
	const raw = ( await page.locator( DUMP ).textContent() ) || '{}';
	return JSON.parse( raw );
};

/** Helper: submit bypassing HTML5 validation. */
const submitForm = async ( page ) => {
	await Promise.all( [
		page.waitForLoadState( 'networkidle' ),
		page.evaluate( () => {
			const form = document.querySelector( 'form' );
			HTMLFormElement.prototype.submit.call( form );
		} ),
	] );
};

test.describe( 'Repeater Kitchen Sink', () => {
	test.beforeEach( async ( { page } ) => {
		await page.goto( RESET_URL );
	} );

	// ─────────────────────────────────────────────────────────────────
	// Render
	// ─────────────────────────────────────────────────────────────────
	test.describe( 'Render', () => {
		test( 'page loads with the repeater and two seeded rows', async ( {
			page,
		} ) => {
			const repeater = page.locator( '#pc-repeater-links' );
			await expect( repeater ).toBeVisible();

			const rows = repeater.locator( '[data-repeater-row]' );
			await expect( rows ).toHaveCount( 2 );

			// Row 0.
			await expect(
				page.locator( 'input[name="links[platform][0]"]' )
			).toHaveValue( 'Twitter' );
			await expect(
				page.locator( 'input[name="links[url][0]"]' )
			).toHaveValue( 'https://twitter.com' );

			// Row 1.
			await expect(
				page.locator( 'input[name="links[platform][1]"]' )
			).toHaveValue( 'GitHub' );
			await expect(
				page.locator( 'input[name="links[url][1]"]' )
			).toHaveValue( 'https://github.com' );
		} );

		test( 'sortorder hidden input matches the seeded row indices', async ( {
			page,
		} ) => {
			await expect(
				page.locator( 'input[data-repeater-sortorder="links"]' )
			).toHaveValue( '0,1' );
		} );

		test( 'add button and template are present', async ( { page } ) => {
			await expect(
				page.locator( '[data-repeater-add="links"]' )
			).toBeVisible();
			await expect(
				page.locator( 'template[data-repeater-template="links"]' )
			).toBeAttached();
		} );
	} );

	// ─────────────────────────────────────────────────────────────────
	// Add row
	// ─────────────────────────────────────────────────────────────────
	test( 'clicking Add Link creates a new empty row with the next index', async ( {
		page,
	} ) => {
		await page.click( '[data-repeater-add="links"]' );

		const rows = page.locator(
			'#pc-repeater-links [data-repeater-row]'
		);
		await expect( rows ).toHaveCount( 3 );

		// New row should be index 2 with empty values.
		await expect(
			page.locator( 'input[name="links[platform][2]"]' )
		).toHaveValue( '' );
		await expect(
			page.locator( 'input[name="links[url][2]"]' )
		).toHaveValue( '' );

		// Sort order updated.
		await expect(
			page.locator( 'input[data-repeater-sortorder="links"]' )
		).toHaveValue( '0,1,2' );
	} );

	// ─────────────────────────────────────────────────────────────────
	// Remove row
	// ─────────────────────────────────────────────────────────────────
	test( 'removing the first row leaves only the second', async ( {
		page,
	} ) => {
		// Remove row 0.
		await page.click(
			'[data-repeater-remove="pc-repeater-links--0"]'
		);

		const rows = page.locator(
			'#pc-repeater-links [data-repeater-row]'
		);
		await expect( rows ).toHaveCount( 1 );

		// Row 1 should still have GitHub values.
		await expect(
			page.locator( 'input[name="links[platform][1]"]' )
		).toHaveValue( 'GitHub' );

		// Sort order is now just "1".
		await expect(
			page.locator( 'input[data-repeater-sortorder="links"]' )
		).toHaveValue( '1' );
	} );

	// ─────────────────────────────────────────────────────────────────
	// Submit + persistence
	// ─────────────────────────────────────────────────────────────────
	test.describe( 'Submit', () => {
		test( 'submitting seeded rows persists both rows', async ( {
			page,
		} ) => {
			await submitForm( page );
			const stored = await readDump( page );

			expect( stored.links ).toMatchObject( {
				platform: [ 'Twitter', 'GitHub' ],
				url: [ 'https://twitter.com', 'https://github.com' ],
			} );
		} );

		test( 'adding a row, filling it, and submitting persists three rows', async ( {
			page,
		} ) => {
			await page.click( '[data-repeater-add="links"]' );
			await page.fill( 'input[name="links[platform][2]"]', 'LinkedIn' );
			await page.fill(
				'input[name="links[url][2]"]',
				'https://linkedin.com'
			);

			await submitForm( page );
			const stored = await readDump( page );

			expect( stored.links.platform ).toHaveLength( 3 );
			expect( stored.links.platform[ 2 ] ).toBe( 'LinkedIn' );
			expect( stored.links.url[ 2 ] ).toBe( 'https://linkedin.com' );
		} );

		test( 'removing all rows and submitting persists an empty repeater', async ( {
			page,
		} ) => {
			// Remove both seeded rows.
			await page.click(
				'[data-repeater-remove="pc-repeater-links--0"]'
			);
			await page.click(
				'[data-repeater-remove="pc-repeater-links--1"]'
			);

			// No rows left.
			await expect(
				page.locator( '#pc-repeater-links [data-repeater-row]' )
			).toHaveCount( 0 );
			await expect(
				page.locator( 'input[data-repeater-sortorder="links"]' )
			).toHaveValue( '' );

			await submitForm( page );
			const stored = await readDump( page );

			// Both field arrays should be empty.
			expect( stored.links.platform ).toEqual( [] );
			expect( stored.links.url ).toEqual( [] );
		} );

		test( 'reordering via sortorder edit persists in the new order', async ( {
			page,
		} ) => {
			// Manually reverse the sortorder: "1,0" instead of "0,1".
			// This simulates a drag-reorder without relying on pointer events.
			await page.evaluate( () => {
				const sortInput = document.querySelector(
					'input[data-repeater-sortorder="links"]'
				);
				if ( sortInput ) sortInput.value = '1,0';
			} );

			await submitForm( page );
			const stored = await readDump( page );

			// GitHub (was index 1) should now be first.
			expect( stored.links.platform[ 0 ] ).toBe( 'GitHub' );
			expect( stored.links.url[ 0 ] ).toBe( 'https://github.com' );
			expect( stored.links.platform[ 1 ] ).toBe( 'Twitter' );
			expect( stored.links.url[ 1 ] ).toBe( 'https://twitter.com' );
		} );
	} );

	// ─────────────────────────────────────────────────────────────────
	// Drag-and-drop reorder (native HTML5 drag events)
	// ─────────────────────────────────────────────────────────────────
	test( 'dragging the second row above the first reorders the sortorder', async ( {
		page,
	} ) => {
		const row0 = page.locator( '[data-repeater-row="0"]' );
		const row1 = page.locator( '[data-repeater-row="1"]' );
		const handle1 = row1.locator( '.pc-repeater__drag-handle' );

		// Trigger a drag from row1's handle to above row0.
		// We need to use the dragTo API which dispatches native drag events.
		await handle1.dragTo( row0, { targetPosition: { x: 10, y: 5 } } );

		// After the drop, sortorder should be reversed.
		const sortorder = await page
			.locator( 'input[data-repeater-sortorder="links"]' )
			.inputValue();

		// The sort order should now show row 1 before row 0.
		expect( sortorder ).toBe( '1,0' );
	} );
} );
