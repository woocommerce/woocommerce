/** @format */

module.exports = {
	launch: {
		slowMo: process.env.PUPPETEER_HEADLESS ? false : 200,
		headless: process.env.PUPPETEER_HEADLESS || false,
		ignoreHTTPSErrors: true,
		args: [
			'--window-size=1920,1080',
			'--user-agent=puppeteer-debug',
		],
		devtools: true,
		defaultViewport: {
			width: 1280,
			height: 800,
		},
		browserContext: 'incognito',
		// This will pipe browser's console to node.
		dumpio: true,
	},
};
