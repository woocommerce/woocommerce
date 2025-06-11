/**
 * Internal dependencies
 */
const {
	merchant,
	withRestApi,
	utils,
	createSimpleDownloadableProduct,
	createOrder,
	verifyValueOfInputField,
	orderPageSaveChanges,
} = require( '@woocommerce/e2e-utils' );

let orderId;

const orderStatus = {
	processing: 'processing',
	completed: 'completed',
};

const runEditOrderTest = () => {
	describe( 'WooCommerce Orders > Edit order', () => {
		beforeAll( async () => {
			orderId = await createOrder( { status: orderStatus.processing } );
			await merchant.login();
		} );

		afterAll( async () => {
			await withRestApi.deleteOrder( orderId );
		} );

		it( 'can view single order', async () => {
			// Go to "orders" page
			await merchant.openAllOrdersView();

			// Make sure we're on the orders page
			await expect( page.title() ).resolves.toMatch( 'Orders' );

			//Open order we created
			await merchant.goToOrder( orderId );

			// Make sure we're on the order details page
			await expect( page.title() ).resolves.toMatch( 'Edit order' );
		} );

		it( 'can update order status', async () => {
			//Open order we created
			await merchant.goToOrder( orderId );

			// Make sure we're still on the order details page
			await expect( page.title() ).resolves.toMatch( 'Edit order' );

			// Update order status to `Completed`
			await merchant.updateOrderStatus( orderId, 'Completed' );

			// Verify order status changed note added
			await expect( page ).toMatchElement(
				'#select2-order_status-container',
				{
					text: 'Completed',
				}
			);
			await expect( page ).toMatchElement(
				'#woocommerce-order-notes .note_content',
				{
					text: 'Order status changed from Processing to Completed.',
				}
			);
		} );

		it( 'can update order details', async () => {
			//Open order we created
			await merchant.goToOrder( orderId );

			// Make sure we're still on the order details page
			await expect( page.title() ).resolves.toMatch( 'Edit order' );

			// Update order details
			await expect( page ).toFill(
				'input[name=order_date]',
				'2018-12-14'
			);

			// Wait for auto save
			await utils.waitForTimeout( 2000 );

			// Save the order changes
			await orderPageSaveChanges();

			// Verify
			await expect( page ).toMatchElement( '#message', {
				text: 'Order updated.',
			} );
			await verifyValueOfInputField(
				'input[name=order_date]',
				'2018-12-14'
			);
		} );
	} );

	describe( 'WooCommerce Orders > Edit order > Downloadable product permissions', () => {
		const productName = 'TDP 001';
		const customerBilling = {
			email: 'john.doe@example.com',
		};

		let productId;

		beforeAll( async () => {
			await merchant.login();
		} );

		beforeEach( async () => {
			productId = await createSimpleDownloadableProduct( productName );
			orderId = await createOrder( {
				productId,
				customerBilling,
				status: orderStatus.processing,
			} );
		} );

		afterEach( async () => {
			await withRestApi.deleteOrder( orderId );
			await withRestApi.deleteProduct( productId );
		} );

		it( 'can add downloadable product permissions to order without product', async () => {
			// Create order without product
			const newOrderId = await createOrder( {
				customerBilling,
				status: orderStatus.processing,
			} );

			// Open order we created
			await merchant.goToOrder( newOrderId );

			// Add permission
			await merchant.addDownloadableProductPermission( productName );

			// Verify new downloadable product permission details
			await merchant.verifyDownloadableProductPermission( productName );

			// Remove order
			await withRestApi.deleteOrder( newOrderId );
		} );

		it( 'can add downloadable product permissions to order with product', async () => {
			// Create new downloadable product
			const newProductName = 'TDP 002';
			const newProductId = await createSimpleDownloadableProduct(
				newProductName
			);

			// Open order we created
			await merchant.goToOrder( orderId );

			// Add permission
			await merchant.addDownloadableProductPermission( newProductName );

			// Verify new downloadable product permission details
			await merchant.verifyDownloadableProductPermission(
				newProductName
			);

			// Remove product
			await withRestApi.deleteProduct( newProductId );
		} );

		it( 'can edit downloadable product permissions', async () => {
			// Define expected downloadable product attributes
			const expectedDownloadsRemaining = '10';
			const expectedDownloadsExpirationDate = '2050-01-01';

			// Open order we created
			await merchant.goToOrder( orderId );

			// Update permission
			await merchant.updateDownloadableProductPermission(
				productName,
				expectedDownloadsExpirationDate,
				expectedDownloadsRemaining
			);

			// Verify new downloadable product permission details
			await merchant.verifyDownloadableProductPermission(
				productName,
				expectedDownloadsExpirationDate,
				expectedDownloadsRemaining
			);
		} );

		it( 'can revoke downloadable product permissions', async () => {
			// Open order we created
			await merchant.goToOrder( orderId );

			// Revoke permission
			await merchant.revokeDownloadableProductPermission( productName );

			// Verify
			await expect( page ).not.toMatchElement(
				'div.order_download_permissions',
				{
					text: productName,
				}
			);
		} );

		it( 'should not allow downloading a product if download attempts are exceeded', async () => {
			// Define expected download error reason
			const expectedReason =
				'Sorry, you have reached your download limit for this file';

			// Create order with product without any available download attempt
			const newProductId = await createSimpleDownloadableProduct(
				productName,
				0
			);
			const newOrderId = await createOrder( {
				productId: newProductId,
				customerBilling,
				status: orderStatus.processing,
			} );

			// Open order we created
			await merchant.goToOrder( newOrderId );

			// Open download page
			const downloadPage = await merchant.openDownloadLink();

			// Verify file download cannot start
			await merchant.verifyCannotDownloadFromBecause(
				downloadPage,
				expectedReason
			);

			// Remove data
			await withRestApi.deleteOrder( newOrderId );
			await withRestApi.deleteProduct( newProductId );
		} );

		it( 'should not allow downloading a product if expiration date is exceeded', async () => {
			// Define expected download error reason
			const expectedReason = 'Sorry, this download has expired';

			// Open order we created
			await merchant.goToOrder( orderId );

			// Update permission so that the expiration date has already passed
			// Note: Seems this operation can't be performed through the API
			await merchant.updateDownloadableProductPermission(
				productName,
				'2018-12-14'
			);

			// Open download page
			const downloadPage = await merchant.openDownloadLink();

			// Verify file download cannot start
			await merchant.verifyCannotDownloadFromBecause(
				downloadPage,
				expectedReason
			);
		} );
	} );
};

// eslint-disable-next-line jest/no-export
module.exports = runEditOrderTest;
