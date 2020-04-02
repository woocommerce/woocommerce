/** @format */

module.exports = {
	launch: {
		slowMo: process.env.PUPPETEER_SLOWMO ? false : 50,
		headless: process.env.PUPPETEER_HEADLESS || false,
		ignoreHTTPSErrors: true,
		args: [ '--window-size=1920,1080', '--user-agent=chrome' ],
		devtools: true,
		defaultViewport: {
			width: 1280,
			height: 800,
		},
		// Required for the logged out and logged in tests so they don't share app state/token.
		browserContext: 'incognito',
	},
};
