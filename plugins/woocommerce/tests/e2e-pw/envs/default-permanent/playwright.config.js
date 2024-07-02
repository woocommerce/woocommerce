let config = require( '../../playwright.config.js' );
const { devices } = require( '@playwright/test' );

process.env.BASE_URL = process.env.E2E_DEFAULT_PERMANENT_URL;

config = {
	...config,
	projects: [
		{
			name: 'default',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
};

module.exports = config;
