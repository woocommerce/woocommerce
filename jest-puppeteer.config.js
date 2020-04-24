const { jestPuppeteerConfig } = require( 'puppeteer-utils' );

// When debugging the E2E tests, we need to be able to watch them take place.
if ( process.env.WOOCOMMERCE_E2E_DEBUG ) {
	jestPuppeteerConfig.launch.headless = false;
	jestPuppeteerConfig.launch.devtools = true;
	jestPuppeteerConfig.launch.slowMo = 50;
}

module.exports = jestPuppeteerConfig;
