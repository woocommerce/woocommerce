/**
 * Internal dependencies
 */
const {
	merchant,
	createSimpleOrder,
	withRestApi,
	utils,
} = require( '@woocommerce/e2e-utils' );

let orderId;

const runEditOrderTest = () => {
	describe('WooCommerce Orders > Edit order', () => {
		beforeAll(async () => {
			await merchant.login();
			orderId = await createSimpleOrder('Processing');
		});

		afterAll( async () => {
			await withRestApi.deleteAllOrders();
		});

		it('can view single order', async () => {
			// Go to "orders" page
			await merchant.openAllOrdersView();

			// Make sure we're on the orders page
			await expect(page.title()).resolves.toMatch('Orders');

			//Open order we created
			await merchant.goToOrder(orderId);

			// Make sure we're on the order details page
			await expect(page.title()).resolves.toMatch('Edit order');
        });

        it('can update order status', async () => {
			//Open order we created
			await merchant.goToOrder(orderId);

			// Make sure we're still on the order details page
			await expect(page.title()).resolves.toMatch('Edit order');

			// Update order status to `Completed`
			await merchant.updateOrderStatus(orderId, 'Completed');

			// Verify order status changed note added
			await expect( page ).toMatchElement( '#select2-order_status-container', { text: 'Completed' } );
			await expect( page ).toMatchElement(
				'#woocommerce-order-notes .note_content',
				{
					text: 'Order status changed from Processing to Completed.',
				}
			);
        });

        it('can update order details', async () => {
			//Open order we created
			await merchant.goToOrder(orderId);

			// Make sure we're still on the order details page
			await expect(page.title()).resolves.toMatch('Edit order');

			// Update order details
			await expect(page).toFill('input[name=order_date]', '2018-12-14');

			// Wait for auto save
			await utils.waitForTimeout( 2000 );

			// Save the order changes
			await expect( page ).toClick( 'button.save_order' );
			await page.waitForSelector( '#message' );

			// Verify
			await expect( page ).toMatchElement( '#message', { text: 'Order updated.' } );
			await expect( page ).toMatchElement( 'input[name=order_date]', { value: '2018-12-14' } );
		});
	});
}

module.exports = runEditOrderTest;
