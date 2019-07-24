/** @format */

module.exports = {
	launch: {
		slowMo: false,
		headless: true,
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
	}
};
