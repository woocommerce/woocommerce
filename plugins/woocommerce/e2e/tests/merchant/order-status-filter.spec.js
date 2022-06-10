const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const orderBatchId = new Array();
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
	test.use( { storageState: 'e2e/storage/adminState.json' } );

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

		await page.click( 'li.all > a' );
		await page.click( 'th#order_number > a' ); // ensure we're sorting in the right order
		let i = 0;
		for ( const [ statusText ] of orderStatus ) {
			await expect(
				page.locator( `${ statusColumnTextSelector } >> nth=${ i }` )
			).toContainText( statusText );
			i++;
		}
	} );

	for ( let i = 0; i < orderStatus.length; i++ ) {
		test( `should filter by ${ orderStatus[ i ][ 0 ] }`, async ( {
			page,
		} ) => {
			await page.goto( 'wp-admin/edit.php?post_type=shop_order' );

			await page.click( `li.${ orderStatus[ i ][ 1 ] }` );
			await expect(
				page.locator( statusColumnTextSelector )
			).toContainText( orderStatus[ i ][ 0 ] );
		} );
	}
} );
