const config = require( './jest-puppeteer.config' );

module.exports = {
	...config,
	launch: {
		...config.launch,
		headless: false,
	},
};
