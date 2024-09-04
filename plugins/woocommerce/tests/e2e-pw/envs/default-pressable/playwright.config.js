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
				'**/merchant/command-palette.spec.js',
				'**/merchant/create-cart-block.spec.js',
				'**/merchant/create-checkout-block.spec.js',
				'**/merchant/create-coupon.spec.js',
				'**/merchant/create-order.spec.js',
				'**/merchant/create-woocommerce-blocks.spec.js',
				'**/merchant/create-woocommerce-patterns.spec.js',
				'**/merchant/customer-list.spec.js',
				'**/merchant/customer-payment-page.spec.js',
				'**/merchant/launch-your-store.spec.js',
				'**/merchant/lost-password.spec.js',
				'**/merchant/order-bulk-edit.spec.js',
				'**/merchant/order-coupon.spec.js',
				'**/merchant/order-edit.spec.js',
				'**/merchant/order-emails.spec.js',
				'**/merchant/order-refund.spec.js',
				'**/merchant/order-search.spec.js',
				'**/merchant/order-status-filter.spec.js',
				'**/merchant/page-loads.spec.js',
				'**/merchant/product-create-simple.spec.js',
			],
			grepInvert: /@skip-on-default-pressable/,
		},
	],
};

module.exports = config;
