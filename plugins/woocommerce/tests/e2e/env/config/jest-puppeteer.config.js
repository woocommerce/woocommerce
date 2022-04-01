/** @format */
const { jestPuppeteerConfig } = require( '@automattic/puppeteer-utils' );

let puppeteerConfig;

if ( 'no' == global.process.env.node_config_dev ) {
	puppeteerConfig = {
		launch: {
			// Required for the logged out and logged in tests so they don't share app state/token.
			browserContext: 'incognito',
		},
	};
} else {
	puppeteerConfig = {
		launch: {
			...jestPuppeteerConfig.launch,
			slowMo: process.env.PUPPETEER_SLOWMO ? process.env.PUPPETEER_SLOWMO : 50,
			headless: false,
			ignoreHTTPSErrors: true,
			args: [ '--window-size=1920,1080', '--user-agent=chrome' ],
			devtools: true,
			defaultViewport: {
				width: 1280,
				height: 800,
			},
		},
	};
}

module.exports = puppeteerConfig;
