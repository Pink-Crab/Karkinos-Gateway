/**
 * Takes full-page screenshots of every theme showcase.
 *
 * Output: tests/e2e/theme-screenshots/<theme>.png
 */

const { test } = require( '../../fixtures' );
const path = require( 'path' );

const THEMES = [
	[ 'vanilla', 'theme-showcase-vanilla' ],
	[ 'material', 'theme-showcase-material' ],
	[ 'bootstrap', 'theme-showcase-bootstrap' ],
	[ 'bootstrap-classic', 'theme-showcase-bootstrap-classic' ],
	[ 'wp-admin', 'theme-showcase-wp-admin' ],
	[ 'minimal', 'theme-showcase-minimal' ],
];

for ( const [ name, slug ] of THEMES ) {
	test( `Screenshot: ${ name }`, async ( { page } ) => {
		await page.goto( `/wp-admin/admin.php?page=${ slug }` );
		await page.waitForLoadState( 'networkidle' );
		// Hide the WP admin menu/toolbar so the form fills the shot.
		await page.addStyleTag( {
			content: `
				#adminmenumain, #adminmenuback, #adminmenuwrap,
				#wpadminbar, #wpfooter { display: none !important; }
				#wpcontent, #wpbody-content { margin-left: 0 !important; padding-top: 0 !important; }
				html.wp-toolbar { padding-top: 0 !important; }
			`,
		} );
		await page.screenshot( {
			path: path.join(
				__dirname,
				'../../../../docs/screenshots',
				`${ name }.png`
			),
			fullPage: true,
		} );
	} );
}
