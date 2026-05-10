const { defineConfig } = require( '@playwright/test' );
const path = require( 'path' );

const STORAGE_STATE_PATH = path.join(
	__dirname,
	'../../artifacts/storage-states/admin.json'
);

// Must live on each project's `use` (not just top-level) — the
// `requestUtils` worker fixture from @wordpress/e2e-test-utils-playwright
// reads `workerInfo.project.use.baseURL`, which is the raw per-project use
// block, NOT the merged config. Without this it falls back to its built-in
// localhost:8889 default and the global setup fails.
const BASE_URL = process.env.WP_BASE_URL || 'http://localhost:57894';

module.exports = defineConfig( {
	reporter: process.env.CI ? [ [ 'github' ] ] : [ [ 'list' ] ],
	forbidOnly: !! process.env.CI,
	fullyParallel: false,
	workers: 1,
	retries: process.env.CI ? 2 : 0,
	testDir: './specs',
	outputDir: '../test-results',
	snapshotPathTemplate:
		'{testDir}/{testFileDir}/__snapshots__/{testFileName}/{arg}{ext}',

	use: {
		baseURL: BASE_URL,
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure',
	},

	projects: [
		{
			name: 'setup',
			testDir: __dirname,
			testMatch: /global-setup\.js/,
			teardown: undefined,
			use: {
				baseURL: BASE_URL,
				storageState: STORAGE_STATE_PATH,
			},
		},
		{
			name: 'chromium-desktop',
			use: {
				baseURL: BASE_URL,
				browserName: 'chromium',
				viewport: { width: 1280, height: 800 },
				storageState: STORAGE_STATE_PATH,
			},
			dependencies: [ 'setup' ],
		},
	],

	globalSetup: undefined,
} );
