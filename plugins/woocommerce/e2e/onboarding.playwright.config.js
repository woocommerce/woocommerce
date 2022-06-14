const defaultConfig = require( './ci.playwright.config' );

const config = {
	...defaultConfig,
	workers: 1,
	testMatch: 'onboarding.test.list.js',
};

module.exports = config;
