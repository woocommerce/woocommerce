const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

test.describe(
	'Bulk edit orders',
	{ tag: [ '@services', '@external' ] },
	() => {
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

			// expect order status 'processing' to show
			await expect(
				page
					.locator( `#order-${ orderId1 }` )
					.locator( '.order_status' )
					.locator( 'mark' )
					.getByText( 'Processing' )
			).toBeVisible();
			await expect(
				page
					.locator( `#order-${ orderId2 }` )
					.locator( '.order_status' )
					.locator( 'mark' )
					.getByText( 'Processing' )
			).toBeVisible();
			await expect(
				page
					.locator( `#order-${ orderId3 }` )
					.locator( '.order_status' )
					.locator( 'mark' )
					.getByText( 'Processing' )
			).toBeVisible();
			await expect(
				page
					.locator( `#order-${ orderId4 }` )
					.locator( '.order_status' )
					.locator( 'mark' )
					.getByText( 'Processing' )
			).toBeVisible();
			await expect(
				page
					.locator( `#order-${ orderId5 }` )
					.locator( '.order_status' )
					.locator( 'mark' )
					.getByText( 'Processing' )
			).toBeVisible();

			await page.locator( '#cb-select-all-1' ).click();
			await page
				.locator( '#bulk-action-selector-top' )
				.selectOption( 'Change status to completed' );
			await page.locator( '#doaction' ).click();

			// expect order status 'completed' to show
			await expect(
				page
					.locator( `#order-${ orderId1 }` )
					.locator( '.order_status' )
					.locator( 'mark' )
					.getByText( 'Completed' )
			).toBeVisible();
			await expect(
				page
					.locator( `#order-${ orderId2 }` )
					.locator( '.order_status' )
					.locator( 'mark' )
					.getByText( 'Completed' )
			).toBeVisible();
			await expect(
				page
					.locator( `#order-${ orderId3 }` )
					.locator( '.order_status' )
					.locator( 'mark' )
					.getByText( 'Completed' )
			).toBeVisible();
			await expect(
				page
					.locator( `#order-${ orderId4 }` )
					.locator( '.order_status' )
					.locator( 'mark' )
					.getByText( 'Completed' )
			).toBeVisible();
			await expect(
				page
					.locator( `#order-${ orderId5 }` )
					.locator( '.order_status' )
					.locator( 'mark' )
					.getByText( 'Completed' )
			).toBeVisible();
		} );
	}
);
