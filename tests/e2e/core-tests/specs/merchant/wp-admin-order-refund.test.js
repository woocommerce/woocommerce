/* eslint-disable jest/no-export, jest/no-disabled-tests, */

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
let currencySymbol;

const runRefundOrderTest = () => {
	describe('WooCommerce Orders > Refund an order', () => {
		beforeAll(async () => {
			await StoreOwnerFlow.login();
			await createSimpleProduct();
			orderId = await createSimpleOrder();
			await addProductToOrder(orderId, simpleProductName);

			// Get the currency symbol for the store's selected currency
			await page.waitForSelector('.woocommerce-Price-currencySymbol');
			let currencyElement = await page.$('.woocommerce-Price-currencySymbol');
			currencySymbol = await page.evaluate(el => el.textContent, currencyElement);

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
				expect(page).toMatchElement('.do-manual-refund', { text: `Refund ${currencySymbol}9.99 manually` }),
			]);

			await expect(page).toClick('.do-manual-refund');

			await uiUnblocked();

			await Promise.all([
				// Verify the product line item shows the refunded quantity and amount
				expect(page).toMatchElement('.quantity .refunded', { text: '-1' }),
				expect(page).toMatchElement('.line_cost .refunded', { text: `-${currencySymbol}9.99` }),

				// Verify the refund shows in the list with the amount
				expect(page).toMatchElement('.refund .description', { text: 'No longer wanted' }),
				expect(page).toMatchElement('.refund > .line_cost', { text: `-${currencySymbol}9.99` }),

				// Verify system note was added
				expect(page).toMatchElement('.system-note', { text: 'Order status changed from Completed to Refunded.' }),
			]);
		});

		it('can delete an issued refund', async () => {
			// We need to use this here as `expect(page).toClick()` was unable to find the element
			// See: https://github.com/puppeteer/puppeteer/issues/1769#issuecomment-637645219
			page.$eval('a.delete_refund', elem => elem.click());

			await uiUnblocked();

			// Verify the refunded row item is no longer showing
			await page.waitForSelector('tr.refund', { visible: false });

			await Promise.all([
				// Verify the product line item shows the refunded quantity and amount
				expect(page).not.toMatchElement('.quantity .refunded', { text: '-1' }),
				expect(page).not.toMatchElement('.line_cost .refunded', { text: `-${currencySymbol}9.99` }),

				// Verify the refund shows in the list with the amount
				expect(page).not.toMatchElement('.refund .description', { text: 'No longer wanted' }),
				expect(page).not.toMatchElement('.refund > .line_cost', { text: `-${currencySymbol}9.99` }),
			]);
		});

	});

};

module.exports = runRefundOrderTest;
