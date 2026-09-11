/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { tags, expect, test } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

test.use( { storageState: ADMIN_STATE_PATH } );

test.describe(
	'Order item quantity validation',
	{ tag: [ tags.SERVICES, tags.HPOS ] },
	() => {
		let orderId: number;
		let productId: number;

		test.beforeAll( async ( { restApi } ) => {
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					name: `Qty validation product ${ Date.now() }`,
					type: 'simple',
					regular_price: '10.00',
				} )
				.then( ( response: { data: { id: number } } ) => {
					productId = response.data.id;
				} );
		} );

		// A fresh order per test: a validation regression that persists a bad
		// quantity must not leak into the other tests' fixtures.
		test.beforeEach( async ( { restApi } ) => {
			await restApi
				.post( `${ WC_API_PATH }/orders`, {
					status: 'pending',
					line_items: [ { product_id: productId, quantity: 2 } ],
				} )
				.then( ( response: { data: { id: number } } ) => {
					orderId = response.data.id;
				} );
		} );

		test.afterEach( async ( { restApi } ) => {
			await restApi.delete( `${ WC_API_PATH }/orders/${ orderId }`, {
				force: true,
			} );
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.delete( `${ WC_API_PATH }/products/${ productId }`, {
				force: true,
			} );
		} );

		test( 'the items panel Save button refuses a negative quantity', async ( {
			page,
		} ) => {
			await page.goto(
				`wp-admin/admin.php?page=wc-orders&action=edit&id=${ orderId }`
			);

			await page.locator( 'a.edit-order-item' ).first().click();
			const qtyInput = page
				.locator( 'input[name^="order_item_qty"]' )
				.first();
			await qtyInput.fill( '-1' );

			await page
				.locator( '#woocommerce-order-items button.save-action' )
				.click();

			// The input reports its constraint violation instead of saving.
			const message = await qtyInput.evaluate(
				( input: HTMLInputElement ) => input.validationMessage
			);
			expect( message ).not.toBe( '' );

			// The negative quantity was not persisted.
			await page.reload();
			await expect(
				page.locator( '#order_line_items td.quantity .view' ).first()
			).toContainText( '2' );
		} );

		test( 'the add products modal blocks a negative quantity and stays open', async ( {
			page,
		} ) => {
			await page.goto(
				`wp-admin/admin.php?page=wc-orders&action=edit&id=${ orderId }`
			);

			await page.locator( 'button.add-line-item' ).click();
			await page.locator( 'button.add-order-item' ).click();

			// The modal wrapper has a zero-size box (its content is positioned
			// absolutely), so visibility is asserted on the content element.
			const modal = page.locator( '.wc-backbone-modal-add-products' );
			const modalContent = modal.locator( '.wc-backbone-modal-content' );
			await expect( modalContent ).toBeVisible();

			await modal
				.locator( 'input[name="item_qty"]' )
				.first()
				.fill( '-3' );
			await modal.locator( '#btn-ok' ).click();

			// Without the fix the modal closes and the request is sent;
			// with it, the modal stays open showing the browser message.
			await expect( modalContent ).toBeVisible();
		} );

		test( 'the Update button shows a validation message for an invalid item quantity', async ( {
			page,
		} ) => {
			await page.goto(
				`wp-admin/admin.php?page=wc-orders&action=edit&id=${ orderId }`
			);

			await page.locator( 'a.edit-order-item' ).first().click();
			const qtyInput = page
				.locator( 'input[name^="order_item_qty"]' )
				.first();
			await qtyInput.fill( '-1' );

			await page.locator( 'button.save_order' ).click();

			// The invalid field is visible and focused with its message —
			// not silently swallowed. Focus is the engine-agnostic proof
			// that the browser reported the field.
			const message = await qtyInput.evaluate(
				( input: HTMLInputElement ) => input.validationMessage
			);
			expect( message ).not.toBe( '' );
			await expect( qtyInput ).toBeVisible();
			await expect( qtyInput ).toBeFocused();
		} );
	}
);
