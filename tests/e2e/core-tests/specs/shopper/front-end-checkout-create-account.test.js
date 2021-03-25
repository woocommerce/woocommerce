/* eslint-disable jest/no-export, jest/no-disabled-tests, jest/expect-expect */
/**
 * Internal dependencies
 */
 const {
	shopper,
	merchant,
	createSimpleProduct,
    uiUnblocked,
    setCheckbox,
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
const simpleProductName = config.get( 'products.simple.name' );

const runCheckoutCreateAccountTest = () => {
	describe('Shopper Checkout Create Account', () => {
		beforeAll(async () => {
			await merchant.login();
			await createSimpleProduct();
			await merchant.logout();
            await shopper.goToShop();
			await shopper.addToCartFromShopPage(simpleProductName);
			await uiUnblocked();
			await shopper.goToCheckout();
		});

		it('can create an account during checkout', async () => {
			// Fill all the details for a new customer
			await shopper.fillBillingDetails(config.get('addresses.customer.billing'));
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
            await expect(page).toMatchElement('td.email.column-email > a', {text: 'john.doe@example.com'});
        });
	});
};

module.exports = runCheckoutCreateAccountTest;
