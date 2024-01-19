const { test, expect } = require( '@playwright/test' );
const { getTranslationFor } = require( '../../utils/translations' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

test.describe( 'Bulk edit orders', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	let orderId1, orderId2, orderId3, orderId4, orderId5;

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
			} )
			.then( ( response ) => {
				orderId1 = response.data.id;
			} );
		await api
			.post( 'orders', {
				status: 'processing',
			} )
			.then( ( response ) => {
				orderId2 = response.data.id;
			} );
		await api
			.post( 'orders', {
				status: 'processing',
			} )
			.then( ( response ) => {
				orderId3 = response.data.id;
			} );
		await api
			.post( 'orders', {
				status: 'processing',
			} )
			.then( ( response ) => {
				orderId4 = response.data.id;
			} );
		await api
			.post( 'orders', {
				status: 'processing',
			} )
			.then( ( response ) => {
				orderId5 = response.data.id;
			} );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.delete( `orders/${ orderId1 }`, { force: true } );
		await api.delete( `orders/${ orderId2 }`, { force: true } );
		await api.delete( `orders/${ orderId3 }`, { force: true } );
		await api.delete( `orders/${ orderId4 }`, { force: true } );
		await api.delete( `orders/${ orderId5 }`, { force: true } );
	} );

	test( 'can bulk update order status', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-orders' );

		const orderIds = [orderId1, orderId2, orderId3, orderId4, orderId5];

		// expect order status 'processing' to show
		for (const orderId of orderIds) {
			await expect(
				page.locator( `:is(#order-${ orderId }, #post-${ orderId })` ).getByText( getTranslationFor( 'Processing' ) )
			).toBeVisible();
		}
		
		await page.locator( '#cb-select-all-1' ).click();
		await page.locator( '#bulk-action-selector-top' ).selectOption( getTranslationFor( 'Change status to completed' ));
		await page.locator('#doaction').click();

		// expect order status 'completed' to show
		for (const orderId of orderIds) {
			await expect(
				page.locator( `:is(#order-${ orderId }, #post-${ orderId })` ).getByText( getTranslationFor( 'Completed' ) )
			).toBeVisible();
		}

	} );

} );
