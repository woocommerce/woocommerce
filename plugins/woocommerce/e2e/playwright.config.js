// playwright.config.js
// @ts-check
const { devices } = require('@playwright/test');

/** @type {import('@playwright/test').PlaywrightTestConfig} */
const config = {
	timeout: 60000,
	globalSetup: require.resolve('./global-setup'),
	testDir: 'tests',
	reporter: [['list'], ['html', { outputFolder: 'e2e/output' }]],
	use: {
	  retries: 2,
	  screenshot: 'only-on-failure',
	  video: 'retain-on-failure',
	  trace: 'retain-on-failure',
	  viewport: { width: 1280, height: 720 },
	  baseURL: 'http://localhost:8084'
  },
  projects: [
    {
      name: 'Chrome',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'Firefox',
      use: { ...devices['Desktop Firefox'] },
    },
    {
      name: 'Webkit',
      use: { ...devices['Desktop Safari'] },
    },
  ],
};

module.exports = config;
