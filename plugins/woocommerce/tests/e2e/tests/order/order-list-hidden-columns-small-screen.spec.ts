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
			await page.setViewportSize( SMALL_SCREEN );
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

			// This test verifies the CSS contract of the fix: a small-screen
			// copy is hidden whenever its real column header carries the
			// `hidden` class. We toggle that class directly the way WordPress's
			// Screen Options JS (wp-admin/js/common.js `columns`) does, because
			// the Screen Options panel toggle is not reliably actionable in
			// headless CI below 782px, and hiding a column is core behavior we
			// only react to, not own.
			const setColumnHidden = ( column: string, hidden: boolean ) =>
				page.evaluate(
					( { col, hide } ) => {
						document
							.querySelectorAll(
								`.wp-list-table th.column-${ col }, .wp-list-table td.column-${ col }`
							)
							.forEach( ( cell ) =>
								cell.classList.toggle( 'hidden', hide )
							);
					},
					{ col: column, hide: hidden }
				);

			// Both copies visible by default (both columns shown).
			await expect( dateCopy ).toBeVisible();
			await expect( statusCopy ).toBeVisible();

			// Hide the Date column -> its copy disappears.
			await setColumnHidden( 'order_date', true );
			await expect( dateCopy ).toBeHidden();
			// Status copy is untouched.
			await expect( statusCopy ).toBeVisible();

			// Re-show the Date column -> its copy returns (both directions).
			await setColumnHidden( 'order_date', false );
			await expect( dateCopy ).toBeVisible();

			// Hide the Status column -> its copy disappears.
			await setColumnHidden( 'order_status', true );
			await expect( statusCopy ).toBeHidden();
			await expect( dateCopy ).toBeVisible();
		} );
	}
);
