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
				'**/merchant/create-restricted-coupons.spec.js',
				'**/merchant/create-shipping-classes.spec.js',
				'**/merchant/create-shipping-zones.spec.js',
				'**/merchant/create-woocommerce-blocks.spec.js',
				'**/merchant/create-woocommerce-patterns.spec.js',
				'**/merchant/customer-list.spec.js',
				'**/merchant/customer-payment-page.spec.js',
				'**/merchant/launch-your-store.spec.js',
				'**/merchant/lost-password.spec.js',
				'**/merchant/order-bulk-edit.spec.js',
			],
			grepInvert: /@skip-on-default-wpcom/,
		},
	],
};

module.exports = config;
