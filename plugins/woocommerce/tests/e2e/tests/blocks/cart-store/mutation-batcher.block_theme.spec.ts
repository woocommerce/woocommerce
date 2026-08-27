/**
 * Mutation Batcher E2E Tests
 *
 * The batcher's grouping, reconciliation and per-cycle side effects are unit
 * tested against the cart store in
 * `client/blocks/assets/js/base/stores/woocommerce/test/cart.ts` and against
 * the queue itself in `.../test/mutation-batcher.test.ts`. What remains here
 * is the part those cannot reach: a rollback surfacing in the markup of a
 * server-rendered iAPI block.
 */

/**
 * External dependencies
 */
import { expect, test as base } from '@woocommerce/e2e-utils';

const test = base.extend( {} );

test.describe( 'Mutation Batcher', () => {
	test( 'total batch failure rolls back product button UI to pre-failure state', async ( {
		page,
		frontendUtils,
	} ) => {
		// The shop page has iAPI product-button blocks, which means the
		// interactivity API and cart store are loaded and hydrated.
		await frontendUtils.goToShop();

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
