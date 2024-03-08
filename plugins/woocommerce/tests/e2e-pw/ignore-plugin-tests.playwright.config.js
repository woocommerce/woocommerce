const defaultConfig = require( './playwright.config' );
const defaultUse = defaultConfig.use;

const config = {
	...defaultConfig,
	reportSlowTests: { max: 0, threshold: 30 * 1000 }, // 30 seconds threshold
	testIgnore: '**/smoke-tests/**',
	use: {
		...defaultUse,
		video: 'retain-on-failure',
	},
};

module.exports = config;
