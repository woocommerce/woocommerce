const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const orderBatchId = [];
const statusColumnTextSelector = 'mark.order-status > span';

// Define order statuses to filter against
const orderStatus = [
	[ 'Pending payment', 'wc-pending' ],
	[ 'Processing', 'wc-processing' ],
	[ 'On hold', 'wc-on-hold' ],
	[ 'Completed', 'wc-completed' ],
	[ 'Cancelled', 'wc-cancelled' ],
	[ 'Refunded', 'wc-refunded' ],
	[ 'Failed', 'wc-failed' ],
];

test.describe( 'WooCommerce Orders > Filter Order by Status', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// create some orders we can filter
		const orders = orderStatus.map( ( entryPair ) => {
			const statusName = entryPair[ 1 ].replace( 'wc-', '' );

			return {
				status: statusName,
			};
		} );
		await api
			.post( 'orders/batch', { create: orders } )
			.then( ( response ) => {
				for ( let i = 0; i < response.data.create.length; i++ ) {
					orderBatchId.push( response.data.create[ i ].id );
				}
			} );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.post( 'orders/batch', { delete: [ ...orderBatchId ] } );
	} );

	test( 'should filter by All', async ( { page } ) => {
		await page.goto( 'wp-admin/edit.php?post_type=shop_order' );

		await page.locator( 'li.all > a' ).click();
		await page.waitForLoadState( 'networkidle' );
		// because tests are running in parallel, we can't know how many orders there
		// are beyond the ones we created here.
		for ( let i = 0; i < orderStatus.length; i++ ) {
			const statusTag = 'text=' + orderStatus[ i ][ 0 ];
			const countElements = await page.locator( statusTag ).count();
			await expect( countElements ).toBeGreaterThan( 0 );
		}
	} );

	for ( let i = 0; i < orderStatus.length; i++ ) {
		test( `should filter by ${ orderStatus[ i ][ 0 ] }`, async ( {
			page,
		} ) => {
			await page.goto( 'wp-admin/edit.php?post_type=shop_order' );

			await page.locator( `li.${ orderStatus[ i ][ 1 ] }` ).click();
			await page.waitForLoadState( 'networkidle' );
			const countElements = await page
				.locator( statusColumnTextSelector )
				.count();
			await expect( countElements ).toBeGreaterThan( 0 );
		} );
	}
} );
