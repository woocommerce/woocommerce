/**
 * Internal dependencies
 */
import { tags, expect, test } from '../../fixtures/fixtures';
import { WC_API_PATH } from '../../utils/api-client';
import { ADMIN_STATE_PATH } from '../../playwright.config';

test.describe(
	'Bulk edit orders',
	{ tag: [ tags.SERVICES, tags.HPOS ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		let orderId1, orderId2, orderId3, orderId4, orderId5;

		test.beforeAll( async ( { restApi } ) => {
			await restApi
				.post( `${ WC_API_PATH }/orders`, {
					status: 'processing',
				} )
				.then( ( response ) => {
					orderId1 = response.data.id;
				} );
			await restApi
				.post( `${ WC_API_PATH }/orders`, {
					status: 'processing',
				} )
				.then( ( response ) => {
					orderId2 = response.data.id;
				} );
			await restApi
				.post( `${ WC_API_PATH }/orders`, {
					status: 'processing',
				} )
				.then( ( response ) => {
					orderId3 = response.data.id;
				} );
			await restApi
				.post( `${ WC_API_PATH }/orders`, {
					status: 'processing',
				} )
				.then( ( response ) => {
					orderId4 = response.data.id;
				} );
			await restApi
				.post( `${ WC_API_PATH }/orders`, {
					status: 'processing',
				} )
				.then( ( response ) => {
					orderId5 = response.data.id;
				} );
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.delete( `${ WC_API_PATH }/orders/${ orderId1 }`, {
				force: true,
			} );
			await restApi.delete( `${ WC_API_PATH }/orders/${ orderId2 }`, {
				force: true,
			} );
			await restApi.delete( `${ WC_API_PATH }/orders/${ orderId3 }`, {
				force: true,
			} );
			await restApi.delete( `${ WC_API_PATH }/orders/${ orderId4 }`, {
				force: true,
			} );
			await restApi.delete( `${ WC_API_PATH }/orders/${ orderId5 }`, {
				force: true,
			} );
		} );

		test( 'can bulk update order status', async ( { page } ) => {
			await page.goto( 'wp-admin/admin.php?page=wc-orders' );

			// expect order status 'processing' to show
			await expect(
				page
					.locator( `:is(#order-${ orderId1 }, #post-${ orderId1 })` )
					.getByText( 'Processing' )
					.nth( 1 )
			).toBeVisible();
			await expect(
				page
					.locator( `:is(#order-${ orderId2 }, #post-${ orderId2 })` )
					.getByText( 'Processing' )
					.nth( 1 )
			).toBeVisible();
			await expect(
				page
					.locator( `:is(#order-${ orderId3 }, #post-${ orderId3 })` )
					.getByText( 'Processing' )
					.nth( 1 )
			).toBeVisible();
			await expect(
				page
					.locator( `:is(#order-${ orderId4 }, #post-${ orderId4 })` )
					.getByText( 'Processing' )
					.nth( 1 )
			).toBeVisible();
			await expect(
				page
					.locator( `:is(#order-${ orderId5 }, #post-${ orderId5 })` )
					.getByText( 'Processing' )
					.nth( 1 )
			).toBeVisible();

			await page.locator( '#cb-select-all-1' ).click();
			await page
				.locator( '#bulk-action-selector-top' )
				.selectOption( 'Change status to completed' );
			await page.locator( '#doaction' ).click();

			// expect order status 'completed' to show
			await expect(
				page
					.locator( `:is(#order-${ orderId1 }, #post-${ orderId1 })` )
					.getByText( 'Completed' )
					.nth( 1 )
			).toBeVisible();
			await expect(
				page
					.locator( `:is(#order-${ orderId2 }, #post-${ orderId2 })` )
					.getByText( 'Completed' )
					.nth( 1 )
			).toBeVisible();
			await expect(
				page
					.locator( `:is(#order-${ orderId3 }, #post-${ orderId3 })` )
					.getByText( 'Completed' )
					.nth( 1 )
			).toBeVisible();
			await expect(
				page
					.locator( `:is(#order-${ orderId4 }, #post-${ orderId4 })` )
					.getByText( 'Completed' )
					.nth( 1 )
			).toBeVisible();
			await expect(
				page
					.locator( `:is(#order-${ orderId5 }, #post-${ orderId5 })` )
					.getByText( 'Completed' )
					.nth( 1 )
			).toBeVisible();
		} );
	}
);
