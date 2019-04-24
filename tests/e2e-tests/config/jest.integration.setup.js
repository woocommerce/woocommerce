/** @format */

const path = require( 'path' );
const mkdirp = require( 'mkdirp' );

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

/**
 * Allow test cases to operate in a fresh context (separate browser session).
 */
jestPuppeteer.resetContext = async () => {
	if ( global.context && global.context.isIncognito() ) {
		global.context = await global.browser.createIncognitoBrowserContext();

		await jestPuppeteer.resetPage();
	}
};

/**
 * Override the test case method so we can take screenshots of assertion failures.
 *
 * See: https://github.com/smooth-code/jest-puppeteer/issues/131#issuecomment-469439666
 */
let currentBlock;

global.describe = ( name, func ) => {
	currentBlock = name;

	try {
		func();
	} catch ( e ) {
		throw e;
	}
};

global.it = async ( name, func ) => {
	return await test( name, async () => {
		try {
			await func();
		} catch ( e ) {
			const savePath = 'screenshots';
			const fileName = `failed-${ currentBlock }-${ name }.png`;
			mkdirp.sync( savePath );

			await page.screenshot( {
				path: path.join(
					savePath,
					fileName.replace( /[^a-z0-9.-]+/gi, '-' )
				),
				fullPage: true,
			} );

			throw e;
		}
	} );
};
