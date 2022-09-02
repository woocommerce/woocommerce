const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

let productId, orderId;
const productName = 'Order email product';
const customerEmail = 'order-email-test@example.com';
const storeName = 'WooCommerce Core E2E Test Suite';

test.describe( 'Shopper Order Email Receiving', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// add product
		await api
			.post( 'products', {
				name: productName,
				type: 'simple',
				regular_price: '42.77',
			} )
			.then( ( response ) => {
				productId = response.data.id;
			} );
		// enable COD payment
		await api.put( 'payment_gateways/cod', {
			enabled: true,
		} );
	} );

	test.beforeEach( async ( { page } ) => {
		await page.goto(
			`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
				customerEmail
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
		await api.delete( `products/${ productId }`, {
			force: true,
		} );
		if ( orderId ) {
			await api.delete( `orders/${ orderId }`, {
				force: true,
			} );
		}
		await api.put( 'payment_gateways/cod', {
			enabled: false,
		} );
	} );

	test( 'should receive order email after purchasing an item', async ( {
		page,
	} ) => {
		await page.goto( `/shop/?add-to-cart=${ productId }` );
		await page.waitForLoadState( 'networkidle' );

		await page.goto( '/checkout/' );

		await page.fill( '#billing_first_name', 'Maggie' );
		await page.fill( '#billing_last_name', 'Simpson' );
		await page.fill( '#billing_address_1', '123 Evergreen Terrace' );
		await page.fill( '#billing_city', 'Springfield' );
		await page.selectOption( '#billing_state', 'OR' );
		await page.fill( '#billing_postcode', '97403' );
		await page.fill( '#billing_phone', '555 555-5555' );
		await page.fill( '#billing_email', customerEmail );

		await page.click( 'text=Place order' );

		await page.waitForSelector(
			'li.woocommerce-order-overview__order > strong'
		);
		orderId = await page.textContent(
			'li.woocommerce-order-overview__order > strong'
		);

		// search to narrow it down to just the messages we want
		await page.goto(
			`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
				customerEmail
			) }`
		);
		await page.waitForLoadState( 'networkidle' );
		await expect(
			page.locator( 'td.column-receiver >> nth=0' )
		).toContainText( customerEmail );
		await expect(
			page.locator( 'td.column-subject >> nth=1' )
		).toContainText( `[${ storeName }]: New order #${ orderId }` );
	} );
} );
