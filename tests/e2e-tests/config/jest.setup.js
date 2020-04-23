/** format */

//Set the default test timeout to 60s
let jestTimeoutInMilliSeconds = 60000;

// When running test in the Development mode, the test timeout is increased to 2 minutes which allows for errors to be inspected.
// Use `await jestPuppeteer.debug()` in test code to pause execution.
if ( process.env.JEST_PUPPETEER_CONFIG === 'tests/e2e-tests/config/jest-puppeteer.dev.config.js' ) {
	jestTimeoutInMilliSeconds = 120000;
}

jest.setTimeout( jestTimeoutInMilliSeconds );
