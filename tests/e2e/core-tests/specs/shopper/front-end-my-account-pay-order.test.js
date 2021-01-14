/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	shopper,
	merchant,
	createSimpleProduct,
	uiUnblocked
} = require( '@woocommerce/e2e-utils' );

let simplePostIdValue;
const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );

const runMyAccountPayOrderTest = () => {
	describe('Customer can pay for his order through my account', () => {
		beforeAll(async () => {
			await merchant.login();
			simplePostIdValue = await createSimpleProduct();
			await merchant.logout();
			await shopper.login();
			await shopper.goToProduct(simplePostIdValue);
			await shopper.addToCartFromShopPage(simpleProductName);
			await shopper.goToCheckout();
			await shopper.fillBillingDetails(config.get('addresses.customer.billing'));
			await uiUnblocked();
			await shopper.placeOrder();
			const orderElement = await page.$(".order");
			const orderId = await page.evaluate(orderElement => orderElement.textContent, orderElement);
			await shopper.logout();
			await merchant.login();
			await merchant.updateOrderStatus(orderId, 'Pending payment');
			await merchant.logout();
		})

		it('allows customer to pay for his order in my account', async () => {
			await shopper.login();
			await shopper.goToOrders();
			await expect(page).toClick('.wc_payment_method woocommerce-button button pay', {text: 'Pay'});
            await expect(page).toMatchElement('.entry-title', {text: 'Pay for order'});
            await shopper.placeOrder();
            await expect(page).toMatch('Order received');
		});
	});
}

module.exports = runMyAccountPayOrderTest;
