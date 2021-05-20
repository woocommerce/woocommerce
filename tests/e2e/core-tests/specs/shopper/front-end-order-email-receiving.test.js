/* eslint-disable jest/no-export, jest/no-disabled-tests, jest/expect-expect */
/**
 * Internal dependencies
 */
 const {
	shopper,
	merchant,
	createSimpleProduct,
	uiUnblocked,
	deleteAllEmailLogs,
} = require( '@woocommerce/e2e-utils' );

let simplePostIdValue;
let orderId;
const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );
const customerEmail = config.get( 'addresses.customer.billing.email' );
const storeName = 'WooCommerce Core E2E Test Suite';

/**
 * External dependencies
 */
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

const runOrderEmailReceivingTest = () => {
	describe('Shopper Order Email Receiving', () => {
		beforeAll(async () => {
			simplePostIdValue = await createSimpleProduct();
			
			await merchant.login();
			await deleteAllEmailLogs();
			await merchant.logout();
		});

		it('should receive order email after purchasing an item', async () => {
			await shopper.login();

			// Go to the shop and purchase an item
			await shopper.goToProduct(simplePostIdValue);
			await shopper.addToCart(simpleProductName);
			await shopper.goToCheckout();
			await uiUnblocked();
			await shopper.placeOrder();
			// Get order ID from the order received html element on the page
			orderId = await page.$$eval(".woocommerce-order-overview__order strong", elements => elements.map(item => item.textContent));

			// Verify the new order email has been received
			await merchant.login();
			await merchant.openEmailLog();
			await expect( page ).toMatchElement( '.column-receiver', { text: customerEmail } );
			await expect( page ).toMatchElement( '.column-subject', { text: `[${storeName}]: New order #${orderId}` } );
		});
	});
};

module.exports = runOrderEmailReceivingTest;
