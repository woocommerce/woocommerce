let config = require( '../../playwright.config.js' );
const { devices } = require( '@playwright/test' );

config = {
	...config,
	projects: [
		{
			name: 'default subdomain',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
};

module.exports = config;
