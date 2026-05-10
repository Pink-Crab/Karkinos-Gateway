/**
 * Kitchen sink e2e: every supported field type, basic + full variants.
 *
 * Pairs with `tests/e2e/mu-plugins/fixtures/Test_Kitchen_Sink_Settings.php`.
 *
 * Three groups of assertions:
 *   1. Render — each field shows up with the defaults defined in the fixture.
 *   2. Submit — fill every fillable field, submit, expect a success notice.
 *   3. Round-trip — after the post-submit redirect/reload, every field
 *      reflects the new value (proves the repository round-trip works).
 *
 * Non-trivially-fillable fields (WP_Editor, Media_Library, Post_Picker,
 * User_Picker, Repeater) get render-only assertions in this spec — they
 * have their own focused specs in follow-up work.
 */

const { test, expect } = require( '../../fixtures' );

const PAGE_URL = '/wp-admin/admin.php?page=kitchen-sink-settings';
const RESET_URL = `${ PAGE_URL }&kitchen_sink_reset=1`;
const STORED_DUMP = '#kitchen-sink-stored';

test.describe( 'Kitchen Sink Settings Page', () => {
	test.beforeEach( async ( { page } ) => {
		// Hit the mu-plugin reset endpoint, which writes the kitchen sink
		// option to its known defaults and then redirects back to the
		// clean page URL. Playwright follows the redirect.
		await page.goto( RESET_URL );
	} );

	// ─────────────────────────────────────────────────────────────────
	// Render: defaults + attributes
	// ─────────────────────────────────────────────────────────────────
	test.describe( 'Render', () => {
		test( 'page loads with title and form', async ( { page } ) => {
			await expect(
				page.getByRole( 'heading', { name: 'Kitchen Sink Settings' } )
			).toBeVisible();
			// FC's Form::__construct sets wrapper_id( 'form-' . $name ) so the
			// rendered <form> tag has id="form-kitchen-sink-settings".
			await expect(
				page.locator( 'form#form-kitchen-sink-settings' )
			).toBeVisible();
			await expect(
				page.locator( 'input[type="submit"][value="Save Settings"]' )
			).toBeVisible();
		} );

		test( 'text fields render with defaults and attributes', async ( {
			page,
		} ) => {
			await expect( page.locator( 'input[name="text_basic"]' ) ).toHaveValue(
				'Hello World'
			);

			const full = page.locator( 'input[name="text_full"]' );
			await expect( full ).toHaveValue( 'Initial' );
			await expect( full ).toHaveAttribute( 'placeholder', 'Type something…' );
			await expect( full ).toHaveAttribute( 'pattern', '[A-Za-z0-9 ]+' );
			await expect( full ).toHaveAttribute( 'required', '' );
			await expect( full ).toHaveAttribute( 'data-foo', 'bar' );
		} );

		test( 'email fields render', async ( { page } ) => {
			await expect( page.locator( 'input[name="email_basic"]' ) ).toHaveValue(
				'basic@example.com'
			);
			await expect( page.locator( 'input[name="email_basic"]' ) ).toHaveAttribute(
				'type',
				'email'
			);

			const full = page.locator( 'input[name="email_full"]' );
			await expect( full ).toHaveValue( 'full@example.com' );
			await expect( full ).toHaveAttribute( 'placeholder', 'name@example.com' );
			await expect( full ).toHaveAttribute( 'required', '' );
		} );

		test( 'phone fields render', async ( { page } ) => {
			await expect( page.locator( 'input[name="phone_basic"]' ) ).toHaveAttribute(
				'type',
				'tel'
			);
			await expect( page.locator( 'input[name="phone_basic"]' ) ).toHaveValue(
				'+44 1234 567890'
			);

			const full = page.locator( 'input[name="phone_full"]' );
			await expect( full ).toHaveValue( '+44 7700 900000' );
			await expect( full ).toHaveAttribute( 'placeholder', '+44 …' );
			await expect( full ).toHaveAttribute( 'pattern', '\\+?[0-9 ]+' );
		} );

		test( 'url fields render', async ( { page } ) => {
			await expect( page.locator( 'input[name="url_basic"]' ) ).toHaveAttribute(
				'type',
				'url'
			);
			await expect( page.locator( 'input[name="url_basic"]' ) ).toHaveValue(
				'https://example.com'
			);

			const full = page.locator( 'input[name="url_full"]' );
			await expect( full ).toHaveValue( 'https://example.org' );
			await expect( full ).toHaveAttribute( 'placeholder', 'https://…' );
			await expect( full ).toHaveAttribute( 'required', '' );
		} );

		test( 'password fields render', async ( { page } ) => {
			await expect(
				page.locator( 'input[name="password_basic"]' )
			).toHaveAttribute( 'type', 'password' );

			const full = page.locator( 'input[name="password_full"]' );
			await expect( full ).toHaveAttribute( 'type', 'password' );
			await expect( full ).toHaveAttribute( 'placeholder', 'Enter a password' );
			await expect( full ).toHaveAttribute( 'required', '' );
		} );

		test( 'textarea fields render', async ( { page } ) => {
			await expect(
				page.locator( 'textarea[name="textarea_basic"]' )
			).toHaveValue( 'Line one\nLine two' );

			const full = page.locator( 'textarea[name="textarea_full"]' );
			await expect( full ).toHaveValue( 'Initial multiline content.' );
			await expect( full ).toHaveAttribute( 'placeholder', 'Tell us about yourself…' );
			await expect( full ).toHaveAttribute( 'rows', '6' );
			await expect( full ).toHaveAttribute( 'cols', '40' );
		} );

		test( 'hidden field renders', async ( { page } ) => {
			const hidden = page.locator( 'input[name="hidden_basic"]' );
			await expect( hidden ).toHaveAttribute( 'type', 'hidden' );
			await expect( hidden ).toHaveValue( 'hidden-default-value' );
		} );

		test( 'number fields render with min/max/step', async ( { page } ) => {
			const basic = page.locator( 'input[name="number_basic"]' );
			await expect( basic ).toHaveAttribute( 'type', 'number' );
			await expect( basic ).toHaveValue( '42' );

			const full = page.locator( 'input[name="number_full"]' );
			await expect( full ).toHaveValue( '10' );
			await expect( full ).toHaveAttribute( 'min', '0' );
			await expect( full ).toHaveAttribute( 'max', '100' );
			await expect( full ).toHaveAttribute( 'step', '5' );
		} );

		test( 'select fields render', async ( { page } ) => {
			const basic = page.locator( 'select[name="select_basic"]' );
			await expect( basic ).toBeVisible();
			await expect( basic.locator( 'option' ) ).toHaveCount( 3 );
			await expect( basic ).toHaveValue( 'green' );

			const full = page.locator( 'select[name="select_full[]"]' );
			await expect( full ).toBeVisible();
			await expect( full ).toHaveAttribute( 'multiple', '' );
			await expect( full.locator( 'option' ) ).toHaveCount( 4 );
			// Default: apple + cherry selected.
			const selected = await full.evaluate(
				/** @param {HTMLSelectElement} el */ ( el ) =>
					Array.from( el.selectedOptions ).map( ( o ) => o.value )
			);
			expect( selected.sort() ).toEqual( [ 'apple', 'cherry' ] );
		} );

		test( 'radio fields render', async ( { page } ) => {
			const basic = page.locator( 'input[type="radio"][name="radio_basic"]' );
			await expect( basic ).toHaveCount( 3 );
			await expect(
				page.locator( 'input[type="radio"][name="radio_basic"]:checked' )
			).toHaveValue( 'b' );

			const full = page.locator( 'input[type="radio"][name="radio_full"]' );
			await expect( full ).toHaveCount( 3 );
			await expect(
				page.locator( 'input[type="radio"][name="radio_full"]:checked' )
			).toHaveValue( 'medium' );
		} );

		test( 'checkbox fields render', async ( { page } ) => {
			const basic = page.locator( 'input[type="checkbox"][name="checkbox_basic"]' );
			await expect( basic ).toHaveAttribute( 'value', '1' );
			await expect( basic ).toBeChecked();

			const full = page.locator( 'input[type="checkbox"][name="checkbox_full"]' );
			await expect( full ).toHaveAttribute( 'value', 'yes' );
			await expect( full ).not.toBeChecked();
		} );

		test( 'checkbox group fields render', async ( { page } ) => {
			const basicBoxes = page.locator(
				'input[type="checkbox"][name="checkbox_group_basic[]"]'
			);
			await expect( basicBoxes ).toHaveCount( 3 );
			// Default: one + three checked.
			await expect(
				page.locator(
					'input[type="checkbox"][name="checkbox_group_basic[]"]:checked'
				)
			).toHaveCount( 2 );

			const fullBoxes = page.locator(
				'input[type="checkbox"][name="checkbox_group_full[]"]'
			);
			await expect( fullBoxes ).toHaveCount( 4 );
			await expect(
				page.locator(
					'input[type="checkbox"][name="checkbox_group_full[]"]:checked'
				)
			).toHaveCount( 0 );
		} );

		test( 'colour fields render', async ( { page } ) => {
			const basic = page.locator( 'input[name="colour_basic"]' );
			await expect( basic ).toHaveAttribute( 'type', 'color' );
			await expect( basic ).toHaveValue( '#ff0000' );

			const full = page.locator( 'input[name="colour_full"]' );
			await expect( full ).toHaveValue( '#3858e9' );
			await expect( full ).toHaveAttribute( 'autocomplete', 'off' );
		} );

		test( 'wp_editor renders', async ( { page } ) => {
			// WP_Editor renders a wp_editor() block; the textarea fallback is named
			// after the field key.
			await expect(
				page.locator( 'textarea[name="wp_editor_basic"]' )
			).toBeAttached();
		} );

		test( 'media library field renders', async ( { page } ) => {
			await expect(
				page.locator( '#media_upload_media_library_basic' )
			).toBeVisible();
			await expect(
				page.locator(
					'input[type="hidden"][name="media_library_basic"]'
				)
			).toBeAttached();
			await expect(
				page.locator( '.pc-settings-media-select[data-key="media_library_basic"]' )
			).toBeVisible();
		} );

		test( 'post picker renders', async ( { page } ) => {
			const picker = page.locator(
				'.pc-picker[data-picker-key="post_picker_basic"]'
			);
			await expect( picker ).toBeVisible();
			await expect( picker ).toHaveAttribute( 'data-picker-type', 'post' );
			await expect( picker ).toHaveAttribute( 'data-picker-post-type', 'post' );
			await expect( picker.locator( '.pc-picker__search' ) ).toHaveAttribute(
				'placeholder',
				'Search posts…'
			);
		} );

		test( 'user picker renders', async ( { page } ) => {
			const picker = page.locator(
				'.pc-picker[data-picker-key="user_picker_basic"]'
			);
			await expect( picker ).toBeVisible();
			await expect( picker ).toHaveAttribute( 'data-picker-type', 'user' );
			await expect( picker ).toHaveAttribute( 'data-picker-role', 'administrator' );
		} );

		test( 'field group renders bracket-named children', async ( { page } ) => {
			await expect(
				page.locator( 'input[name="field_group_address[line_1]"]' )
			).toBeVisible();
			await expect(
				page.locator( 'input[name="field_group_address[city]"]' )
			).toBeVisible();
			await expect(
				page.locator( 'input[name="field_group_address[postcode]"]' )
			).toBeVisible();
		} );

		test( 'repeater renders', async ( { page } ) => {
			await expect( page.locator( '#pc-repeater-repeater_basic' ) ).toBeVisible();
			await expect(
				page.locator( '[data-repeater-add="repeater_basic"]' )
			).toBeVisible();
			await expect(
				page.locator(
					'input[type="hidden"][data-repeater-sortorder="repeater_basic"]'
				)
			).toBeAttached();
		} );

		test( 'pre template renders with static data and sits above the form', async ( {
			page,
		} ) => {
			const pre = page.locator( '#kitchen-sink-pre' );
			await expect( pre ).toBeVisible();
			// Heading came from the $pre_data property default on the page class.
			await expect( pre.locator( 'h2' ) ).toHaveText( 'Pre Template Heading' );

			// Pre template must appear before the form in the DOM.
			const preIndex = await pre.evaluate(
				/** @param {HTMLElement} el */ ( el ) =>
					Array.from(
						el.ownerDocument.querySelectorAll( '*' )
					).indexOf( el )
			);
			const formIndex = await page
				.locator( 'form#form-kitchen-sink-settings' )
				.evaluate(
					/** @param {HTMLElement} el */ ( el ) =>
						Array.from(
							el.ownerDocument.querySelectorAll( '*' )
						).indexOf( el )
				);
			expect( preIndex ).toBeLessThan( formIndex );
		} );

		test( 'post template renders with runtime data from before_render()', async ( {
			page,
		} ) => {
			const post = page.locator( '#kitchen-sink-post' );
			await expect( post ).toBeVisible();

			// before_render() reads the live settings and feeds them into the
			// template — values should match the seeded defaults.
			await expect(
				page.locator( '[data-testid="post-text-basic"]' )
			).toHaveText( 'Hello World' );
			await expect(
				page.locator( '[data-testid="post-number-basic"]' )
			).toHaveText( '42' );

			// Post template must appear after the form in the DOM.
			const postIndex = await post.evaluate(
				/** @param {HTMLElement} el */ ( el ) =>
					Array.from(
						el.ownerDocument.querySelectorAll( '*' )
					).indexOf( el )
			);
			const formIndex = await page
				.locator( 'form#form-kitchen-sink-settings' )
				.evaluate(
					/** @param {HTMLElement} el */ ( el ) =>
						Array.from(
							el.ownerDocument.querySelectorAll( '*' )
						).indexOf( el )
				);
			expect( postIndex ).toBeGreaterThan( formIndex );
		} );
	} );

	// ─────────────────────────────────────────────────────────────────
	// Submit + round-trip
	// ─────────────────────────────────────────────────────────────────
	test.describe( 'Submit and round-trip', () => {
		test( 'fills every fillable field, saves, and reload reflects new values', async ( {
			page,
		} ) => {
			// ─── Fill text-like ───
			await page.fill( 'input[name="text_basic"]', 'updated basic' );
			await page.fill( 'input[name="text_full"]', 'updated full' );
			await page.fill( 'input[name="email_basic"]', 'newbasic@example.com' );
			await page.fill( 'input[name="email_full"]', 'newfull@example.com' );
			await page.fill( 'input[name="phone_basic"]', '+44 9876 543210' );
			await page.fill( 'input[name="phone_full"]', '+44 7000 000001' );
			await page.fill( 'input[name="url_basic"]', 'https://updated-basic.test' );
			await page.fill( 'input[name="url_full"]', 'https://updated-full.test' );
			await page.fill( 'input[name="password_basic"]', 'pw-basic-1!' );
			await page.fill( 'input[name="password_full"]', 'pw-full-2!' );
			await page.fill( 'textarea[name="textarea_basic"]', 'new basic body' );
			await page.fill( 'textarea[name="textarea_full"]', 'new full body' );

			// ─── Number ───
			await page.fill( 'input[name="number_basic"]', '99' );
			await page.fill( 'input[name="number_full"]', '25' );

			// ─── Select ───
			await page.selectOption( 'select[name="select_basic"]', 'blue' );
			await page.selectOption( 'select[name="select_full[]"]', [
				'banana',
				'date',
			] );

			// ─── Radio ───
			await page
				.locator( 'input[type="radio"][name="radio_basic"][value="c"]' )
				.check();
			await page
				.locator( 'input[type="radio"][name="radio_full"][value="large"]' )
				.check();

			// ─── Checkboxes ───
			// Toggle basic OFF (was on).
			await page
				.locator( 'input[type="checkbox"][name="checkbox_basic"]' )
				.uncheck();
			// Toggle full ON (was off).
			await page
				.locator( 'input[type="checkbox"][name="checkbox_full"]' )
				.check();

			// ─── Checkbox groups ───
			// basic: was [one, three]; switch to [two] only.
			await page
				.locator(
					'input[type="checkbox"][name="checkbox_group_basic[]"][value="one"]'
				)
				.uncheck();
			await page
				.locator(
					'input[type="checkbox"][name="checkbox_group_basic[]"][value="three"]'
				)
				.uncheck();
			await page
				.locator(
					'input[type="checkbox"][name="checkbox_group_basic[]"][value="two"]'
				)
				.check();
			// full: was none; pick comments + newsletter.
			await page
				.locator(
					'input[type="checkbox"][name="checkbox_group_full[]"][value="comments"]'
				)
				.check();
			await page
				.locator(
					'input[type="checkbox"][name="checkbox_group_full[]"][value="newsletter"]'
				)
				.check();

			// ─── Colour ───
			// Native color inputs accept .fill() in Playwright.
			await page.fill( 'input[name="colour_basic"]', '#00ff00' );
			await page.fill( 'input[name="colour_full"]', '#123456' );

			// ─── Field Group ───
			await page.fill(
				'input[name="field_group_address[line_1]"]',
				'10 Downing Street'
			);
			await page.fill(
				'input[name="field_group_address[city]"]',
				'London'
			);
			await page.fill(
				'input[name="field_group_address[postcode]"]',
				'SW1A 2AA'
			);

			// ─── Submit ───
			// Wait for the navigation triggered by the submit click so the
			// next assertions run against the post-save render, not the
			// in-flight request.
			await Promise.all( [
				page.waitForLoadState( 'networkidle' ),
				page.click( 'input[type="submit"][value="Save Settings"]' ),
			] );

			// ─── Persistence: read the JSON dump (source of truth) ───
			// We assert against the dump first because it's deterministic
			// even if the admin notice markup changes. The dump is rendered
			// inline by Test_Kitchen_Sink_Page::render_view().
			const storedRaw =
				( await page.locator( STORED_DUMP ).textContent() ) || '{}';
			let stored;
			try {
				stored = JSON.parse( storedRaw );
			} catch ( e ) {
				throw new Error(
					`Could not parse persisted option JSON.\nRaw dump:\n${ storedRaw }`
				);
			}
			// Single combined assertion — failure prints the entire dump
			// so we can diagnose any field that didn't round-trip.
			expect(
				stored,
				`persisted option after submit:\n${ JSON.stringify(
					stored,
					null,
					2
				) }`
			).toMatchObject( {
				text_basic: 'updated basic',
				text_full: 'updated full',
				email_basic: 'newbasic@example.com',
				email_full: 'newfull@example.com',
				phone_basic: '+44 9876 543210',
				phone_full: '+44 7000 000001',
				url_basic: 'https://updated-basic.test',
				url_full: 'https://updated-full.test',
				password_basic: 'pw-basic-1!',
				password_full: 'pw-full-2!',
				textarea_basic: 'new basic body',
				textarea_full: 'new full body',
				number_basic: 99,
				number_full: 25,
				select_basic: 'blue',
				radio_basic: 'c',
				radio_full: 'large',
				checkbox_basic: '',
				checkbox_full: 'yes',
				colour_basic: '#00ff00',
				colour_full: '#123456',
				field_group_address: {
					line_1: '10 Downing Street',
					city: 'London',
					postcode: 'SW1A 2AA',
				},
				hidden_basic: 'hidden-default-value',
			} );
			// Array-valued fields — sort-agnostic comparison.
			expect( [ ...stored.select_full ].sort() ).toEqual( [
				'banana',
				'date',
			] );
			expect( stored.checkbox_group_basic ).toEqual( [ 'two' ] );
			expect( [ ...stored.checkbox_group_full ].sort() ).toEqual( [
				'comments',
				'newsletter',
			] );

			// ─── Round-trip: every fillable field reflects the saved value ───
			await expect( page.locator( 'input[name="text_basic"]' ) ).toHaveValue(
				'updated basic'
			);
			await expect( page.locator( 'input[name="text_full"]' ) ).toHaveValue(
				'updated full'
			);
			await expect( page.locator( 'input[name="email_basic"]' ) ).toHaveValue(
				'newbasic@example.com'
			);
			await expect( page.locator( 'input[name="email_full"]' ) ).toHaveValue(
				'newfull@example.com'
			);
			await expect( page.locator( 'input[name="phone_basic"]' ) ).toHaveValue(
				'+44 9876 543210'
			);
			await expect( page.locator( 'input[name="phone_full"]' ) ).toHaveValue(
				'+44 7000 000001'
			);
			await expect( page.locator( 'input[name="url_basic"]' ) ).toHaveValue(
				'https://updated-basic.test'
			);
			await expect( page.locator( 'input[name="url_full"]' ) ).toHaveValue(
				'https://updated-full.test'
			);
			// Password values are intentionally NOT rendered back into a password
			// input on reload (browser/security behaviour) — assert via the dump.
			await expect(
				page.locator( 'textarea[name="textarea_basic"]' )
			).toHaveValue( 'new basic body' );
			await expect(
				page.locator( 'textarea[name="textarea_full"]' )
			).toHaveValue( 'new full body' );
			await expect( page.locator( 'input[name="number_basic"]' ) ).toHaveValue(
				'99'
			);
			await expect( page.locator( 'input[name="number_full"]' ) ).toHaveValue(
				'25'
			);
			await expect( page.locator( 'select[name="select_basic"]' ) ).toHaveValue(
				'blue'
			);
			const fullSelected = await page
				.locator( 'select[name="select_full[]"]' )
				.evaluate(
					/** @param {HTMLSelectElement} el */ ( el ) =>
						Array.from( el.selectedOptions ).map( ( o ) => o.value )
				);
			expect( fullSelected.sort() ).toEqual( [ 'banana', 'date' ] );

			await expect(
				page.locator( 'input[type="radio"][name="radio_basic"]:checked' )
			).toHaveValue( 'c' );
			await expect(
				page.locator( 'input[type="radio"][name="radio_full"]:checked' )
			).toHaveValue( 'large' );

			await expect(
				page.locator( 'input[type="checkbox"][name="checkbox_basic"]' )
			).not.toBeChecked();
			await expect(
				page.locator( 'input[type="checkbox"][name="checkbox_full"]' )
			).toBeChecked();

			await expect(
				page.locator(
					'input[type="checkbox"][name="checkbox_group_basic[]"]:checked'
				)
			).toHaveCount( 1 );
			await expect(
				page.locator(
					'input[type="checkbox"][name="checkbox_group_basic[]"][value="two"]'
				)
			).toBeChecked();
			await expect(
				page.locator(
					'input[type="checkbox"][name="checkbox_group_full[]"]:checked'
				)
			).toHaveCount( 2 );

			await expect( page.locator( 'input[name="colour_basic"]' ) ).toHaveValue(
				'#00ff00'
			);
			await expect( page.locator( 'input[name="colour_full"]' ) ).toHaveValue(
				'#123456'
			);

			await expect(
				page.locator( 'input[name="field_group_address[line_1]"]' )
			).toHaveValue( '10 Downing Street' );
			await expect(
				page.locator( 'input[name="field_group_address[city]"]' )
			).toHaveValue( 'London' );
			await expect(
				page.locator( 'input[name="field_group_address[postcode]"]' )
			).toHaveValue( 'SW1A 2AA' );

			// Hidden field — should still hold its default (we never changed it).
			await expect( page.locator( 'input[name="hidden_basic"]' ) ).toHaveValue(
				'hidden-default-value'
			);

			// ─── Post template round-trip ───
			// before_render() pulls live settings into the post template on
			// every render. After submit, those values should reflect the
			// newly-saved text_basic / number_basic.
			await expect(
				page.locator( '[data-testid="post-text-basic"]' )
			).toHaveText( 'updated basic' );
			await expect(
				page.locator( '[data-testid="post-number-basic"]' )
			).toHaveText( '99' );
		} );
	} );

	// ─────────────────────────────────────────────────────────────────
	// Validation errors
	// ─────────────────────────────────────────────────────────────────
	test.describe( 'Validation errors', () => {
		test( 'multiple failing fields emit ONE grouped notice and leave the option untouched', async ( {
			page,
		} ) => {
			// Make two fields fail server-side validation at the same time:
			//   text_full has `set_validate(len >= 3)` — empty string fails.
			//   number_full has `set_validate(>= 5)` — 0 fails.
			// Both fields pass HTML5 constraints (or we bypass via form.submit())
			// so the request actually reaches the PHP form handler.

			await page.fill( 'input[name="text_full"]', '' );
			await page.fill( 'input[name="number_full"]', '0' );

			// form.submit() (the JS method) bypasses HTML5 constraint
			// validation — exactly what we want so the PHP validators get
			// a chance to run.
			await Promise.all( [
				page.waitForLoadState( 'networkidle' ),
				page.evaluate( () => {
					const form = /** @type {HTMLFormElement} */ (
						document.querySelector( 'form#form-kitchen-sink-settings' )
					);
					// The form has an <input name="submit"> which shadows
					// the native form.submit() method as a DOM property
					// reference. Pull the real method off the prototype.
					HTMLFormElement.prototype.submit.call( form );
				} ),
			] );

			// ─── Exactly ONE error notice ───
			const errorNotices = page.locator( '.notice-error' );
			await expect( errorNotices ).toHaveCount( 1 );

			// ─── Both field errors live inside that single notice ───
			const notice = errorNotices.first();
			await expect( notice ).toContainText( 'Validation failed' );
			// form_handler generates "Validation failed for <label>." per field.
			const errorList = notice.locator( 'ul.pc-settings-error-list li' );
			await expect( errorList ).toHaveCount( 2 );
			await expect( notice ).toContainText( 'Text Full' );
			await expect( notice ).toContainText( 'Number Full' );

			// ─── No success notice ───
			await expect( page.locator( '.notice-success' ) ).toHaveCount( 0 );

			// ─── Option was NOT updated — defaults still in the dump ───
			const stored = JSON.parse(
				( await page.locator( STORED_DUMP ).textContent() ) || '{}'
			);
			// Seeded defaults should still be there.
			expect( stored.text_full ).toBe( 'Initial' );
			expect( stored.number_full ).toBe( 10 );
			expect( stored.text_basic ).toBe( 'Hello World' );
		} );

		test( 'a single failing field still uses the grouped markup (list of 1)', async ( {
			page,
		} ) => {
			// Only text_full fails this time; number_full stays valid.
			await page.fill( 'input[name="text_full"]', '' );

			await Promise.all( [
				page.waitForLoadState( 'networkidle' ),
				page.evaluate( () => {
					const form = /** @type {HTMLFormElement} */ (
						document.querySelector( 'form#form-kitchen-sink-settings' )
					);
					// The form has an <input name="submit"> which shadows
					// the native form.submit() method as a DOM property
					// reference. Pull the real method off the prototype.
					HTMLFormElement.prototype.submit.call( form );
				} ),
			] );

			await expect( page.locator( '.notice-error' ) ).toHaveCount( 1 );
			await expect(
				page.locator( '.notice-error ul.pc-settings-error-list li' )
			).toHaveCount( 1 );
			await expect( page.locator( '.notice-error' ) ).toContainText(
				'Text Full'
			);

			// Option still at defaults.
			const stored = JSON.parse(
				( await page.locator( STORED_DUMP ).textContent() ) || '{}'
			);
			expect( stored.text_full ).toBe( 'Initial' );
		} );

		test( 'invalid nonce emits a single plain error notice (no field error list)', async ( {
			page,
		} ) => {
			// Corrupt the nonce value before submitting — everything else
			// is valid so the only failure is nonce verification.
			await page.evaluate( () => {
				const nonce = /** @type {HTMLInputElement | null} */ (
					document.querySelector( 'input[name="pc_settings_nonce"]' )
				);
				if ( nonce ) {
					nonce.value = 'not-a-real-nonce';
				}
			} );

			await Promise.all( [
				page.waitForLoadState( 'networkidle' ),
				page.evaluate( () => {
					const form = /** @type {HTMLFormElement} */ (
						document.querySelector( 'form#form-kitchen-sink-settings' )
					);
					// The form has an <input name="submit"> which shadows
					// the native form.submit() method as a DOM property
					// reference. Pull the real method off the prototype.
					HTMLFormElement.prototype.submit.call( form );
				} ),
			] );

			await expect( page.locator( '.notice-error' ) ).toHaveCount( 1 );
			await expect( page.locator( '.notice-error' ) ).toContainText(
				'nonce'
			);
			// The grouped list should NOT be present — nonce failures
			// don't carry per-field errors.
			await expect(
				page.locator( '.notice-error ul.pc-settings-error-list' )
			).toHaveCount( 0 );
		} );
	} );
} );
