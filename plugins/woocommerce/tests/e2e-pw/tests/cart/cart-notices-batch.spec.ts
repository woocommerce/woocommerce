/**
 * Tests for cart notice centralization via the mutation batcher commit hook.
 *
 * Covers:
 * - No false "Quantity changed" notices during rapid add/remove in same tick.
 * - Mixed batch (add + remove) emits exactly one notice update from commit.
 * - Server-auto-adjusted quantity DOES produce a notice.
 * - Rollback on total batch failure produces no info notices.
 */

import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { addAProductToCart } from '@woocommerce/e2e-utils-playwright';

const SIMPLE_PRODUCT_NAME = 'Simple Product';
const SECOND_PRODUCT_NAME = 'Simple Product 2';

test.describe( 'Cart notices — centralized batch commit', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllCartItems();
	} );

	test( 'no false "Quantity changed to 1" notice on rapid delete and re-add', async ( {
		page,
		frontendUtils,
	} ) => {
		await addAProductToCart( page, SIMPLE_PRODUCT_NAME );
		await frontendUtils.goToMiniCart();

		const cartItem = page.locator(
			`.wc-block-mini-cart__products-table [data-title="${ SIMPLE_PRODUCT_NAME }"]`
		);
		await expect( cartItem ).toBeVisible();

		// Remove the item.
		await page
			.locator( `.wc-block-cart-item__remove-link` )
			.first()
			.click();

		// Immediately re-add the same product before server responds.
		await addAProductToCart( page, SIMPLE_PRODUCT_NAME );

		// Wait for the cart to settle.
		await page.waitForFunction(
			() =>
				! document
					.querySelector( '.wc-block-mini-cart__button' )
					?.classList.contains( 'is-loading' )
		);

		// Must not contain a "Quantity changed" notice.
		const noticesArea = page.locator(
			'.wc-block-store-notices, .wc-block-components-notices'
		);
		await expect( noticesArea ).not.toContainText(
			'was changed to',
			{ timeout: 5000 }
		);
	} );

	test( 'mixed batch: add + remove in one tick emits notices only once', async ( {
		page,
		frontendUtils,
		requestUtils,
	} ) => {
		await requestUtils.addToCart( { id: 1, quantity: 1 } );
		await page.reload();
		await frontendUtils.goToMiniCart();

		// Intercept updateNotices calls by counting store-notices mutations.
		const noticeMutations: string[] = [];
		await page.exposeFunction(
			'__recordNotice',
			( text: string ) => void noticeMutations.push( text )
		);

		await page.addScriptTag( {
			content: `
				const observer = new MutationObserver( () => {
					const text = document.querySelector(
						'.wc-block-store-notices, .wc-block-components-notices'
					)?.textContent ?? '';
					window.__recordNotice( text );
				} );
				const target = document.querySelector(
					'.wc-block-store-notices, .wc-block-components-notices'
				);
				if ( target ) {
					observer.observe( target, { childList: true, subtree: true, characterData: true } );
				}
			`,
		} );

		// Trigger remove and add nearly simultaneously.
		await page
			.locator( '.wc-block-cart-item__remove-link' )
			.first()
			.click();
		await addAProductToCart( page, SECOND_PRODUCT_NAME );

		// Wait for the cart to settle.
		await page.waitForFunction(
			() =>
				! document
					.querySelector( '.wc-block-mini-cart__button' )
					?.classList.contains( 'is-loading' )
		);

		// noticeMutations should have at most one distinct non-empty text snapshot,
		// meaning commit fired once and pushed a single notice update.
		const distinctNonEmpty = [
			...new Set( noticeMutations.filter( ( t ) => t.trim() ) ),
		];
		expect( distinctNonEmpty.length ).toBeLessThanOrEqual( 1 );
	} );

	test( 'server auto-quantity adjustment produces a notice', async ( {
		page,
		requestUtils,
		frontendUtils,
	} ) => {
		// Set stock to 1 so the server caps the quantity.
		await requestUtils.updateProduct( {
			id: 1,
			manage_stock: true,
			stock_quantity: 1,
		} );

		await addAProductToCart( page, SIMPLE_PRODUCT_NAME );
		await frontendUtils.goToMiniCart();

		// Attempt to set quantity to 5 — server should cap to 1.
		await page.locator( '.wc-block-cart-item__quantity input' ).fill( '5' );
		await page.locator( '.wc-block-cart-item__quantity input' ).blur();

		await expect(
			page.locator(
				'.wc-block-store-notices, .wc-block-components-notices'
			)
		).toContainText( 'was changed to', { timeout: 5000 } );

		// Restore stock.
		await requestUtils.updateProduct( {
			id: 1,
			manage_stock: false,
		} );
	} );

	test( 'total batch failure produces no info notices and rolls back state', async ( {
		page,
		frontendUtils,
	} ) => {
		await addAProductToCart( page, SIMPLE_PRODUCT_NAME );
		await frontendUtils.goToMiniCart();

		// Intercept the batch request and force a network failure.
		await page.route( '**/wc/store/v1/batch', ( route ) =>
			route.abort( 'failed' )
		);

		await page
			.locator( '.wc-block-cart-item__remove-link' )
			.first()
			.click();

		await page.waitForSelector(
			`.wc-block-mini-cart__products-table [data-title="${ SIMPLE_PRODUCT_NAME }"]`,
			{ state: 'visible' }
		);

		// No info notice should appear on pure network failure.
		const noticesArea = page.locator(
			'.wc-block-store-notices, .wc-block-components-notices'
		);
		await expect( noticesArea ).not.toContainText( 'was removed', {
			timeout: 3000,
		} );

		// Cart should be rolled back — item still visible.
		const cartItem = page.locator(
			`.wc-block-mini-cart__products-table [data-title="${ SIMPLE_PRODUCT_NAME }"]`
		);
		await expect( cartItem ).toBeVisible();

		await page.unroute( '**/wc/store/v1/batch' );
	} );
} );
