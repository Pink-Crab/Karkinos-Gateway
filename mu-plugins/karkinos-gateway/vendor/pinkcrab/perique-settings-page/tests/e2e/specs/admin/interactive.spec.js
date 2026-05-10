/**
 * Interactive Kitchen Sink e2e.
 *
 * Covers Post_Picker, User_Picker, Media_Library and WP_Editor — fields
 * that require user interaction beyond simple fill+submit.
 *
 * Pairs with tests/e2e/mu-plugins/fixtures/Test_Interactive_Kitchen_Sink_Settings.php.
 *
 * Prerequisites (seeded by settings-page-bootstrap.php on first load):
 *   - A published post titled "E2E Test Post"
 *   - A dummy attachment titled "E2E Test Image"
 *   - The default admin user (created by wp-env)
 */

const { test, expect } = require( '../../fixtures' );

const PAGE_URL  = '/wp-admin/admin.php?page=interactive-kitchen-sink';
const RESET_URL = `${ PAGE_URL }&interactive_reset=1`;
const DUMP      = '#interactive-stored';

/** Submit bypassing HTML5 validation. */
const submitForm = async ( page ) => {
	await Promise.all( [
		page.waitForLoadState( 'networkidle' ),
		page.evaluate( () => {
			const form = document.querySelector( 'form' );
			HTMLFormElement.prototype.submit.call( form );
		} ),
	] );
};

const readDump = async ( page ) => {
	const raw = ( await page.locator( DUMP ).textContent() ) || '{}';
	return JSON.parse( raw );
};

