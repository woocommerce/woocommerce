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
	addShippingZoneAndMethod,
	withRestApi,
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

const config = require( 'config' );
const customerBilling = config.get( 'addresses.customer.billing' );

const runCheckoutCreateAccountTest = () => {
	describe('Shopper Checkout Create Account', () => {
		let productId;

		beforeAll(async () => {
			productId = await createSimpleProduct();
			await withRestApi.deleteCustomerByEmail( customerBilling.email );

			// Set checkbox for creating an account during checkout
			await merchant.login();
			await merchant.openSettings('account');
			await setCheckbox('#woocommerce_enable_signup_and_login_from_checkout');
			await settingsPageSaveChanges();

			// Set free shipping within California
			await addShippingZoneAndMethod('Free Shipping CA', 'state:US:CA', ' ', 'free_shipping');

			await merchant.logout();

			// Add simple product to cart and proceed to checkout
			await shopper.goToShop();
			await shopper.addToCartFromShopPage( productId );
			await uiUnblocked();
			await shopper.goToCheckout();
		});

		it('can create an account during checkout', async () => {
			// Fill all the details for a new customer
			await shopper.fillBillingDetails( customerBilling );
			await uiUnblocked();

			// Set checkbox for creating account during checkout
			await setCheckbox('#createaccount');

			// Place an order
			await shopper.placeOrder();
			await expect(page).toMatchElement('h1.entry-title', {text: 'Order received'});
		});

		it('can verify that the customer has been created', async () => {
			await merchant.login();
			await merchant.openAllUsersView();
			await expect(page).toMatchElement('td.email.column-email > a', { text: customerBilling.email });
		});
	});
};

module.exports = runCheckoutCreateAccountTest;
