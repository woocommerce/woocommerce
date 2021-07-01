/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	shopper,
} = require( '@woocommerce/e2e-utils' );

const pages = [
	['Orders', 'my-account/orders', shopper.goToOrders],
	['Downloads', 'my-account/downloads', shopper.goToDownloads],
	['Addresses', 'my-account/edit-address', shopper.goToAddresses],
	['Account details', 'my-account/edit-account', shopper.goToAccountDetails]
];

const runMyAccountPageTest = () => {
	describe('My account page', () => {
		it('allows customer to login', async () => {
			await shopper.login();
			expect(page).toMatch('Hello');
			await expect(page).toMatchElement('.woocommerce-MyAccount-navigation-link', { text: 'Dashboard' });
			await expect(page).toMatchElement('.woocommerce-MyAccount-navigation-link', { text: 'Orders' });
			await expect(page).toMatchElement('.woocommerce-MyAccount-navigation-link', { text: 'Downloads' });
			await expect(page).toMatchElement('.woocommerce-MyAccount-navigation-link', { text: 'Addresses' });
			await expect(page).toMatchElement('.woocommerce-MyAccount-navigation-link', { text: 'Account details' });
			await expect(page).toMatchElement('.woocommerce-MyAccount-navigation-link', { text: 'Logout' });
		});

		it.each(pages)('allows customer to see %s page', async (pageTitle, path, goToPage) => {
			await goToPage();
			expect(page.url()).toMatch(path);
			await expect(page).toMatchElement('h1', { text: pageTitle });
		});
	});
}

module.exports = runMyAccountPageTest;
