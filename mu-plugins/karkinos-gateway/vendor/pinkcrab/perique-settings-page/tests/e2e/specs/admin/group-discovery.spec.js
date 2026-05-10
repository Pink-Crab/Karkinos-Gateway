/**
 * Heavy E2E test for the Hooks::GROUPS_PROCESSED listener + issue #58 dedupe.
 *
 * Bootstrap registers four overlapping scenarios:
 *   - Test_Discovery_Page_A    Settings_Page in registration_classes ONLY.
 *   - Test_Discovery_Page_B    Settings_Page in Group ONLY (Group's primary).
 *   - Test_Discovery_Page_C    Settings_Page in BOTH registration_classes AND Group's $pages.
 *   - Test_Discovery_Plain_Page Plain Menu_Page in Group only.
 *
 * Asserts each renders with the expected content (no "Settings not initialised"
 * fallback for any Settings_Page) and that Page_C — the duplicate — appears in
 * the WP admin sidebar exactly once.
 */

const { test, expect } = require( '../../fixtures' );

const PAGE_A_URL     = '/wp-admin/admin.php?page=test_discovery_page_a';
const PAGE_B_URL     = '/wp-admin/admin.php?page=test_discovery_page_b';
const PAGE_C_URL     = '/wp-admin/admin.php?page=test_discovery_page_c';
const PLAIN_PAGE_URL = '/wp-admin/admin.php?page=test_discovery_plain_page';

const NOT_INITIALISED = 'Settings not initialised';

test.describe( 'Group / single-page discovery (issue #58 + GROUPS_PROCESSED)', () => {

	test( 'Page A (registration_classes only) renders form, no fallback', async ( { page } ) => {
		await page.goto( PAGE_A_URL );

		await expect(
			page.getByRole( 'heading', { name: 'Discovery Page A' } )
		).toBeVisible();
		await expect(
			page.locator( 'form#form-test_discovery_page_a' )
		).toBeVisible();
		await expect( page.locator( 'body' ) ).not.toContainText( NOT_INITIALISED );
	} );

	test( 'Page B (Group-only — the GROUPS_PROCESSED listener path) renders form, no fallback', async ( { page } ) => {
		await page.goto( PAGE_B_URL );

		await expect(
			page.getByRole( 'heading', { name: 'Discovery Page B' } )
		).toBeVisible();
		await expect(
			page.locator( 'form#form-test_discovery_page_b' )
		).toBeVisible();
		// The bug-fix invariant: a Settings_Page declared only inside a Group
		// must NOT render the "Settings not initialised." fallback.
		await expect( page.locator( 'body' ) ).not.toContainText( NOT_INITIALISED );
	} );

	test( 'Page C (registration_classes AND Group — duplicate scenario) renders form, no fallback', async ( { page } ) => {
		await page.goto( PAGE_C_URL );

		await expect(
			page.getByRole( 'heading', { name: 'Discovery Page C' } )
		).toBeVisible();
		await expect(
			page.locator( 'form#form-test_discovery_page_c' )
		).toBeVisible();
		await expect( page.locator( 'body' ) ).not.toContainText( NOT_INITIALISED );
	} );

	test( 'Page C slug appears in the admin sidebar exactly once (issue #58 dedupe)', async ( { page } ) => {
		await page.goto( PAGE_B_URL );

		// adminmenu links each carry an `href` containing `?page=<slug>`.
		const links = page.locator(
			'#adminmenu a[href*="page=test_discovery_page_c"]'
		);
		await expect( links ).toHaveCount( 1 );
	} );

	test( 'Plain Menu_Page in Group renders its own template — listener does NOT apply Settings_Page DI rules to it', async ( { page } ) => {
		await page.goto( PLAIN_PAGE_URL );

		await expect(
			page.getByRole( 'heading', { name: 'Discovery Plain Page' } )
		).toBeVisible();
		await expect(
			page.locator( '#discovery-plain-marker' )
		).toHaveText( 'PLAIN_PAGE_MARKER_OK' );
		// Plain Menu_Page never had set_settings wired — assert no leakage.
		await expect( page.locator( 'body' ) ).not.toContainText( NOT_INITIALISED );
	} );

	test( 'No Settings_Page in this scenario set rendered the fallback (sweep)', async ( { page } ) => {
		// Sanity sweep over every discovery URL to catch any regression that
		// trips only on a particular path.
		for ( const url of [ PAGE_A_URL, PAGE_B_URL, PAGE_C_URL, PLAIN_PAGE_URL ] ) {
			await page.goto( url );
			await expect(
				page.locator( 'body' ),
				`URL ${ url } leaked the uninitialised-settings fallback.`
			).not.toContainText( NOT_INITIALISED );
		}
	} );
} );
