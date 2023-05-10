const defaultConfig = require( './playwright.config' );
const defaultUse = defaultConfig.use;

const config = {
	...defaultConfig,
	testIgnore: '**/smoke-tests/**',
	use: {
		...defaultUse,
		video: 'retain-on-failure',
	},
};

module.exports = config;
