/**
 * Mutation Batcher E2E Tests
 *
 * These tests call the iAPI cart store directly via page.evaluate to verify
 * batching behavior at the network level. They're designed as regression
 * tests for the mutation batcher — if internals are refactored, these
 * should still pass.
 *
 * KEY IDEA: The batcher uses queueMicrotask() to collect requests.
 * - Synchronous calls within one microtick → batched into 1 request
 * - Calls separated by await → separate batch requests
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

	test( 'synchronous calls are batched into a single request', async ( {
		page,
	} ) => {
		const batchRequests: number[] = [];

		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const body = route.request().postDataJSON();
			batchRequests.push( body?.requests?.length || 0 );
			await route.continue();
		} );

		await page.evaluate( async () => {
			const { store } = await import( '@wordpress/interactivity' );
			const unlockKey =
				'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

			await import( '@woocommerce/stores/woocommerce/cart' );
			const { actions } = store( 'woocommerce', {}, { lock: unlockKey } );

			// Three calls with no await between them — same microtick.
			const p1 = actions.addCartItem( { id: 15, quantityToAdd: 1 } );
			const p2 = actions.addCartItem( { id: 16, quantityToAdd: 1 } );
			const p3 = actions.addCartItem( { id: 17, quantityToAdd: 1 } );

			await Promise.all( [ p1, p2, p3 ] );
		} );

		// All 3 operations should have been sent in a single batch request.
		expect( batchRequests ).toHaveLength( 1 );
		expect( batchRequests[ 0 ] ).toBe( 3 );
	} );

	test( 'awaited calls produce separate batch requests', async ( {
		page,
	} ) => {
		const batchRequests: number[] = [];

		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const body = route.request().postDataJSON();
			batchRequests.push( body?.requests?.length || 0 );
			await route.continue();
		} );

		await page.evaluate( async () => {
			const { store } = await import( '@wordpress/interactivity' );
			const unlockKey =
				'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

			await import( '@woocommerce/stores/woocommerce/cart' );
			const { actions } = store( 'woocommerce', {}, { lock: unlockKey } );

			// Each await breaks the microtick — each call becomes its own batch.
			await actions.addCartItem( { id: 18, quantityToAdd: 1 } );
			await actions.addCartItem( { id: 19, quantityToAdd: 1 } );
			await actions.addCartItem( { id: 20, quantityToAdd: 1 } );
		} );

		// Each call should have produced its own batch request.
		expect( batchRequests ).toHaveLength( 3 );
		batchRequests.forEach( ( count ) => {
			expect( count ).toBe( 1 );
		} );
	} );

	test( 'mixed sync and async calls produce expected batch grouping', async ( {
		page,
	} ) => {
		const batchRequests: number[] = [];

		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const body = route.request().postDataJSON();
			batchRequests.push( body?.requests?.length || 0 );
			await route.continue();
		} );

		await page.evaluate( async () => {
			const { store } = await import( '@wordpress/interactivity' );
			const unlockKey =
				'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

			await import( '@woocommerce/stores/woocommerce/cart' );
			const { actions } = store( 'woocommerce', {}, { lock: unlockKey } );

			// Batch 1: two sync calls
			const p1 = actions.addCartItem( { id: 21, quantityToAdd: 1 } );
			const p2 = actions.addCartItem( { id: 22, quantityToAdd: 1 } );
			await Promise.all( [ p1, p2 ] );

			// Batch 2: one call after await
			await actions.addCartItem( { id: 23, quantityToAdd: 1 } );

			// Batch 3: three sync calls
			const p3 = actions.addCartItem( { id: 24, quantityToAdd: 1 } );
			const p4 = actions.addCartItem( { id: 25, quantityToAdd: 1 } );
			const p5 = actions.addCartItem( { id: 26, quantityToAdd: 1 } );
			await Promise.all( [ p3, p4, p5 ] );
		} );

		// Should produce 3 batches: 2, 1, 3 operations respectively.
		expect( batchRequests ).toHaveLength( 3 );
		expect( batchRequests[ 0 ] ).toBe( 2 );
		expect( batchRequests[ 1 ] ).toBe( 1 );
		expect( batchRequests[ 2 ] ).toBe( 3 );
	} );

	test( 'cart state is correct after batched operations', async ( {
		page,
	} ) => {
		const cartItemIds = await page.evaluate( async () => {
			const { store } = await import( '@wordpress/interactivity' );
			const unlockKey =
				'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

			await import( '@woocommerce/stores/woocommerce/cart' );
			const { actions, state } = store(
				'woocommerce',
				{},
				{ lock: unlockKey }
			);

			// Refresh to start with known state.
			await actions.refreshCartItems();

			// Remove all existing items to start clean.
			const existingKeys = state.cart.items.map(
				( item: { key: string } ) => item.key
			);
			for ( const key of existingKeys ) {
				await actions.removeCartItem( key );
			}

			// Now add 3 products synchronously (one batch).
			const p1 = actions.addCartItem( { id: 15, quantityToAdd: 1 } );
			const p2 = actions.addCartItem( { id: 16, quantityToAdd: 1 } );
			const p3 = actions.addCartItem( { id: 17, quantityToAdd: 1 } );
			await Promise.all( [ p1, p2, p3 ] );

			// Return the product IDs now in the cart.
			return state.cart.items.map( ( item: { id: number } ) => item.id );
		} );

		// All 3 products should be in the cart.
		expect( cartItemIds ).toContain( 15 );
		expect( cartItemIds ).toContain( 16 );
		expect( cartItemIds ).toContain( 17 );
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

	test( 'partial failure in a batch does not prevent successful operations', async ( {
		page,
	} ) => {
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

			// Refresh to get clean state.
			await actions.refreshCartItems();

			// Mix valid and invalid product IDs — all in one microtick.
			const p1 = actions.addCartItem( { id: 15, quantityToAdd: 1 } );
			const p2 = actions.addCartItem( { id: 999999, quantityToAdd: 1 } ); // Invalid
			const p3 = actions.addCartItem( { id: 16, quantityToAdd: 1 } );

			// addCartItem catches errors internally, so all three promises
			// resolve rather than reject — each with its own outcome for
			// that call's product: { success: true } for 15 and 16, or
			// { success: false, error: { code, message } } for the
			// rejected 999999. Promise.allSettled just waits for all three
			// to settle; the accept/reject split is verified below via
			// cart state.
			await Promise.allSettled( [ p1, p2, p3 ] );

			const cartProductIds = state.cart.items.map(
				( item: { id: number } ) => item.id
			);

			return {
				has15: cartProductIds.includes( 15 ),
				has999999: cartProductIds.includes( 999999 ),
				has16: cartProductIds.includes( 16 ),
			};
		} );

		// Valid products should be in cart, invalid should not.
		expect( result.has15 ).toBe( true );
		expect( result.has999999 ).toBe( false );
		expect( result.has16 ).toBe( true );

		// Should still have been sent as a single batch.
		expect( batchRequests ).toHaveLength( 1 );
		expect( batchRequests[ 0 ] ).toBe( 3 );
	} );

	test.describe( 'cross-cutting side effects fire once per cycle', () => {
		// Cross-cutting effects (the `wc-blocks_store_sync_required` sync
		// event and the legacy `wc-blocks_added_to_cart` event) are wired to
		// the batcher's per-cycle settlement hook, not to each individual
		// request. These tests lock that contract in: no matter how many
		// mutations land in one microtick/cycle, each effect fires at most
		// once for that cycle.

		test( 'N concurrent successful adds fire each effect exactly once', async ( {
			page,
		} ) => {
			// This project reuses one authenticated user's persistent cart
			// across every test in the file (not a fresh guest cart per
			// test), so start from a known-clean cart in a preliminary
			// evaluate call, *before* the batch-request route is installed —
			// otherwise the cleanup's own request(s) would pollute the count
			// below.
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

				// Register listeners before the gesture.
				let syncEvents = 0;
				window.addEventListener(
					'wc-blocks_store_sync_required',
					( event ) => {
						if (
							( event as CustomEvent ).detail?.type ===
							'from_iAPI'
						) {
							syncEvents++;
						}
					}
				);
				let addedToCartEvents = 0;
				document.body.addEventListener(
					'wc-blocks_added_to_cart',
					() => {
						addedToCartEvents++;
					}
				);

				// Three concurrent adds for three distinct products, no
				// await between them — one microtick, one cycle.
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

			// One batch request carrying all three mutations.
			expect( batchRequests ).toHaveLength( 1 );
			expect( batchRequests[ 0 ] ).toBe( 3 );

			// Exactly one of each cross-cutting effect for the whole cycle.
			expect( result.syncEvents ).toBe( 1 );
			expect( result.addedToCartEvents ).toBe( 1 );

			// All three products landed in the cart.
			expect( result.cartProductIds ).toContain( 15 );
			expect( result.cartProductIds ).toContain( 16 );
			expect( result.cartProductIds ).toContain( 17 );
		} );

		test( 'a concurrent remove-only cycle fires one sync event and no legacy event', async ( {
			page,
		} ) => {
			const batchRequests: number[] = [];

			await page.route( '**/wc/store/v1/batch**', async ( route ) => {
				const body = route.request().postDataJSON();
				batchRequests.push( body?.requests?.length || 0 );
				await route.continue();
			} );

			// Seed a clean cart with several server-keyed lines before the
			// gesture under test, outside the batch-request count below.
			const seededKeys = await page.evaluate( async () => {
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

				await actions.addCartItem( { id: 15, quantityToAdd: 1 } );
				await actions.addCartItem( { id: 16, quantityToAdd: 1 } );
				await actions.addCartItem( { id: 17, quantityToAdd: 1 } );

				return state.cart.items.map(
					( item: { key: string } ) => item.key
				);
			} );

			// Only count the batch request produced by the gesture below.
			batchRequests.length = 0;

			const result = await page.evaluate( async ( keys ) => {
				const { store } = await import( '@wordpress/interactivity' );
				const unlockKey =
					'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

				await import( '@woocommerce/stores/woocommerce/cart' );
				const { actions, state } = store(
					'woocommerce',
					{},
					{ lock: unlockKey }
				);

				// Register listeners before the gesture.
				let syncEvents = 0;
				window.addEventListener(
					'wc-blocks_store_sync_required',
					( event ) => {
						if (
							( event as CustomEvent ).detail?.type ===
							'from_iAPI'
						) {
							syncEvents++;
						}
					}
				);
				let addedToCartEvents = 0;
				document.body.addEventListener(
					'wc-blocks_added_to_cart',
					() => {
						addedToCartEvents++;
					}
				);

				// Remove every seeded line concurrently — one microtick,
				// one cycle, no adds at all.
				const removals = keys.map( ( key: string ) =>
					actions.removeCartItem( key )
				);
				await Promise.all( removals );

				return {
					syncEvents,
					addedToCartEvents,
					cartProductIds: state.cart.items.map(
						( item: { id: number } ) => item.id
					),
				};
			}, seededKeys );

			// One batch request carrying every removal.
			expect( batchRequests ).toHaveLength( 1 );
			expect( batchRequests[ 0 ] ).toBe( seededKeys.length );

			// One sync event, but the remove-only cycle never triggers the
			// legacy added-to-cart event.
			expect( result.syncEvents ).toBe( 1 );
			expect( result.addedToCartEvents ).toBe( 0 );

			// The targeted lines are gone from the cart.
			expect( result.cartProductIds ).not.toContain( 15 );
			expect( result.cartProductIds ).not.toContain( 16 );
			expect( result.cartProductIds ).not.toContain( 17 );
		} );

		test( 'a mixed successful/failed add cycle fires each effect once and preserves the failed item', async ( {
			page,
		} ) => {
			// This project reuses one authenticated user's persistent cart
			// across every test in the file (not a fresh guest cart per
			// test), so start from a known-clean cart in a preliminary
			// evaluate call, *before* the batch-request route is installed —
			// otherwise the cleanup's own request(s) would pollute the count
			// below.
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

				// Register listeners before the gesture.
				let syncEvents = 0;
				window.addEventListener(
					'wc-blocks_store_sync_required',
					( event ) => {
						if (
							( event as CustomEvent ).detail?.type ===
							'from_iAPI'
						) {
							syncEvents++;
						}
					}
				);
				let addedToCartEvents = 0;
				document.body.addEventListener(
					'wc-blocks_added_to_cart',
					() => {
						addedToCartEvents++;
					}
				);

				// Two valid adds and one invalid product id, no await
				// between them — one microtick, one cycle.
				const p1 = actions.addCartItem( { id: 15, quantityToAdd: 1 } );
				const p2 = actions.addCartItem( {
					id: 999999,
					quantityToAdd: 1,
				} ); // Invalid.
				const p3 = actions.addCartItem( { id: 16, quantityToAdd: 1 } );

				// addCartItem catches errors internally, so all three
				// promises resolve rather than reject; Promise.allSettled
				// just waits for all three to settle.
				await Promise.allSettled( [ p1, p2, p3 ] );

				return {
					syncEvents,
					addedToCartEvents,
					cartProductIds: state.cart.items.map(
						( item: { id: number } ) => item.id
					),
				};
			} );

			// One batch request carrying all three mutations.
			expect( batchRequests ).toHaveLength( 1 );
			expect( batchRequests[ 0 ] ).toBe( 3 );

			// Exactly one of each cross-cutting effect for the whole cycle,
			// since at least one add succeeded.
			expect( result.syncEvents ).toBe( 1 );
			expect( result.addedToCartEvents ).toBe( 1 );

			// The valid products landed in the cart, the invalid one did not.
			expect( result.cartProductIds ).toContain( 15 );
			expect( result.cartProductIds ).toContain( 16 );
			expect( result.cartProductIds ).not.toContain( 999999 );
		} );
	} );
} );
