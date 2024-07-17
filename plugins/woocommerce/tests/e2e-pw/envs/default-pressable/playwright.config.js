let config = require( '../../playwright.config.js' );
const { devices } = require( '@playwright/test' );

config = {
	...config,
	projects: [
		{
			name: 'default',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
		{
			name: 'Core API tests',
		},
	],
};

module.exports = config;
