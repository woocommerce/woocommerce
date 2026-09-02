/**
 * Mutation Batcher E2E Tests
 *
 * These tests call the iAPI cart store directly via page.evaluate to verify
 * the browser-owned optimistic rollback and event-delivery contracts.
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

		const productButtonBlock = page
			.locator( '.wc-block-components-product-button' )
			.first();
		const button = productButtonBlock.getByRole( 'button' );

		// Click to add one item — this request goes through normally.
		await button.click();
		await expect( button ).toHaveText( '1 in cart' );
		await page.evaluate( async () => {
			const { store } = await import( '@wordpress/interactivity' );
			const unlockKey =
				'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

			await import( '@woocommerce/stores/woocommerce/cart' );
			const { actions } = store( 'woocommerce', {}, { lock: unlockKey } );
			await actions.waitForIdle();
		} );

		let markIntercepted!: () => void;
		let releaseBatch!: () => void;
		const intercepted = new Promise< void >( ( resolve ) => {
			markIntercepted = resolve;
		} );
		const released = new Promise< void >( ( resolve ) => {
			releaseBatch = resolve;
		} );

		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const body = route.request().postDataJSON();
			markIntercepted();
			await released;

			await route.fulfill( {
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify( {
					responses: Array.from(
						{ length: body?.requests?.length || 1 },
						() => ( {
							status: 500,
							body: {
								message: 'Simulated server error',
								code: 'internal_error',
							},
						} )
					),
				} ),
			} );
		} );

		try {
			// Click twice rapidly — both go through the intercepted batcher.
			await button.click();
			await button.click();
			await intercepted;

			// The optimistic updates should be visible before the response.
			await expect( button ).toHaveText( '3 in cart' );
		} finally {
			releaseBatch();
		}

		// The failed response rolls back to the pre-mutation snapshot.
		await expect( button ).toHaveText( '1 in cart' );
	} );

	test.describe( 'cross-cutting side effects fire once per cycle', () => {
		test( 'N concurrent successful adds fire each effect exactly once', async ( {
			page,
		} ) => {
			// This project reuses one authenticated user's persistent cart, so
			// clean it before installing the route that counts the test gesture.
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

			const batchRequests: number[] = [];

			await page.route( '**/wc/store/v1/batch**', async ( route ) => {
				const body = route.request().postDataJSON();
				batchRequests.push( body?.requests?.length || 0 );
				await route.continue();
			} );

			const result = await page.evaluate( async () => {
				const { store } = await import( '@wordpress/interactivity' );
				const unlockKey =
					'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

				await import( '@woocommerce/stores/woocommerce/cart' );
				const { actions, state } = store(
					'woocommerce',
					{},
					{ lock: unlockKey }
				);

				const syncEvents: Array< {
					targetIsWindow: boolean;
					type?: string;
				} > = [];
				window.addEventListener(
					'wc-blocks_store_sync_required',
					( event ) => {
						syncEvents.push( {
							targetIsWindow: event.target === window,
							type: ( event as CustomEvent ).detail?.type,
						} );
					}
				);
				const addedToCartEvents: Array< {
					targetIsBody: boolean;
					bubbles: boolean;
				} > = [];
				document.body.addEventListener(
					'wc-blocks_added_to_cart',
					( event ) => {
						addedToCartEvents.push( {
							targetIsBody: event.target === document.body,
							bubbles: event.bubbles,
						} );
					}
				);

				// Three synchronous calls enter one mutation cycle and Batch.
				const p1 = actions.addCartItem( { id: 15, quantityToAdd: 1 } );
				const p2 = actions.addCartItem( { id: 16, quantityToAdd: 1 } );
				const p3 = actions.addCartItem( { id: 17, quantityToAdd: 1 } );
				await Promise.all( [ p1, p2, p3 ] );

				return {
					syncEvents,
					addedToCartEvents,
					cartProductIds: state.cart.items.map(
						( item: { id: number } ) => item.id
					),
				};
			} );

			expect( batchRequests ).toEqual( [ 3 ] );
			expect( result.syncEvents ).toEqual( [
				{ targetIsWindow: true, type: 'from_iAPI' },
			] );
			expect( result.addedToCartEvents ).toEqual( [
				{ targetIsBody: true, bubbles: true },
			] );
			expect( result.cartProductIds ).toEqual(
				expect.arrayContaining( [ 15, 16, 17 ] )
			);
			expect( result.cartProductIds ).toHaveLength( 3 );
		} );
	} );
} );