test.describe( 'Interactive Kitchen Sink', () => {
	test.beforeEach( async ( { page } ) => {
		await page.goto( RESET_URL );
	} );

	// ─────────────────────────────────────────────────────────────────
	// Post Picker
	// ─────────────────────────────────────────────────────────────────
	test.describe( 'Post Picker', () => {
		test( 'renders with search input and hidden field', async ( {
			page,
		} ) => {
			const picker = page.locator(
				'.pc-picker[data-picker-key="pick_post"]'
			);
			await expect( picker ).toBeVisible();
			await expect( picker ).toHaveAttribute(
				'data-picker-type',
				'post'
			);

			const search = picker.locator( '.pc-picker__search' );
			await expect( search ).toBeVisible();
			await expect( search ).toHaveAttribute(
				'placeholder',
				'Search posts…'
			);

			// Hidden input for the value.
			await expect(
				picker.locator( 'input[data-picker-input="pick_post"]' )
			).toBeAttached();
		} );

		test( 'typing in the search shows a dropdown with results', async ( {
			page,
		} ) => {
			const picker = page.locator(
				'.pc-picker[data-picker-key="pick_post"]'
			);
			const search = picker.locator( '.pc-picker__search' );

			await search.fill( 'E2E' );

			// Wait for the debounced search to fire and dropdown to appear.
			const dropdown = picker.locator( '.pc-picker__dropdown' );
			await expect( dropdown ).toBeVisible( { timeout: 5000 } );

			// Should contain our seeded post.
			await expect(
				dropdown.locator( '.pc-picker__option', {
					hasText: 'E2E Test Post',
				} ).first()
			).toBeVisible();
		} );

		test( 'selecting a result adds a tag and sets the hidden input', async ( {
			page,
		} ) => {
			const picker = page.locator(
				'.pc-picker[data-picker-key="pick_post"]'
			);
			const search = picker.locator( '.pc-picker__search' );

			await search.fill( 'E2E' );
			const dropdown = picker.locator( '.pc-picker__dropdown' );
			await expect( dropdown ).toBeVisible( { timeout: 5000 } );

			// Click the option.
			await dropdown
				.locator( '.pc-picker__option', { hasText: 'E2E Test Post' } )
				.first()
				.click();

			// Tag should appear.
			await expect(
				picker.locator( '.pc-picker__tag' )
			).toBeVisible();
			await expect(
				picker.locator( '.pc-picker__tag' )
			).toContainText( 'E2E Test Post' );

			// Hidden input should hold the post ID (a number > 0).
			const val = await picker
				.locator( 'input[data-picker-input="pick_post"]' )
				.inputValue();
			expect( Number( val ) ).toBeGreaterThan( 0 );
		} );

		test( 'selected post persists after submit', async ( { page } ) => {
			const picker = page.locator(
				'.pc-picker[data-picker-key="pick_post"]'
			);
			const search = picker.locator( '.pc-picker__search' );

			await search.fill( 'E2E' );
			await expect(
				picker.locator( '.pc-picker__dropdown' )
			).toBeVisible( { timeout: 5000 } );
			await picker
				.locator( '.pc-picker__option', { hasText: 'E2E Test Post' } )
				.first()
				.click();

			await submitForm( page );
			const stored = await readDump( page );

			// Should be a numeric post ID string.
			expect( Number( stored.pick_post ) ).toBeGreaterThan( 0 );
		} );

		test( 'removing a selected post clears the hidden input', async ( {
			page,
		} ) => {
			const picker = page.locator(
				'.pc-picker[data-picker-key="pick_post"]'
			);
			const search = picker.locator( '.pc-picker__search' );

			// Select a post.
			await search.fill( 'E2E' );
			await expect(
				picker.locator( '.pc-picker__dropdown' )
			).toBeVisible( { timeout: 5000 } );
			await picker
				.locator( '.pc-picker__option', { hasText: 'E2E Test Post' } )
				.first()
				.click();
			await expect( picker.locator( '.pc-picker__tag' ) ).toBeVisible();

			// Remove it.
			await picker.locator( '.pc-picker__tag-remove' ).click();

			// Tag gone, hidden input empty.
			await expect( picker.locator( '.pc-picker__tag' ) ).toHaveCount(
				0
			);
			await expect(
				picker.locator( 'input[data-picker-input="pick_post"]' )
			).toHaveValue( '' );
		} );
	} );

	// ─────────────────────────────────────────────────────────────────
	// User Picker
	// ─────────────────────────────────────────────────────────────────
	test.describe( 'User Picker', () => {
		test( 'renders with search input', async ( { page } ) => {
			const picker = page.locator(
				'.pc-picker[data-picker-key="pick_user"]'
			);
			await expect( picker ).toBeVisible();
			await expect( picker ).toHaveAttribute(
				'data-picker-type',
				'user'
			);
		} );

		test( 'searching for "admin" shows the default admin user', async ( {
			page,
		} ) => {
			const picker = page.locator(
				'.pc-picker[data-picker-key="pick_user"]'
			);
			const search = picker.locator( '.pc-picker__search' );

			await search.fill( 'admin' );

			const dropdown = picker.locator( '.pc-picker__dropdown' );
			await expect( dropdown ).toBeVisible( { timeout: 5000 } );

			// wp-env creates an admin user with display_name "admin".
			await expect(
				dropdown.locator( '.pc-picker__option' ).first()
			).toBeVisible();
		} );

		test( 'selecting a user and submitting persists the user ID', async ( {
			page,
		} ) => {
			const picker = page.locator(
				'.pc-picker[data-picker-key="pick_user"]'
			);
			const search = picker.locator( '.pc-picker__search' );

			await search.fill( 'admin' );
			const dropdown = picker.locator( '.pc-picker__dropdown' );
			await expect( dropdown ).toBeVisible( { timeout: 5000 } );

			await dropdown.locator( '.pc-picker__option' ).first().click();
			await expect( picker.locator( '.pc-picker__tag' ) ).toBeVisible();

			await submitForm( page );
			const stored = await readDump( page );

			expect( Number( stored.pick_user ) ).toBeGreaterThan( 0 );
		} );
	} );

	// ─────────────────────────────────────────────────────────────────
	// Media Library
	// ─────────────────────────────────────────────────────────────────
	test.describe( 'Media Library', () => {
		test( 'renders with select button visible, clear button hidden, and empty hidden input', async ( {
			page,
		} ) => {
			await expect(
				page.locator( '#media_upload_media_image' )
			).toBeVisible();
			await expect(
				page.locator(
					'.pc-settings-media-select[data-key="media_image"]'
				)
			).toBeVisible();
			// Clear button is hidden until a selection is made.
			await expect(
				page.locator(
					'.pc-settings-media-clear[data-key="media_image"]'
				)
			).toBeHidden();
			await expect(
				page.locator(
					'input[type="hidden"][name="media_image"]'
				)
			).toHaveValue( '' );
		} );

		test( 'clicking Select opens the WP media modal', async ( {
			page,
		} ) => {
			await page.click(
				'.pc-settings-media-select[data-key="media_image"]'
			);

			// WP media modal has the class .media-modal.
			await expect(
				page.locator( '.media-modal' )
			).toBeVisible( { timeout: 5000 } );
		} );

		test( 'selecting an attachment sets the hidden input and shows a preview', async ( {
			page,
		} ) => {
			await page.click(
				'.pc-settings-media-select[data-key="media_image"]'
			);

			// Wait for modal.
			const modal = page.locator( '.media-modal' );
			await expect( modal ).toBeVisible( { timeout: 5000 } );

			// Modal opens on the "Upload files" tab by default; switch to the library.
			await modal
				.locator( '.media-router .media-menu-item:has-text("Media Library")' )
				.click();

			// Click the first attachment in the grid.
			const attachment = modal.locator( '.attachment' ).first();
			await expect( attachment ).toBeVisible( { timeout: 10000 } );
			await attachment.click();

			// Click the "Use this media" button.
			await page.click( '.media-button-select' );

			// Modal closes.
			await expect( modal ).not.toBeVisible();

			// Hidden input now has the attachment ID.
			const val = await page
				.locator( 'input[type="hidden"][name="media_image"]' )
				.inputValue();
			expect( Number( val ) ).toBeGreaterThan( 0 );
		} );

		test( 'selected media persists after submit', async ( { page } ) => {
			// Select an attachment.
			await page.click(
				'.pc-settings-media-select[data-key="media_image"]'
			);
			const modal = page.locator( '.media-modal' );
			await expect( modal ).toBeVisible( { timeout: 5000 } );
			await modal
				.locator( '.media-router .media-menu-item:has-text("Media Library")' )
				.click();
			await modal.locator( '.attachment' ).first().click();
			await page.click( '.media-button-select' );
			await expect( modal ).not.toBeVisible();

			await submitForm( page );
			const stored = await readDump( page );

			expect( Number( stored.media_image ) ).toBeGreaterThan( 0 );
		} );

		test( 'clearing the selection empties the hidden input', async ( {
			page,
		} ) => {
			// First select something.
			await page.click(
				'.pc-settings-media-select[data-key="media_image"]'
			);
			const modal = page.locator( '.media-modal' );
			await expect( modal ).toBeVisible( { timeout: 5000 } );
			await modal
				.locator( '.media-router .media-menu-item:has-text("Media Library")' )
				.click();
			await modal.locator( '.attachment' ).first().click();
			await page.click( '.media-button-select' );
			await expect( modal ).not.toBeVisible();

			// Now clear.
			await page.click(
				'.pc-settings-media-clear[data-key="media_image"]'
			);

			await expect(
				page.locator( 'input[type="hidden"][name="media_image"]' )
			).toHaveValue( '' );
		} );
	} );

	// ─────────────────────────────────────────────────────────────────
	// WP Editor
	// ─────────────────────────────────────────────────────────────────
	test.describe( 'WP Editor', () => {
		test( 'renders the editor textarea with seeded content', async ( {
			page,
		} ) => {
			// WP Editor renders as a textarea (which may be hidden behind
			// TinyMCE). The textarea always exists in the DOM.
			const textarea = page.locator( 'textarea[name="editor"]' );
			await expect( textarea ).toBeAttached();

			// Seeded value.
			const val = await textarea.inputValue();
			expect( val ).toContain( 'Hello editor.' );
		} );

		test( 'editing via the text tab and submitting persists the new value', async ( {
			page,
		} ) => {
			// Switch to the text/HTML tab to avoid TinyMCE iframe complexity.
			const textTab = page.locator( '#editor-html' );
			if ( await textTab.isVisible() ) {
				await textTab.click();
			}

			const textarea = page.locator( 'textarea[name="editor"]' );

			// Clear and type new content.
			await textarea.fill( '<p>Updated editor content.</p>' );

			await submitForm( page );
			const stored = await readDump( page );

			expect( stored.editor ).toContain( 'Updated editor content.' );
		} );

		test( 'empty editor submit persists empty string', async ( {
			page,
		} ) => {
			// Switch to text tab.
			const textTab = page.locator( '#editor-html' );
			if ( await textTab.isVisible() ) {
				await textTab.click();
			}

			const textarea = page.locator( 'textarea[name="editor"]' );
			await textarea.fill( '' );

			await submitForm( page );
			const stored = await readDump( page );

			expect( stored.editor ).toBe( '' );
		} );
	} );
} );
