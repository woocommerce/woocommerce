const assert = require( 'node:assert/strict' );
const test = require( 'node:test' );
const playwrightDefaults = require( '@woocommerce/extension-tooling/playwright-defaults' );

test( 'uses local defaults and writes only the configured HTML report', () => {
	assert.deepEqual(
		playwrightDefaults( {
			htmlReport: './test-results/playwright-report',
			jsonReport: './test-results/results.json',
			env: { WP_ENV_TESTS_PORT: '8897' },
		} ),
		{
			timeout: 60_000,
			expect: { timeout: 10_000 },
			forbidOnly: false,
			retries: 0,
			reporter: [
				[ 'list' ],
				[
					'html',
					{
						outputFolder: './test-results/playwright-report',
						open: 'never',
					},
				],
			],
			use: {
				baseURL: 'http://localhost:8897',
				trace: 'retain-on-failure',
				screenshot: 'only-on-failure',
			},
		}
	);
} );

test( 'keeps an explicit URL and enables the selected CI reports', () => {
	const options = {
		htmlReport: '/tmp/e2e results/html',
		jsonReport: '/tmp/e2e results/results.json',
		env: {
			CI: 'true',
			GITHUB_ACTIONS: 'true',
			WP_BASE_URL: 'http://example.test:8894/subdirectory/',
			WP_ENV_TESTS_PORT: '8897',
		},
	};
	const config = playwrightDefaults( options );
	assert.equal( config.forbidOnly, true );
	assert.equal( config.retries, 1 );
	assert.equal( config.use.baseURL, options.env.WP_BASE_URL );
	assert.deepEqual( config.reporter, [
		[ 'list' ],
		[ 'github' ],
		[ 'html', { outputFolder: options.htmlReport, open: 'never' } ],
		[ 'json', { outputFile: options.jsonReport } ],
	] );
	assert.deepEqual(
		playwrightDefaults( { ...options, jsonReport: undefined } ).reporter,
		config.reporter.slice( 0, -1 )
	);
} );

test( 'can enable GitHub annotations independently of CI mode', () => {
	const config = playwrightDefaults( {
		htmlReport: './test-results/playwright-report',
		env: { GITHUB_ACTIONS: 'true' },
	} );
	assert.equal( config.use.baseURL, 'http://localhost:8889' );
	assert.equal( config.retries, 0 );
	assert.deepEqual( config.reporter[ 1 ], [ 'github' ] );
} );
