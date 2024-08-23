let config = require( '../../playwright.config.js' );
const { devices } = require( '@playwright/test' );

config = {
	...config,
	projects: [
		{
			name: 'default pressable',
			use: { ...devices[ 'Desktop Chrome' ] },
			testMatch: [
				'**/basic.spec.js', // Match the specific file
				'**/activate-and-setup/**/*.spec.js', // Match all .spec.js files in the activate-and-setup folder
			],
			grepInvert: /@local/,
		},
	],
};

module.exports = config;
