const { defineConfig, devices } = require('@playwright/test');
const dotenv = require('dotenv');

dotenv.config({ path: __dirname + '/.env' });

// Use QIT_SITE_URL if available, otherwise fall back to BASE_URL or default
const BASE_URL = process.env.QIT_SITE_URL || process.env.BASE_URL || 'http://localhost:8080';

const { CI, E2E_MAX_FAILURES, REPEAT_EACH } = process.env;

const TESTS_ROOT_PATH = __dirname;
const TESTS_RESULTS_PATH = `${TESTS_ROOT_PATH}/results`;
const STORAGE_DIR_PATH = `${TESTS_ROOT_PATH}/.state/`;
const ADMIN_STATE_PATH = `${STORAGE_DIR_PATH}/admin.json`;
const CUSTOMER_STATE_PATH = `${STORAGE_DIR_PATH}/customer.json`;

const setupProjects = [
	{
		name: 'install wc',
		testDir: `${TESTS_ROOT_PATH}/fixtures`,
		testMatch: 'install-wc.setup.js',
	},
	{
		name: 'global authentication',
		testDir: `${TESTS_ROOT_PATH}/fixtures`,
		testMatch: 'auth.setup.js',
		dependencies: ['install wc'],
	},
	{
		name: 'site setup',
		testDir: `${TESTS_ROOT_PATH}/fixtures`,
		testMatch: 'site.setup.js',
		dependencies: ['global authentication'],
	},
];

module.exports = defineConfig({
	timeout: 120 * 1000,
	expect: { timeout: 20 * 1000 },
	outputDir: TESTS_RESULTS_PATH,
	testDir: `${TESTS_ROOT_PATH}/tests`,
	retries: CI ? 1 : 0,
	repeatEach: REPEAT_EACH ? Number(REPEAT_EACH) : 1,
	workers: process.env.WORKERS ? Number(process.env.WORKERS) : (CI ? 1 : 1),
	fullyParallel: true,
	reportSlowTests: { max: 5, threshold: 30 * 1000 },
	reporter: [
		['list'],
		['playwright-ctrf-json-reporter', {
			outputDir: './results',
			outputFile: 'ctrf.json',
		}],
		['allure-playwright', {
			resultsDir: './results/allure',
		}],
		['blob', {
			outputDir: './results/blob',
		}],
		['html', {
			outputFolder: `${TESTS_ROOT_PATH}/playwright-report`,
			open: 'never'
		}]
	],
	maxFailures: E2E_MAX_FAILURES ? Number(E2E_MAX_FAILURES) : 0,
	forbidOnly: !!CI,
	use: {
		baseURL: BASE_URL.endsWith('/') ? BASE_URL : BASE_URL + '/',
		screenshot: { mode: 'only-on-failure', fullPage: true },
		trace: 'retain-on-first-failure',
		video: 'retain-on-failure',
		actionTimeout: 20 * 1000,
		navigationTimeout: 20 * 1000,
		timezoneId: 'UTC',
		contextOptions: {
			reducedMotion: 'reduce',
		},
		...devices['Desktop Chrome'],
	},
	projects: [
		...setupProjects,
		{
			name: 'e2e',
			testIgnore: [
				'**/api-tests/**',
				'**/tests/customize-store/**/*.spec.js' // QIT-SKIP: Customize Store tests fail due to iframe loading issues
			],
			dependencies: ['site setup'],
		},
		{
			name: 'api',
			testMatch: '**/api-tests/**',
			dependencies: ['site setup'],
		},
	],
});

// Export these for use in tests
module.exports.TESTS_ROOT_PATH = TESTS_ROOT_PATH;
module.exports.TESTS_RESULTS_PATH = TESTS_RESULTS_PATH;
module.exports.STORAGE_DIR_PATH = STORAGE_DIR_PATH;
module.exports.ADMIN_STATE_PATH = ADMIN_STATE_PATH;
module.exports.CUSTOMER_STATE_PATH = CUSTOMER_STATE_PATH;
