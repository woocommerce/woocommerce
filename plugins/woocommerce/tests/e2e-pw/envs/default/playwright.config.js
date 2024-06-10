let config = require( '../../playwright.config.js' );
const { devices } = require( '@playwright/test' );

process.env.USE_WP_ENV = 'true';

config = {
	...config,
	projects: [
		{
			name: 'default',
			use: { ...devices[ 'Desktop Chrome' ] },
			testMatch: '**/tests/e2e-pw/tests/basic.spec.js',
		},
	],
};

module.exports = config;
