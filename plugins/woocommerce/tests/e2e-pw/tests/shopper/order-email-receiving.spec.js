const { test, expect } = require( '@playwright/test' );
const { customer, storeDetails } = require( '../../test-data/data' );
const { api } = require( '../../utils' );
const { getOrderIdFromUrl } = require( '../../utils/order' );
const { addAProductToCart } = require( '../../utils/cart' );

let productId, orderId, zoneId;

const product = {
	name: 'Order email product',
	type: 'simple',
	regular_price: '42.77',
};
const zoneInfo = {
	name: 'Free shipping',
};
const methodInfo = {
	method_id: 'free_shipping',
};

const storeName = 'WooCommerce Core E2E Test Suite';

test.describe( 'Shopper Order Email Receiving', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async () => {
		productId = await api.create.product( product );
		await api.update.enableCashOnDelivery();

		zoneId = await api.create.shippingZone( zoneInfo );
		await api.create.shippingMethod( zoneId, methodInfo );
	} );

	test.beforeEach( async ( { page } ) => {
		await page.goto(
			`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
				customer.email
			) }`
		);
		// clear out the email logs before each test
		while (
			await page.locator( '#bulk-action-selector-top' ).isVisible()
		) {
			// In WP 6.3, label intercepts check action. Need to force.
			await page
				.getByLabel( 'Select All' )
				.first()
				.check( { force: true } );
			await page
				.locator( '#bulk-action-selector-top' )
				.selectOption( 'delete' );
			await page.locator( '#doaction' ).click();
		}
	} );

	test.afterAll( async () => {
		await api.deletePost.product( productId );
		if ( orderId ) {
			await api.deletePost.order( orderId );
		}
		await api.update.disableCashOnDelivery();

		await api.deletePost.shippingZone( zoneId );
	} );

	test( 'should receive order email after purchasing an item', async ( {
		page,
	} ) => {
		// ensure that the store's address is in the US
		await api.update.storeDetails( storeDetails.us.store );

		await addAProductToCart( page, productId );

		await page.goto( '/checkout/' );

		await page
			.locator( '#billing_first_name' )
			.fill( customer.billing.us.first_name );
		await page
			.locator( '#billing_last_name' )
			.fill( customer.billing.us.last_name );
		await page
			.locator( '#billing_address_1' )
			.fill( customer.billing.us.address );
		await page.locator( '#billing_city' ).fill( customer.billing.us.city );
		await page
			.locator( '#billing_country' )
			.selectOption( customer.billing.us.country );

		await page
			.locator( '#billing_state' )
			.selectOption( customer.billing.us.state );

		await page
			.locator( '#billing_postcode' )
			.fill( customer.billing.us.zip );
		await page
			.locator( '#billing_phone' )
			.fill( customer.billing.us.phone );
		await page.locator( '#billing_email' ).fill( customer.email );

		await page.locator( 'text=Place order' ).click();

		await expect(
			page.getByText( 'Your order has been received' )
		).toBeVisible();
		orderId = getOrderIdFromUrl( page );

		// search to narrow it down to just the messages we want
		await page.goto(
			`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
				customer.email
			) }`
		);
		await expect(
			page.locator( 'td.column-receiver >> nth=0' )
		).toContainText( customer.email );
		await expect(
			page.locator( 'td.column-subject >> nth=1' )
		).toContainText( `[${ storeName }]: New order #${ orderId }` );
	} );
} );
