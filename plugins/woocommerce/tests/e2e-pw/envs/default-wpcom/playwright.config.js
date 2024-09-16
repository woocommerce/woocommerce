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
				'**/merchant/order-coupon.spec.js',
				'**/merchant/order-edit.spec.js',
				'**/merchant/order-emails.spec.js',
				'**/merchant/order-refund.spec.js',
				'**/merchant/order-search.spec.js',
				'**/merchant/order-status-filter.spec.js',
				'**/merchant/page-loads.spec.js',
				'**/merchant/product-create-simple.spec.js',
				'**/merchant/product-delete.spec.js',
				'**/merchant/product-edit.spec.js',
			],
			grepInvert: /@skip-on-default-wpcom/,
		},
	],
};

module.exports = config;
