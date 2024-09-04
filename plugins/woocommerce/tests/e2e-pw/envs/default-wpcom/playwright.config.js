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
			grepInvert: /@skip-on-default-wpcom/,
		},
	],
};

module.exports = config;
