/** @format */
const { jestPuppeteerConfig } = require( '@automattic/puppeteer-utils' );

let puppeteerConfig;

if ( 'no' == global.process.env.node_config_dev ) {
	puppeteerConfig = jestPuppeteerConfig;
} else {
	puppeteerConfig = {
		launch: {
			...jestPuppeteerConfig.launch,
			ignoreHTTPSErrors: true,
			args: [ '--window-size=1920,1080', '--user-agent=chrome' ],
			devtools: true,
			defaultViewport: {
				width: 1280,
				height: 800,
			}
		},
	};
}

module.exports = puppeteerConfig;
