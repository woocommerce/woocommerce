/* eslint-disable jest/no-export, jest/no-disabled-tests, jest/no-standalone-expect */

/**
 * Internal dependencies
 */
const {
	StoreOwnerFlow,
	createSimpleProduct,
	createSimpleOrder,
	verifyCheckboxIsSet,
	verifyValueOfInputField,
	uiUnblocked,
	addProductToOrder,
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );

let orderId;

const runRefundOrderTest = () => {
	describe('WooCommerce Orders > Refund an order', () => {
		beforeAll(async () => {
			await StoreOwnerFlow.login();

			await createSimpleProduct();
			orderId = await createSimpleOrder();
			await addProductToOrder(orderId, simpleProductName);

			// Update order status to `Completed` so we can issue a refund
			await StoreOwnerFlow.updateOrderStatus(orderId, 'Completed');
		});

		it('can issue a refund by quantity', async () => {
			// Click the Refund button
			await expect(page).toClick('button.refund-items');

			// Verify the refund section shows
			await page.waitForSelector('div.wc-order-refund-items', { visible: true });
			await verifyCheckboxIsSet('#restock_refunded_items');

			// Initiate a refund
			await expect(page).toFill('.refund_order_item_qty', '1');
			await expect(page).toFill('#refund_reason', 'No longer wanted');

			await Promise.all([
				verifyValueOfInputField('.refund_line_total', '9.99'),
				verifyValueOfInputField('#refund_amount', '9.99'),
				expect(page).toMatchElement('.do-manual-refund', { text: 'Refund £9.99 manually' }),
			]);

			await expect(page).toClick('.do-manual-refund');

			await uiUnblocked();

			await Promise.all([
				// Verify the product line item shows the refunded quantity and amount
				expect(page).toMatchElement('.quantity .refunded', { text: '-1' }),
				expect(page).toMatchElement('.line_cost .refunded', { text: '-£9.99' }),

				// Verify the refund shows in the list with the amount
				expect(page).toMatchElement('.refund .description', { text: 'No longer wanted' }),
				expect(page).toMatchElement('.refund > .line_cost', { text: '-£9.99' }),
			]);
		});

	});

};

module.exports = runRefundOrderTest;
