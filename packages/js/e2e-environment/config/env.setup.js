const { getTestConfig } = require( '../utils' );
const testConfig = getTestConfig();

global.process.env = {
	...global.process.env,
	// Remove the trailing slash from jest sequencer WORDPRESS_URL.
	WP_BASE_URL: testConfig.baseUrl,
	PUPPETEER_SLOWMO: true,
};
