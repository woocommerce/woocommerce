const { test, expect } = require( '@playwright/test' );
const { admin } = require( '../../test-data/data' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

test.describe( 'Merchant > Order Action emails received', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	const customerBilling = {
		email: 'john.doe.merchant.test@example.com',
	};

	const storeName = 'WooCommerce Core E2E Test Suite';
	let orderId, newOrderId;

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api
			.post( 'orders', {
				status: 'processing',
				billing: customerBilling,
			} )
			.then( ( response ) => {
				orderId = response.data.id;
			} );
	} );

	test.beforeEach( async ( { page } ) => {
		await page.goto(
			`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
				customerBilling.email
			) }`
		);
		// clear out the email logs before each test
		while ( ( await page.$( '#bulk-action-selector-top' ) ) !== null ) {
			await page.click( '#cb-select-all-1' );
			await page.selectOption( '#bulk-action-selector-top', 'delete' );
			await page.click( '#doaction' );
		}
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.delete( `orders/${ orderId }`, { force: true } );
		await api.delete( `orders/${ newOrderId }`, { force: true } );
	} );

	test( 'can receive new order email', async ( { page, baseURL } ) => {
		// New order emails are sent automatically when we create a simple order. Verify that we get these.
		// Need to create a new order for this test because we clear logs before each run.
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api
			.post( 'orders', {
				status: 'processing',
				billing: customerBilling,
			} )
			.then( ( response ) => {
				newOrderId = response.data.id;
			} );
		// search to narrow it down to just the messages we want
		await page.goto(
			`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
				customerBilling.email
			) }`
		);
		await expect(
			page.locator( 'td.column-receiver >> nth=1' )
		).toContainText( admin.email );
		await expect(
			page.locator( 'td.column-subject >> nth=1' )
		).toContainText( `[${ storeName }]: New order #${ newOrderId }` );
	} );

	test( 'can resend new order notification', async ( { page } ) => {
		// resend the new order notification
		await page.goto( `wp-admin/post.php?post=${ orderId }&action=edit` );
		await page.selectOption(
			'li#actions > select',
			'send_order_details_admin'
		);
		await page.click( 'button.wc-reload' );
		await page.waitForLoadState( 'networkidle' );

		// search to narrow it down to just the messages we want
		await page.goto(
			`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
				customerBilling.email
			) }`
		);
		await expect( page.locator( 'td.column-receiver' ) ).toContainText(
			admin.email
		);
		await expect( page.locator( 'td.column-subject' ) ).toContainText(
			`[${ storeName }]: New order #${ orderId }`
		);
	} );

	test( 'can email invoice/order details to customer', async ( { page } ) => {
		// send the customer order details
		await page.goto( `wp-admin/post.php?post=${ orderId }&action=edit` );
		await page.selectOption( 'li#actions > select', 'send_order_details' );
		await page.click( 'button.wc-reload' );
		await page.waitForLoadState( 'networkidle' );

		// confirm the message was delivered in the logs
		await page.goto(
			`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
				customerBilling.email
			) }`
		);
		await expect( page.locator( 'td.column-receiver' ) ).toContainText(
			customerBilling.email
		);
		await expect( page.locator( 'td.column-subject' ) ).toContainText(
			`Invoice for order #${ orderId } on ${ storeName }`
		);
	} );
} );
