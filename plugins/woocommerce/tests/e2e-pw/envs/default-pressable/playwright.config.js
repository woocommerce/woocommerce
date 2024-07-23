let config = require( '../../playwright.config.js' );
const { devices } = require( '@playwright/test' );

config = {
	...config,
	projects: [
		{
			name: 'default pressable',
			use: { ...devices[ 'Desktop Chrome' ] },
			grep: /@external/,
		},
	],
};

module.exports = config;
