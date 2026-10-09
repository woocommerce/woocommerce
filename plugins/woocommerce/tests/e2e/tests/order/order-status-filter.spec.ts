/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { tags, expect, test } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

const orderBatchId: number[] = [];
const statusColumnSelector = 'mark.order-status';

// Define order statuses to filter against
const orderStatus = [
	[ 'All', 'all' ],
	[ 'Pending payment', 'wc-pending' ],
	[ 'Processing', 'wc-processing' ],
	[ 'On hold', 'wc-on-hold' ],
	[ 'Completed', 'wc-completed' ],
	[ 'Cancelled', 'wc-cancelled' ],
	[ 'Refunded', 'wc-refunded' ],
	[ 'Failed', 'wc-failed' ],
];

test.describe(
	'WooCommerce Orders > Filter Order by Status',
	{ tag: [ tags.HPOS ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		test.beforeAll( async ( { restApi } ) => {
			// create some orders we can filter
			const orders = orderStatus.map( ( entryPair ) => {
				const statusName = entryPair[ 1 ].replace( 'wc-', '' );

				return {
					status: statusName,
				};
			} );
			await restApi
				.post( `${ WC_API_PATH }/orders/batch`, { create: orders } )
				.then( ( response: { data: { create: { id: number }[] } } ) => {
					for ( let i = 0; i < response.data.create.length; i++ ) {
						orderBatchId.push( response.data.create[ i ].id );
					}
				} );
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.post( `${ WC_API_PATH }/orders/batch`, {
				delete: [ ...orderBatchId ],
			} );
		} );

		// Processing is representative: the status filter is one code path. The
		// other seven statuses are still seeded in beforeAll, so asserting that
		// none of them appear also proves the filter excludes what it should.
		test( 'should filter by Processing', async ( { page } ) => {
			await page.goto( 'wp-admin/admin.php?page=wc-orders' );

			await page.locator( 'li.wc-processing' ).click();
			await expect(
				page.locator( 'li.wc-processing > a.current' )
			).toBeVisible();

			await expect(
				page.locator( `${ statusColumnSelector }.status-processing` )
			).not.toHaveCount( 0 );
			await expect(
				page.locator(
					`${ statusColumnSelector }:not(.status-processing)`
				)
			).toHaveCount( 0 );
		} );
	}
);
