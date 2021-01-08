/* eslint-disable jest/no-export, jest/no-disabled-tests, */

/**
 * Internal dependencies
 */
const {
	merchant,
	createSimpleProduct,
	createSimpleOrder,
	verifyCheckboxIsSet,
	verifyValueOfInputField,
	uiUnblocked,
	addProductToOrder,
	evalAndClick,
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );
const simpleProductPrice = config.has('products.simple.price') ? config.get('products.simple.price') : '9.99';

let orderId;
let currencySymbol;

const runRefundOrderTest = () => {
	describe('WooCommerce Orders > Refund an order', () => {
		beforeAll(async () => {
			await merchant.login();
			await createSimpleProduct();
			orderId = await createSimpleOrder();
			await addProductToOrder(orderId, simpleProductName);

			// Get the currency symbol for the store's selected currency
			await page.waitForSelector('.woocommerce-Price-currencySymbol');
			let currencyElement = await page.$('.woocommerce-Price-currencySymbol');
			currencySymbol = await page.evaluate(el => el.textContent, currencyElement);

			// Update order status to `Completed` so we can issue a refund
			await merchant.updateOrderStatus(orderId, 'Completed');
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
				verifyValueOfInputField('.refund_line_total', simpleProductPrice),
				verifyValueOfInputField('#refund_amount', simpleProductPrice),
				expect(page).toMatchElement('.do-manual-refund', { text: `Refund ${currencySymbol + simpleProductPrice} manually` }),
			]);

			await expect(page).toClick('.do-manual-refund');

			await uiUnblocked();

			await Promise.all([
				// Verify the product line item shows the refunded quantity and amount
				expect(page).toMatchElement('.quantity .refunded', { text: '-1' }),
				expect(page).toMatchElement('.line_cost .refunded', { text: `-${currencySymbol + simpleProductPrice}` }),

				// Verify the refund shows in the list with the amount
				expect(page).toMatchElement('.refund .description', { text: 'No longer wanted' }),
				expect(page).toMatchElement('.refund > .line_cost', { text: `-${currencySymbol + simpleProductPrice}` }),

				// Verify system note was added
				expect(page).toMatchElement('.system-note', { text: 'Order status changed from Completed to Refunded.' }),
			]);
			page.waitForNavigation( { waitUntil: 'networkidle0' } );
		});

		it('can delete an issued refund', async () => {
			await evalAndClick( 'a.delete_refund' );
			await uiUnblocked();

			// Verify the refunded row item is no longer showing
			await page.waitForSelector('tr.refund', { visible: false });

			await Promise.all([
				// Verify the product line item shows the refunded quantity and amount
				expect(page).not.toMatchElement('.quantity .refunded', { text: '-1' }),
				expect(page).not.toMatchElement('.line_cost .refunded', { text: `-${currencySymbol + simpleProductPrice}` }),

				// Verify the refund shows in the list with the amount
				expect(page).not.toMatchElement('.refund .description', { text: 'No longer wanted' }),
				expect(page).not.toMatchElement('.refund > .line_cost', { text: `-${currencySymbol + simpleProductPrice}` }),
			]);
		});

	});

};

module.exports = runRefundOrderTest;
