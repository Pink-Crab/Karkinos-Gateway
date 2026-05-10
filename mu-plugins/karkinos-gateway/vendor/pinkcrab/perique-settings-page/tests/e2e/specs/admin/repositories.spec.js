/**
 * Repository variants e2e.
 *
 * Three describe blocks exercising each repository implementation:
 *   1. WP_Options_Individual_Repository  — each field its own wp_option
 *   2. WP_Options_Named_Groups_Repository — fields split across named groups
 *   3. WP_Site_Options_Decorator          — grouped repo wrapped in site_option
 *
 * Each block: render with seeded defaults, fill + submit, assert
 * persisted values via the JSON dump (which reads the raw options).
 */

const { test, expect } = require( '../../fixtures' );

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

const readDump = async ( page, selector ) => {
	const raw = ( await page.locator( selector ).textContent() ) || '{}';
	return JSON.parse( raw );
};

// ─────────────────────────────────────────────────────────────────────
// 1. Individual Repository
// ─────────────────────────────────────────────────────────────────────
test.describe( 'Repository: Individual', () => {
	const PAGE_URL  = '/wp-admin/admin.php?page=repo-individual';
	const RESET_URL = `${ PAGE_URL }&repo_individual_reset=1`;
	const DUMP      = '#repo-individual-stored';

	test.beforeEach( async ( { page } ) => {
		await page.goto( RESET_URL );
	} );

	test( 'page loads and renders seeded values', async ( { page } ) => {
		await expect(
			page.getByRole( 'heading', { name: 'Repository: Individual' } )
		).toBeVisible();

		await expect(
			page.locator( 'input[name="site_name"]' )
		).toHaveValue( 'Individual Site' );
		await expect(
			page.locator( 'input[name="tag_line"]' )
		).toHaveValue( 'Individual tagline' );
		await expect(
			page.locator( 'input[name="max_posts"]' )
		).toHaveValue( '25' );
	} );

	test( 'filling and submitting persists each field as an individual option', async ( {
		page,
	} ) => {
		await page.fill( 'input[name="site_name"]', 'Updated Individual' );
		await page.fill( 'input[name="tag_line"]', 'New tagline' );
		await page.fill( 'input[name="max_posts"]', '50' );

		await submitForm( page );

		const stored = await readDump( page, DUMP );
		expect( stored[ 'ind_site_name' ] ).toBe( 'Updated Individual' );
		expect( stored[ 'ind_tag_line' ] ).toBe( 'New tagline' );
		expect( stored[ 'ind_max_posts' ] ).toBe( 50 );
	} );
} );

// ─────────────────────────────────────────────────────────────────────
// 2. Named Groups Repository
// ─────────────────────────────────────────────────────────────────────
test.describe( 'Repository: Named Groups', () => {
	const PAGE_URL  = '/wp-admin/admin.php?page=repo-named-groups';
	const RESET_URL = `${ PAGE_URL }&repo_named_groups_reset=1`;
	const DUMP      = '#repo-named-groups-stored';

	test.beforeEach( async ( { page } ) => {
		await page.goto( RESET_URL );
	} );

	test( 'page loads and renders seeded values', async ( { page } ) => {
		await expect(
			page.getByRole( 'heading', { name: 'Repository: Named Groups' } )
		).toBeVisible();

		await expect(
			page.locator( 'input[name="site_name"]' )
		).toHaveValue( 'Named Groups Site' );
		await expect(
			page.locator( 'input[name="tag_line"]' )
		).toHaveValue( 'Named Groups tagline' );
		await expect(
			page.locator( 'input[name="max_posts"]' )
		).toHaveValue( '15' );
		await expect(
			page.locator( 'input[type="checkbox"][name="show_sidebar"]' )
		).toBeChecked();
	} );

	test( 'filling and submitting persists fields into their named groups', async ( {
		page,
	} ) => {
		await page.fill( 'input[name="site_name"]', 'Updated NG Site' );
		await page.fill( 'input[name="tag_line"]', 'Updated NG tagline' );
		await page.fill( 'input[name="max_posts"]', '20' );
		await page
			.locator( 'input[type="checkbox"][name="show_sidebar"]' )
			.uncheck();

		await submitForm( page );

		const stored = await readDump( page, DUMP );

		// General group.
		expect( stored.ng_general ).toMatchObject( {
			ng_site_name: 'Updated NG Site',
			ng_tag_line: 'Updated NG tagline',
		} );

		// Display group.
		expect( stored.ng_display.ng_max_posts ).toBe( 20 );
		// Unchecked checkbox → empty string.
		expect( stored.ng_display.ng_show_sidebar ).toBe( '' );
	} );
} );

// ─────────────────────────────────────────────────────────────────────
// 3. Site Options Decorator
// ─────────────────────────────────────────────────────────────────────
test.describe( 'Repository: Site Options Decorator', () => {
	const PAGE_URL  = '/wp-admin/admin.php?page=repo-site-options';
	const RESET_URL = `${ PAGE_URL }&repo_site_options_reset=1`;
	const DUMP      = '#repo-site-options-stored';

	test.beforeEach( async ( { page } ) => {
		await page.goto( RESET_URL );
	} );

	test( 'page loads and renders seeded values', async ( { page } ) => {
		await expect(
			page.getByRole( 'heading', { name: 'Repository: Site Options' } )
		).toBeVisible();

		await expect(
			page.locator( 'input[name="site_name"]' )
		).toHaveValue( 'Site Options Site' );
		await expect(
			page.locator( 'input[name="tag_line"]' )
		).toHaveValue( 'Site Options tagline' );
		await expect(
			page.locator( 'input[name="max_posts"]' )
		).toHaveValue( '30' );
	} );

	test( 'filling and submitting persists via site_option (grouped)', async ( {
		page,
	} ) => {
		await page.fill( 'input[name="site_name"]', 'Updated Site Opt' );
		await page.fill( 'input[name="tag_line"]', 'Updated SO tagline' );
		await page.fill( 'input[name="max_posts"]', '40' );

		await submitForm( page );

		const stored = await readDump( page, DUMP );

		// Grouped repo: all fields under one key.
		expect( stored ).toMatchObject( {
			site_name: 'Updated Site Opt',
			tag_line: 'Updated SO tagline',
			max_posts: 40,
		} );
	} );
} );
