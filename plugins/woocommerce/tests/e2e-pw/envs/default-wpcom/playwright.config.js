let config = require( '../../playwright.config.js' );
const { devices } = require( '@playwright/test' );

config = {
	...config,
	projects: [
		{
			name: 'default wpcom',
			use: { ...devices[ 'Desktop Chrome' ] },
			testMatch: [ '**/basic.spec.js', '**/shopper/**/*.spec.js' ],
			grepInvert: /@skip-on-default-wpcom/,
		},
	],
};

module.exports = config;
