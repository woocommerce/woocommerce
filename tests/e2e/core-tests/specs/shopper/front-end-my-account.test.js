/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	shopper,
	merchant
} = require( '@woocommerce/e2e-utils' );

const runMyAccountPageTest = () => {
	describe('My account page', () => {
		it('allows customer to login', async () => {
			await merchant.logout();
			await shopper.login();
			await expect(page).toMatch('Hello');
			await expect(page).toMatchElement('.woocommerce-MyAccount-navigation-link', {text: 'Dashboard'});
			await expect(page).toMatchElement('.woocommerce-MyAccount-navigation-link', {text: 'Orders'});
			await expect(page).toMatchElement('.woocommerce-MyAccount-navigation-link', {text: 'Downloads'});
			await expect(page).toMatchElement('.woocommerce-MyAccount-navigation-link', {text: 'Addresses'});
			await expect(page).toMatchElement('.woocommerce-MyAccount-navigation-link', {text: 'Account details'});
			await expect(page).toMatchElement('.woocommerce-MyAccount-navigation-link', {text: 'Logout'});
		});

		it('allows customer to see orders', async () => {
			await shopper.goToOrders();
			await expect(page.url()).toMatch('my-account/orders');
			await expect(page).toMatchElement('h1', {text: 'Orders'});
		});

		it('allows customer to see downloads', async () => {
			await shopper.goToDownloads();
			expect(page.url()).toMatch('my-account/downloads');
			await expect(page).toMatchElement('h1', {text: 'Downloads'});
		});

		it('allows customer to see addresses', async () => {
			await shopper.goToAddresses();
			expect(page.url()).toMatch('my-account/edit-address');
			await expect(page).toMatchElement('h1', {text: 'Addresses'});
		});

		it('allows customer to see account details', async () => {
			await shopper.goToAccountDetails();
			expect(page.url()).toMatch('my-account/edit-account');
			await expect(page).toMatchElement('h1', {text: 'Account details'});
		});
	});
}

module.exports = runMyAccountPageTest;
