/**
 * Internal dependencies
 */
const {
	merchant,
	clickUpdateOrder,
	createSimpleOrder,
	selectOrderAction,
	deleteAllEmailLogs,
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );
const customerEmail = config.get( 'addresses.customer.billing.email' );
const adminEmail = config.has( 'users.admin.email' ) ? config.get( 'users.admin.email' )  : 'admin@woocommercecoree2etestsuite.com';
const storeName = 'WooCommerce Core E2E Test Suite';

let orderId;

const runMerchantOrderEmailsTest = () => {

	describe('Merchant > Order Action emails received', () => {
		beforeAll( async () => {
			await merchant.login();

			// Clear out the existing email logs if any
			await deleteAllEmailLogs();

			orderId = await createSimpleOrder( 'Processing' );

			await Promise.all( [
				// Select the billing email address field and add the customer billing email from the config
				await page.click( 'div.order_data_column:nth-child(2) > h3:nth-child(1) > a:nth-child(1)' ),
				await expect( page ).toFill( '#_billing_email', customerEmail ),
				await clickUpdateOrder( 'Order updated.' ),
			] );
		} );

		afterEach( async () => {
			// Clear out any emails after each test
			await deleteAllEmailLogs();
		} );

		// New order emails are sent automatically when we create the simple order above, so let's verify we get these emails
		it('can receive new order email', async () => {
			await merchant.openEmailLog();
			await expect( page ).toMatchElement( '.column-receiver', { text: adminEmail } );
			await expect( page ).toMatchElement( '.column-subject', { text: `[${storeName}]: New order #${orderId}` } );
		} );

		it('can resend new order notification', async () => {
			await merchant.goToOrder( orderId );
			await selectOrderAction( 'send_order_details_admin' );

			await merchant.openEmailLog();
			await expect( page ).toMatchElement( '.column-receiver', { text: adminEmail } );
			await expect( page ).toMatchElement( '.column-subject', { text: `[${storeName}]: New order #${orderId}` } );
		} );

		it('can email invoice/order details to customer', async () => {
			await merchant.goToOrder( orderId );
			await selectOrderAction( 'send_order_details' );

			await merchant.openEmailLog();
			await expect( page ).toMatchElement( '.column-receiver', { text: customerEmail } );
			await expect( page ).toMatchElement( '.column-subject', { text: `Invoice for order #${orderId} on ${storeName}` } );
		} );

	} );
}

module.exports = runMerchantOrderEmailsTest;
