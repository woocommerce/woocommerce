/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const { StoreOwnerFlow } = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

const runActivationTest = () => {
	describe('Store owner can login and make sure WooCommerce is activated', () => {
		beforeAll(async () => {
			await StoreOwnerFlow.login();
		});

		it('can make sure WooCommerce is activated. If not, activate it', async () => {
			const slug = 'woocommerce';
			await StoreOwnerFlow.openPlugins();
			const disableLink = await page.$(`tr[data-slug="${slug}"] .deactivate a`);
			if (disableLink) {
				return;
			}
			await page.click(`tr[data-slug="${slug}"] .activate a`);
			await page.waitForSelector(`tr[data-slug="${slug}"] .deactivate a`);
		});

	});
};

module.exports = runActivationTest;
