/** @format */
/**
 * For a detailed explanation of configuration properties, visit:
 * https://github.com/GoogleChrome/puppeteer/blob/master/docs/api.md#puppeteerlaunchoptions
 */

const { CI, E2E_DEBUG, PUPPETEER_SLOWMO, E2E_EXE_PATH } = process.env;
let executablePath = '';
let dumpio = false;
let puppeteerConfig;


if ( ! CI && E2E_EXE_PATH !== '' ) {
	executablePath = E2E_EXE_PATH;
}

if ( E2E_DEBUG ) {
	dumpio = true;
}
const jestPuppeteerLaunch = {
	// Required for the logged out and logged in tests so they don't share app state/token.
	browserContext: 'incognito',
	defaultViewport: {
		width: 1280,
		height: 800,
	},
};

if ( 'no' == global.process.env.node_config_dev ) {
	puppeteerConfig = {
		launch: jestPuppeteerLaunch,
	};
} else {
	puppeteerConfig = {
		launch: {
			...jestPuppeteerLaunch,
			executablePath,
			dumpio,
			slowMo: PUPPETEER_SLOWMO ? PUPPETEER_SLOWMO : 50,
			headless: false,
			ignoreHTTPSErrors: true,
			args: [ '--window-size=1920,1080', '--user-agent=chrome' ],
			devtools: true,
		},
	};
}

module.exports = puppeteerConfig;
