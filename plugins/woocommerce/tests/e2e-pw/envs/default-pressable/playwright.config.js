let config = require( '../../playwright.config.js' );
const { devices } = require( '@playwright/test' );

config = {
	...config,
	projects: [
		{
			name: 'default pressable',
			use: { ...devices[ 'Desktop Chrome' ] },
			testMatch: [
				'**/basic.spec.js',
				'**/merchant/products/add-variable-product/**/*.spec.js',
				'**/activate-and-setup/**/*.spec.js',
				'**/merchant/products/block-editor/**/*.spec.js',
				'**/admin-analytics/**/*.spec.js',
				'**/admin-marketing/**/*.spec.js',
				'**/admin-tasks/**/*.spec.js',
				'**/customize-store/**/*.spec.js',
				'**/merchant/create-page.spec.js',
				'**/merchant/create-post.spec.js',
				'**/merchant/create-restricted-coupons.spec.js',
				'**/merchant/create-shipping-classes.spec.js',
				'**/merchant/create-shipping-zones.spec.js',
			],
			grepInvert: /@skip-on-default-pressable/,
		},
	],
};

module.exports = config;
