/**
 * Mutation Batcher E2E Tests
 *
 * Request grouping, state reconciliation and the per-cycle cross-cutting
 * effects are covered by Jest unit tests in
 * `client/blocks/assets/js/base/stores/woocommerce/test/`. What is left here
 * is the one behaviour those cannot reach: a real Interactivity API rollback
 * repainting rendered block markup after a whole batch fails.
 */

/**
 * External dependencies
 */
import { expect, test as base } from '@woocommerce/e2e-utils';

const test = base.extend( {} );

test.describe( 'Mutation Batcher', () => {
	test.beforeEach( async ( { frontendUtils } ) => {
		// The shop page has iAPI product-button blocks, which means the
		// interactivity API and cart store are loaded and hydrated.
		await frontendUtils.goToShop();
	} );

	test( 'total batch failure rolls back product button UI to pre-failure state', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();

		// This project reuses one authenticated user's persistent cart across
		// every test in the file (not a fresh guest cart per test), so empty
		// it first. The assertions below count from an empty cart, and this
		// runs before the batch route is installed so the cleanup requests are
		// not intercepted.
		await page.evaluate( async () => {
			const { store } = await import( '@wordpress/interactivity' );
			const unlockKey =
				'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

			await import( '@woocommerce/stores/woocommerce/cart' );
			const { actions, state } = store(
				'woocommerce',
				{},
				{ lock: unlockKey }
			);

			await actions.refreshCartItems();
			const existingKeys = state.cart.items.map(
				( item: { key: string } ) => item.key
			);
			for ( const key of existingKeys ) {
				await actions.removeCartItem( key );
			}
		} );

		const productButtonBlock = page
			.locator( '.wc-block-components-product-button' )
			.first();
		const button = productButtonBlock.getByRole( 'button' );

		// Click to add one item — this request goes through normally.
		await button.click();
		await expect( button ).toHaveText( '1 in cart' );

		// Intercept batch requests with a delay so we can observe
		// the optimistic state before the failure triggers rollback.
		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const body = route.request().postDataJSON();
			const requestCount = body?.requests?.length || 1;

			await new Promise( ( resolve ) => setTimeout( resolve, 1000 ) );

			await route.fulfill( {
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify( {
					responses: Array.from( { length: requestCount }, () => ( {
						status: 500,
						body: {
							message: 'Simulated server error',
							code: 'internal_error',
						},
					} ) ),
				} ),
			} );
		} );

		// Click twice rapidly — both go through the intercepted batcher.
		await button.click();
		await button.click();

		// The optimistic updates should be visible in the UI.
		await expect( button ).toHaveText( '3 in cart' );

		// After the delayed failure response, the batcher rolls back
		// to the snapshot taken before any optimistic mutations.
		await expect( button ).toHaveText( '1 in cart' );
	} );
} );
