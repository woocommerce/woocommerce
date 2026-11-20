/**
 * Build shared Playwright settings for a WooCommerce extension's browser suite.
 *
 * @param {Object}            options              Reporter paths and environment.
 * @param {string}            options.htmlReport   HTML report directory.
 * @param {string}            [options.jsonReport] JSON report path, if needed in CI.
 * @param {NodeJS.ProcessEnv} [options.env]        Environment, defaults to process.env.
 * @return {Object} Playwright configuration to compose with suite settings.
 */
module.exports = function playwrightDefaults( {
	htmlReport,
	jsonReport,
	env = process.env,
} ) {
	return {
		timeout: 60_000,
		expect: { timeout: 10_000 },
		forbidOnly: !! env.CI,
		retries: env.CI ? 1 : 0,
		reporter: [
			[ 'list' ],
			...( env.GITHUB_ACTIONS ? [ [ 'github' ] ] : [] ),
			[ 'html', { outputFolder: htmlReport, open: 'never' } ],
			...( env.CI && jsonReport
				? [ [ 'json', { outputFile: jsonReport } ] ]
				: [] ),
		],
		use: {
			baseURL:
				env.WP_BASE_URL ||
				`http://localhost:${ env.WP_ENV_TESTS_PORT || 8889 }`,
			trace: 'retain-on-failure',
			screenshot: 'only-on-failure',
		},
	};
};
