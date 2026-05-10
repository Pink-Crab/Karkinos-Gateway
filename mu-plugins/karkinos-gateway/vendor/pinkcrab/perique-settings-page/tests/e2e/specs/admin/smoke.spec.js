const { test, expect } = require( '../../fixtures' );

test.describe( 'Settings Page - Smoke Test', () => {
	test( 'WordPress admin dashboard loads', async ( { page } ) => {
		await page.goto( '/wp-admin/' );
		await expect( page.locator( '#wpbody-content' ) ).toBeVisible();
	} );
} );
