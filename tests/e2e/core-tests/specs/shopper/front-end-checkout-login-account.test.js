/**
 * Internal dependencies
 */
 const {
	shopper,
	merchant,
	createSimpleProduct,
	uiUnblocked,
	setCheckbox,
	settingsPageSaveChanges,
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

const config = require('config');

const runCheckoutLoginAccountTest = () => {
	describe('Shopper Checkout Login Account', () => {
		let productId;

		beforeAll(async () => {
			productId = await createSimpleProduct();

			// Set checkbox for logging to account during checkout
			await merchant.login();
			await merchant.openSettings('account');
			await setCheckbox('#woocommerce_enable_checkout_login_reminder');
			await settingsPageSaveChanges();
			await merchant.logout();

			// Add simple product to cart and proceed to checkout
			await shopper.goToShop();
			await shopper.addToCartFromShopPage( productId );
			await uiUnblocked();
			await shopper.goToCheckout();
		});

		it('can login to an existing account during checkout', async () => {
			// Click to login during checkout
			await page.waitForSelector('.woocommerce-form-login-toggle');
			await expect(page).toClick('.woocommerce-info > a.showlogin');

			// Fill shopper's login credentials and proceed further
			await page.type( '#username', config.get('users.customer.username') );
			await page.type( '#password', config.get('users.customer.password') );

			await Promise.all([
				page.waitForNavigation({waitUntil: 'networkidle0'}),
				page.click('button[name="login"]'),
			]);

			// Place an order
			await shopper.placeOrder();
			await expect(page).toMatchElement('h1.entry-title', {text: 'Order received'});

			// Verify the email of a logged in user
			await expect(page).toMatchElement('ul > li.email', {text: 'Email: john.doe@example.com'});

			// Verify the user is logged in on my account page
			await shopper.gotoMyAccount();
			await expect(page.url()).toMatch('my-account/');
			await expect(page).toMatchElement('h1', {text: 'My account'});
		});
	});
};

module.exports = runCheckoutLoginAccountTest;
