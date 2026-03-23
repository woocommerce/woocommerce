/**
 * External dependencies
 */
import { ApiClient, WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { tags, expect, test } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

test.use( { storageState: ADMIN_STATE_PATH } );

test.describe(
	'Order meta box button layout at narrow viewports',
	{ tag: [ tags.SERVICES, tags.HPOS ] },
	() => {
		let orderId: number;

		test.beforeAll( async ( { restApi } ) => {
			await restApi
				.post( `${ WC_API_PATH }/orders`, {
					status: 'processing',
				} )
				.then( ( response: { data: { id: number } } ) => {
					orderId = response.data.id;
				} );
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.delete( `${ WC_API_PATH }/orders/${ orderId }`, {
				force: true,
			} );
		} );

		test( 'order item action buttons do not overflow their container at 1200px viewport', async ( {
			page,
		} ) => {
			await page.setViewportSize( { width: 1200, height: 900 } );

			await page.goto(
				`wp-admin/admin.php?page=wc-orders&action=edit&id=${ orderId }`
			);

			// Simulate WP 7.0+ body class.
			await page.evaluate( () => {
				document.body.classList.add( 'wc-wp-version-gte-70' );
			} );

			// Wait for styles to apply.
			await page.waitForTimeout( 100 );

			const addItemsRow = page.locator(
				'#woocommerce-order-items .wc-order-bulk-actions .add-items'
			);
			await expect( addItemsRow ).toBeVisible();

			const container = await addItemsRow.boundingBox();
			const buttons = await addItemsRow
				.locator( '.button' )
				.all();

			// Every button must be within the horizontal bounds of its container.
			for ( const button of buttons ) {
				const box = await button.boundingBox();
				if ( box && container ) {
					expect( box.x + box.width ).toBeLessThanOrEqual(
						container.x + container.width + 1 // 1px tolerance
					);
				}
			}
		} );

		test( 'order actions select and button fit within their container at 1200px viewport', async ( {
			page,
		} ) => {
			await page.setViewportSize( { width: 1200, height: 900 } );

			await page.goto(
				`wp-admin/admin.php?page=wc-orders&action=edit&id=${ orderId }`
			);

			// Simulate WP 7.0+ body class.
			await page.evaluate( () => {
				document.body.classList.add( 'wc-wp-version-gte-70' );
			} );

			await page.waitForTimeout( 100 );

			const actionsContainer = page.locator(
				'.order_actions #actions'
			);
			await expect( actionsContainer ).toBeVisible();

			const containerBox = await actionsContainer.boundingBox();
			const selectBox = await actionsContainer
				.locator( 'select' )
				.boundingBox();
			const buttonBox = await actionsContainer
				.locator( '.button' )
				.boundingBox();

			if ( containerBox && selectBox && buttonBox ) {
				// Select and button should both fit within the container width.
				expect(
					selectBox.width + buttonBox.width
				).toBeLessThanOrEqual( containerBox.width + 1 );
			}
		} );
	}
);
