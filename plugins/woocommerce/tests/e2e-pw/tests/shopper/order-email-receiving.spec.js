const { test, expect } = require( '@playwright/test' );
const { customer, storeDetails } = require( '../../test-data/data' );
const { api } = require( '../../utils' );

let productId, orderId;

const product = {
	name: 'Order email product',
	type: 'simple',
	price: '42.77',
};

const storeName = 'WooCommerce Core E2E Test Suite';

test.describe( 'Shopper Order Email Receiving', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async () => {
		productId = await api.create.product( product );
		await api.update.enableCashOnDelivery();
	} );

	test.beforeEach( async ( { page } ) => {
		await page.goto(
			`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
				customer.email
			) }`
		);
		// clear out the email logs before each test
		while ( ( await page.$( '#bulk-action-selector-top' ) ) !== null ) {
			await page.click( '#cb-select-all-1' );
			await page.selectOption( '#bulk-action-selector-top', 'delete' );
			await page.click( '#doaction' );
		}
	} );

	test.afterAll( async () => {
		await api.deletePost.product( productId );
		if ( orderId ) {
			await api.deletePost.order( orderId );
		}
		await api.update.disableCashOnDelivery();
	} );

	test( 'should receive order email after purchasing an item', async ( {
		page,
	} ) => {
		// ensure that the store's address is in the US
		await api.update.storeDetails( storeDetails.us.store );

		await page.goto( `/shop/?add-to-cart=${ productId }` );
		await page.waitForLoadState( 'networkidle' );

		await page.goto( '/checkout/' );

		await page.fill(
			'#billing_first_name',
			customer.billing.us.first_name
		);
		await page.fill( '#billing_last_name', customer.billing.us.last_name );
		await page.fill( '#billing_address_1', customer.billing.us.address );
		await page.fill( '#billing_city', customer.billing.us.city );
		await page.selectOption(
			'#billing_country',
			customer.billing.us.country
		);

		await page.selectOption( '#billing_state', customer.billing.us.state );

		await page.fill( '#billing_postcode', customer.billing.us.zip );
		await page.fill( '#billing_phone', customer.billing.us.phone );
		await page.fill( '#billing_email', customer.email );

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
				customer.email
			) }`
		);
		await page.waitForLoadState( 'networkidle' );
		await expect(
			page.locator( 'td.column-receiver >> nth=0' )
		).toContainText( customer.email );
		await expect(
			page.locator( 'td.column-subject >> nth=1' )
		).toContainText( `[${ storeName }]: New order #${ orderId }` );
	} );
} );
