/**
 * Internal dependencies
 */
const {
	merchant,
	createSimpleProduct,
	verifyCheckboxIsSet,
	verifyValueOfInputField,
	uiUnblocked,
	evalAndClick,
	createOrder,
} = require( '@woocommerce/e2e-utils' );

const { waitForSelector } = require( '@woocommerce/e2e-environment' );

const config = require( 'config' );
const simpleProductPrice = config.has('products.simple.price') ? config.get('products.simple.price') : '9.99';

/**
 * Evaluate and click a button selector then wait for a result selector.
 * This is a work around for what appears to be intermittent delays in handling confirm dialogs.
 *
 * @param buttonSelector
 * @param resultSelector
 * @returns {Promise<void>}
 */
const clickAndWaitForSelector = async ( buttonSelector, resultSelector ) => {
	await evalAndClick( buttonSelector );
	await waitForSelector(
		page,
		resultSelector,
		{
			timeout: 5000
		}
	);
};

const runRefundOrderTest = () => {
	describe('WooCommerce Orders > Refund an order', () => {
		let productId;
		let orderId;
		let currencySymbol;

		beforeAll(async () => {
			productId = await createSimpleProduct();
			orderId = await createOrder( {
				productId,
				status: 'completed'
			} );

			await merchant.login();
			await merchant.goToOrder( orderId );

			// Get the currency symbol for the store's selected currency
			await page.waitForSelector('.woocommerce-Price-currencySymbol');
			let currencyElement = await page.$('.woocommerce-Price-currencySymbol');
			currencySymbol = await page.evaluate(el => el.textContent, currencyElement);
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

			await clickAndWaitForSelector( '.do-manual-refund', '.quantity .refunded' );
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
		});

		it('can delete an issued refund', async () => {
			await clickAndWaitForSelector( 'a.delete_refund', '.refund-items' );
			await uiUnblocked();

			await Promise.all([
				// Verify the refunded row item is no longer showing
				expect(page).not.toMatchElement('tr.refund', { visible: true }),
				// Verify the product line item doesn't show the refunded quantity and amount
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
