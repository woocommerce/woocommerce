const defaultConfig = require( './playwright.config' );

const config = {
	...defaultConfig,
	testIgnore: '**/smoke-tests/**',
};

module.exports = config;
