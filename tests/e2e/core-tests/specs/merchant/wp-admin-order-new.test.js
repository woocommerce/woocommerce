/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	merchant,
	verifyPublishAndTrash
} = require( '@woocommerce/e2e-utils' );

const runCreateOrderTest = () => {
	describe('WooCommerce Orders > Add new order', () => {
		beforeAll(async () => {
			await merchant.login();
		});

		it('can create new order', async () => {
			// Go to "add order" page
			await merchant.openNewOrder();

			// Make sure we're on the add order page
			await expect(page.title()).resolves.toMatch('Add new order');

			// Set order data
			await expect(page).toSelect('#order_status', 'Processing');
			await expect(page).toFill('input[name=order_date]', '2018-12-13');
			await expect(page).toFill('input[name=order_date_hour]', '18');
			await expect(page).toFill('input[name=order_date_minute]', '55');

			// Create order, verify that it was created. Trash order, verify that it was trashed.
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
