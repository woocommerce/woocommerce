/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { tags, expect, test } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

// A narrow viewport below the 782px breakpoint that shows the duplicated
// date/status copies inside the order-number cell.
const SMALL_SCREEN = { width: 480, height: 900 };

test.describe(
	'WooCommerce Orders > Hidden columns on small screens',
	{ tag: [ tags.HPOS ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		let orderId: number;

		test.beforeAll( async ( { restApi } ) => {
			// One order is enough to render a row with the copies.
			await restApi
				.post( `${ WC_API_PATH }/orders`, { status: 'processing' } )
				.then( ( response: { data: { id: number } } ) => {
					orderId = response.data.id;
				} );
		} );

		test.afterAll( async ( { restApi } ) => {
			if ( orderId ) {
				await restApi.delete( `${ WC_API_PATH }/orders/${ orderId }`, {
					force: true,
				} );
			}
		} );

		test( 'date/status copies follow the Columns menu below 782px', async ( {
			page,
		} ) => {
			await page.goto( 'wp-admin/admin.php?page=wc-orders' );

			const dateCopy = page
				.locator(
					'td.column-order_number .order_date.small-screen-only'
				)
				.first();
			const statusCopy = page
				.locator(
					'td.column-order_number .order_status.small-screen-only'
				)
				.first();

			// Open Screen Options at desktop width: WordPress hides the toggle
			// below 782px, so it can only be opened before shrinking. The panel
			// stays open across the resize, and the fix reacts to the column's
			// `hidden` class regardless of the width at which it was toggled.
			await page.locator( '#show-settings-link' ).click();

			// Shrink below the 782px breakpoint that renders the copies.
			await page.setViewportSize( SMALL_SCREEN );

			// Both copies visible by default (both columns shown).
			await expect( dateCopy ).toBeVisible();
			await expect( statusCopy ).toBeVisible();

			// Hide the Date column -> its copy disappears live.
			await page.locator( '#order_date-hide' ).uncheck();
			await expect( dateCopy ).toBeHidden();
			// Status copy is untouched.
			await expect( statusCopy ).toBeVisible();

			// Re-show the Date column -> its copy returns live (both directions).
			await page.locator( '#order_date-hide' ).check();
			await expect( dateCopy ).toBeVisible();

			// Hide the Status column -> its copy disappears live.
			await page.locator( '#order_status-hide' ).uncheck();
			await expect( statusCopy ).toBeHidden();
			await expect( dateCopy ).toBeVisible();
		} );
	}
);
