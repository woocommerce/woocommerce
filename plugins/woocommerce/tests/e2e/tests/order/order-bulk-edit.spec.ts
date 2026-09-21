/**
 * External dependencies
 */
import { faker } from '@faker-js/faker';
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { tags, expect, test } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

test.describe(
	'Bulk edit orders',
	{ tag: [ tags.SERVICES, tags.HPOS ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		// A few orders are enough to prove bulk editing works. They share a unique
		// billing email so the orders list can be filtered down to just these rows,
		// and we select only those rows (not "select all"). Together this keeps the
		// test mutating only its own orders and immune to other workers' orders
		// pushing it off the first page — safe to run in parallel.
		const billingEmail = `bulk-edit-${ faker.string
			.alphanumeric( 10 )
			.toLowerCase() }@example.com`;
		const orderIds: number[] = [];

		test.beforeAll( async ( { restApi } ) => {
			for ( let i = 0; i < 3; i++ ) {
				const response = await restApi.post(
					`${ WC_API_PATH }/orders`,
					{
						status: 'processing',
						billing: { email: billingEmail },
					}
				);
				orderIds.push( response.data.id );
			}
		} );

		test.afterAll( async ( { restApi } ) => {
			await Promise.all(
				orderIds.map( ( id ) =>
					restApi.delete( `${ WC_API_PATH }/orders/${ id }`, {
						force: true,
					} )
				)
			);
		} );

		test( 'can bulk update order status', async ( { page } ) => {
			const orderRow = ( id: number ) =>
				page.locator( `:is(#order-${ id }, #post-${ id })` );

			// Filter the orders list to just this test's orders (shared billing
			// email) so concurrently-created orders can't push them off the page.
			await page.goto(
				`wp-admin/admin.php?page=wc-orders&s=${ encodeURIComponent(
					billingEmail
				) }`
			);

			// Expect each created order to show 'Processing', then select its row.
			for ( const id of orderIds ) {
				await expect(
					orderRow( id ).getByText( 'Processing' ).nth( 1 )
				).toBeVisible();
				await page.locator( `#cb-select-${ id }` ).check();
			}

			await page
				.locator( '#bulk-action-selector-top' )
				.selectOption( 'Change status to completed' );
			await page.locator( '#doaction' ).click();

			// Expect only the selected orders to now show 'Completed'.
			for ( const id of orderIds ) {
				await expect(
					orderRow( id ).getByText( 'Completed' ).nth( 1 )
				).toBeVisible();
			}
		} );
	}
);
