let config = require( '../../playwright.config.js' );
const { devices } = require( '@playwright/test' );

config = {
	...config,
	projects: [
		{
			name: 'default wpcom',
			use: { ...devices[ 'Desktop Chrome' ] },
			testMatch: [
				'**/basic.spec.js',
				'**/activate-and-setup/**/*.spec.js',
				'**/admin-analytics/**/*.spec.js',
				'**/admin-marketing/**/*.spec.js',
				'**/admin-tasks/**/*.spec.js',
				'**/shopper/**/*.spec.js',
				'**/api-tests/**/*.test.js',
				'**/merchant/products/add-variable-product/**/*.spec.js',
				'**/merchant/command-palette.spec.js',
				'**/merchant/create-cart-block.spec.js',
				'**/merchant/create-checkout-block.spec.js',
				'**/merchant/create-coupon.spec.js',
				'**/merchant/create-order.spec.js',
				'**/merchant/create-page.spec.js',
				'**/merchant/create-post.spec.js',
			],
			grepInvert: /@skip-on-default-wpcom/,
		},
	],
};

module.exports = config;
