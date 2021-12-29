/**
 * Internal dependencies
 */
const {
	merchant,
	createOrder,
	createSimpleProduct,
	verifyCheckboxIsSet,
	uiUnblocked,
	evalAndClick,
	clickUpdateOrder,
} = require( '@woocommerce/e2e-utils' );
const { waitForSelector } = require( '@woocommerce/e2e-environment' );

/**
 * Evaluate and click a button selector then wait for a result selector.
 * This is a work around for what appears to be intermittent delays in handling confirm dialogs.
 *
 * @param {string} buttonSelector
 * @param {string} resultSelector
 * @returns {Promise<void>}
 */
const clickAndWaitForSelector = async ( buttonSelector, resultSelector ) => {
	await evalAndClick( buttonSelector );
	await waitForSelector( page, resultSelector, {
		timeout: 5000,
	} );
};

const getRefundQuantityInputSelector = ( productName ) =>
	`td.name[data-sort-value="${ productName }"] ~ td.quantity input.refund_order_item_qty`;

const runOrderRefundRestockTest = () => {
	describe( 'WooCommerce Orders > Refund and restock an order item', () => {
		// See: https://github.com/woocommerce/woocommerce/issues/30618
		it( 'Can update order after refunding item without automatic stock adjustment', async () => {
			const noInventoryProductId = await createSimpleProduct();
			const productToRestockId = await createSimpleProduct(
				'Product with Stock',
				'10',
				{
					trackInventory: true,
					remainingStock: 10,
				}
			);

			const orderId = await createOrder( {
				status: 'completed',
				lineItems: [
					{
						product_id: noInventoryProductId,
					},
					{
						product_id: productToRestockId,
						quantity: 2,
					},
				],
			} );

			await merchant.login();
			await merchant.goToOrder( orderId );

			// Get the currency symbol for the store's selected currency
			await page.waitForSelector( '.woocommerce-Price-currencySymbol' );

			// Verify stock reduction system note was added
			await expect( page ).toMatchElement( '.system-note', {
				text: `Stock levels reduced: Product with Stock (#${ productToRestockId }) 10→8`,
			} );

			// Click the Refund button
			await expect( page ).toClick( 'button.refund-items' );

			// Verify the refund section shows
			await page.waitForSelector( 'div.wc-order-refund-items', {
				visible: true,
			} );
			await verifyCheckboxIsSet( '#restock_refunded_items' );

			// Initiate a refund
			await expect( page ).toFill(
				getRefundQuantityInputSelector( 'Product with Stock' ),
				'2'
			);
			await expect( page ).toFill( '#refund_reason', 'No longer wanted' );

			await clickAndWaitForSelector(
				'.do-manual-refund',
				'.quantity .refunded'
			);
			await uiUnblocked();

			// Verify restock system note was added
			await expect( page ).toMatchElement( '.system-note', {
				text: `Item #${ productToRestockId } stock increased from 8 to 10.`,
			} );

			// Update the order.
			await clickUpdateOrder( 'Order updated.' );

			// Verify that inventory wasn't modified.
			// For some reason using expect().not.toMatchElement() did not work for this case.
			expect(
				await page.$$eval( '.note', ( notes ) =>
					notes.every(
						( note ) =>
							! note.textContent.match(
								/Adjusted stock: Product with Stock \(10→8\)/
							)
					)
				)
			).toEqual( true );
		} );
	} );
};

module.exports = runOrderRefundRestockTest;
