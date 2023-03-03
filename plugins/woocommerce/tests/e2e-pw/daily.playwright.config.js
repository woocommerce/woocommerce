const defaultConfig = require( './playwright.config' );

const config = {
	...defaultConfig,
	testIgnore: '**/shopper/**',
};

module.exports = config;
