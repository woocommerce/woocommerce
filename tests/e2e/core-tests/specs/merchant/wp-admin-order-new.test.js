/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	StoreOwnerFlow,
	getValueOfInputField,
	verifyPublish,
	verifyPublishAndTrash
} = require( '@woocommerce/e2e-utils' );

let orderPostIdValue;

const runCreateOrderTest = () => {
	describe('Order Page', () => {
		beforeAll(async () => {
			await StoreOwnerFlow.login();
		});

		it('can create new order', async () => {
			// Go to "add order" page
			await StoreOwnerFlow.openNewOrder();

			// Make sure we're on the add order page
			await expect(page.title()).resolves.toMatch('Add new order');

			// Get order post ID
			orderPostIdValue = await getValueOfInputField(`#post_ID`);

			// Set order data
			await expect(page).toSelect('#order_status', 'Processing');
			await expect(page).toFill('input[name=order_date]', '2018-12-13');
			await expect(page).toFill('input[name=order_date_hour]', '18');
			await expect(page).toFill('input[name=order_date_minute]', '55');

			// Create order, verify that it was created.
			await verifyPublish(
				'.order_actions li .save_order',
				'#message',
				'Order updated.'
			);

			// Verify order status changed
			await expect( page ).toMatchElement( '#select2-order_status-container', { text: 'Processing' } );
			await expect( page ).toMatchElement(
				'#woocommerce-order-notes .note_content',
				{
					text: 'Order status changed from Pending payment to Processing.',
				}
			);
		});

		it('can view single order', async () => {
			// Go to "orders" page
			await StoreOwnerFlow.openAllOrdersView();

			// Make sure we're on the orders page
			await expect(page.title()).resolves.toMatch('Orders');

			//Open order we created
			await StoreOwnerFlow.openOrder(orderPostIdValue);

			// Make sure we're on the order details page
			await expect(page.title()).resolves.toMatch('Edit order');
		});

		it('can update order status', async () => {
			//Open order we created
			await StoreOwnerFlow.openOrder(orderPostIdValue);

			// Make sure we're on the order details page
			await expect(page.title()).resolves.toMatch('Edit order');

			// Set order status
			await expect(page).toSelect('#order_status', 'Completed');

			// Update order, verify that it was updated.
			await verifyPublish(
				'.order_actions li .save_order',
				'#message',
				'Order updated.',
				'Completed'
			);

			// Verify order status changed
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
			await StoreOwnerFlow.openOrder(orderPostIdValue);

			// Make sure we're on the order details page
			await expect(page.title()).resolves.toMatch('Edit order');

			// Update order details
			await expect(page).toFill('input[name=order_date]', '2018-12-14');

			// Update order, verify that it was updated. Trash order, verify that it was trashed.
			await verifyPublishAndTrash(
				'.order_actions li .save_order',
				'#message',
				'Order updated.',
				'1 order moved to the Trash.'
			);
		});
	});
}

module.exports = runCreateOrderTest;
