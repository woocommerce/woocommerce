/** @format */

// We need to increase the default time of 5 seconds because our servers take longer
// to respond.
let jestTimeoutInMilliSeconds = 15000;

// For dev purposes, let's increase the test timeout to 2 minutes so you can
// inspect what happened if an error fails.
// Use `await jestPuppeteer.debug()` in test code to pause execution.
if ( process.env.JEST_PUPPETEER_CONFIG === 'tests/e2e-tests/config/jest-puppeteer.dev.config.js' ) {
	jestTimeoutInMilliSeconds = 120000;
}

jest.setTimeout( jestTimeoutInMilliSeconds );
