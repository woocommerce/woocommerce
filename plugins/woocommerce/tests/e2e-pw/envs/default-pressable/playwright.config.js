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
				'**/merchant/command-palette.spec.js',
				'**/merchant/create-cart-block.spec.js',
				'**/merchant/create-checkout-block.spec.js',
				'**/merchant/create-coupon.spec.js',
				'**/merchant/create-order.spec.js',
				'**/shopper/checkout-create-account.spec.js',
				'**/shopper/checkout-login.spec.js',
				'**/shopper/checkout.spec.js',
				'**/shopper/dashboard-access.spec.js',
				'**/shopper/mini-cart.spec.js',
				'**/shopper/my-account-addresses.spec.js',
				'**/shopper/my-account-create-account.spec.js',
				'**/shopper/my-account-downloads.spec.js',
				'**/shopper/my-account-pay-order.spec.js',
				'**/shopper/my-account.spec.js',
				'**/shopper/order-email-receiving.spec.js',
				'**/shopper/product-grouped.spec.js',
				'**/shopper/product-simple.spec.js',
				'**/shopper/product-tags-attributes.spec.js',
				'**/shopper/product-variable.spec.js',
				'**/shopper/shop-search-browse-sort.spec.js',
				'**/shopper/shop-title-after-deletion.spec.js',
			],
			grepInvert: /@skip-on-default-pressable/,
		},
	],
};

module.exports = config;
