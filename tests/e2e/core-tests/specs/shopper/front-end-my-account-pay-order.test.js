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
let orderNum;
const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );

const runMyAccountPayOrderTest = () => {
	describe('Customer can pay for their order through My Account', () => {
		beforeAll(async () => {
			simplePostIdValue = await createSimpleProduct();
			await shopper.login();
			await shopper.goToProduct(simplePostIdValue);
			await shopper.addToCart(simpleProductName);
			await shopper.goToCheckout();
			await shopper.fillBillingDetails(config.get('addresses.customer.billing'));
			await uiUnblocked();
			await shopper.placeOrder();

			// Get order ID from the order received html element on the page
			orderNum = await page.$$eval(".woocommerce-order-overview__order strong", elements => elements.map(item => item.textContent));

			await merchant.login();
			await merchant.updateOrderStatus(orderNum, 'Pending payment');
			await merchant.logout();
		});

		it('allows customer to pay for their order in My Account', async () => {
			await shopper.login();
			await shopper.goToOrders();
			await expect(page).toClick('a.woocommerce-button.button.pay');
			await page.waitForNavigation({waitUntil: 'networkidle0'});
			await expect(page).toMatchElement('.entry-title', {text: 'Pay for order'});
			await shopper.placeOrder();
			await expect(page).toMatch('Order received');
		});
	});
}

module.exports = runMyAccountPayOrderTest;
