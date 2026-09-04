/**
 * External dependencies
 */
import type { Cart, CartItem } from '@woocommerce/types';
import type { Notice } from '@woocommerce/stores/store-notices';
import { getConfig } from '@wordpress/interactivity';
import { speak } from '@wordpress/a11y';

/**
 * Internal dependencies
 */
import type { Store, OptimisticCartItem, AddCartItemOutcome } from '../cart';
import { triggerAddedToCartEvent } from '../legacy-events';

type MockStore = { state: Store[ 'state' ]; actions: Store[ 'actions' ] };

let mockRegisteredStore: MockStore | null = null;
const mockState = {
	restUrl: 'https://example.com/wp-json/',
	nonce: 'test-nonce-123',
} as Store[ 'state' ];

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: jest.fn(),
		store: jest.fn( ( _name, definition ) => {
			// The cart store calls `store()` twice: once to read `state` and
			// once to register `actions`. Merge the definition's `state`
			// descriptors (e.g. the `findItemInCart` selector) onto the shared
			// mock state so the real selector runs against seeded cart lines,
			// and carry the action generators through both calls.
			if ( definition?.state ) {
				Object.defineProperties(
					mockState,
					Object.getOwnPropertyDescriptors( definition.state )
				);
			}
			mockRegisteredStore = {
				state: mockState,
				actions: definition?.actions ?? mockRegisteredStore?.actions,
			} as MockStore;
			return mockRegisteredStore;
		} ),
	} ),
	{ virtual: true }
);

jest.mock( '../legacy-events', () => ( {
	triggerAddedToCartEvent: jest.fn(),
} ) );

jest.mock( '@wordpress/a11y', () => ( {
	speak: jest.fn(),
} ) );

/**
 * Captured representation of a single mutation sent through the batch endpoint.
 */
type CapturedRequest = {
	/** The Store API path the mutation targeted, e.g. `/wc/store/v1/cart/add-item`. */
	path: string;
	/** The HTTP method of the mutation. */
	method: string;
	/** The parsed JSON body posted for the mutation. */
	body: OptimisticCartItem;
};

/**
 * Drives an Interactivity API async action generator to completion.
 *
 * Async actions are typed as `void` (or a resolved value) for consumers but
 * are generators internally. Each yielded value is awaited (resolving the
 * batched cart request and any dynamic imports) and the resolved value is fed
 * back into the generator until it is done.
 *
 * When a yielded promise rejects, the rejection is routed back into the
 * generator via `iterator.throw()` (mirroring the real Interactivity runtime),
 * so the action's own `try/catch` runs — e.g. `addCartItem` catching a capped
 * request and emitting an error notice. A rejection the generator does not
 * catch re-throws here so `await runAction(...)` still rejects.
 *
 * @param action The async action return value cast to a generator.
 * @return A promise resolving to the generator's final (`done`) return value.
 */
async function runAction( action: unknown ): Promise< unknown > {
	const iterator = action as Generator< unknown, unknown, unknown >;
	let next = iterator.next();
	while ( ! next.done ) {
		try {
			// eslint-disable-next-line no-await-in-loop
			const resolved = await next.value;
			next = iterator.next( resolved );
		} catch ( error ) {
			// Feed the rejection into the generator so its try/catch handles it.
			next = iterator.throw( error );
		}
	}
	return next.value;
}

/**
 * Installs a `global.fetch` mock that records every mutation routed through the
 * batch endpoint and replies with canned successful responses.
 *
 * The mock answers two request shapes the store issues:
 * - The initial `GET /cart` refresh (no request body): returns an empty cart
 *   with a `Nonce` header so the store's nonce-ready gate resolves and queued
 *   mutations are allowed to flush.
 * - The batch `POST` (a `{ requests: [...] }` body): records each mutation and
 *   replies with one successful `responses` entry per request, each carrying
 *   the current optimistic cart as the server state. Echoing the optimistic
 *   cart makes the mutation queue commit (rather than roll back), so the
 *   optimistic line changes the action applied survive reconciliation.
 *
 * @return The array that accumulates captured mutation requests.
 */
function mockBatchFetch(): CapturedRequest[] {
	const captured: CapturedRequest[] = [];
	global.fetch = jest.fn(
		async ( _url: RequestInfo | URL, init?: RequestInit ) => {
			// The GET refresh has no body; reply with an empty cart and a nonce.
			if ( ! init?.body ) {
				return new Response(
					JSON.stringify( { items: [], totals: {}, errors: [] } ),
					{ headers: { Nonce: 'test-nonce-123' } }
				);
			}
			const parsed = JSON.parse( init.body as string ) as {
				requests: CapturedRequest[];
			};
			parsed.requests.forEach( ( request ) => captured.push( request ) );
			// Echo the post-optimistic cart so the queue commits it as the
			// server state instead of rolling back.
			const serverCart = JSON.parse( JSON.stringify( mockState.cart ) );
			const responses = parsed.requests.map( () => ( {
				status: 200,
				body: serverCart,
			} ) );
			return new Response( JSON.stringify( { responses } ), {
				headers: { Nonce: 'test-nonce-123' },
			} );
		}
	) as unknown as typeof fetch;
	return captured;
}

/**
 * Loads a fresh copy of the cart store, resolves its nonce gate, and returns
 * its registered actions.
 *
 * The module is re-required in isolation so each test starts from a clean
 * mutation queue and a fresh module-level nonce-ready promise. The initial
 * `refreshCartItems()` is then driven to completion so that the singleton
 * nonce-ready promise resolves and queued mutations are allowed to flush; tests
 * seed `state.cart` afterwards via {@link seedCart}.
 *
 * @return A promise resolving to the freshly registered cart store actions.
 */
async function loadCartStore(): Promise< Store[ 'actions' ] > {
	jest.isolateModules( () => require( '../cart' ) );
	const actions = mockRegisteredStore?.actions as Store[ 'actions' ];
	// Drive the refresh so the module-level nonce-ready promise resolves.
	await runAction( actions.refreshCartItems() );
	return actions;
}

/**
 * Seeds the shared mock state with the provided cart lines.
 *
 * @param items The cart lines to expose via `state.cart.items`.
 */
function seedCart( items: ( CartItem | OptimisticCartItem )[] ): void {
	mockState.cart = {
		items,
		totals: {},
		errors: [],
	} as unknown as Store[ 'state' ][ 'cart' ];
}

/**
 * Builds a minimal successful server cart payload from the provided lines.
 *
 * @param items The cart lines the server should report.
 * @return A cart object shaped like a successful Store API cart response.
 */
function makeServerCart( items: CartItem[] ): Cart {
	return {
		items,
		totals: {},
		errors: [],
	} as unknown as Cart;
}

/**
 * Installs a `global.fetch` mock whose batch responses return a caller-supplied
 * server cart instead of echoing the post-optimistic cart.
 *
 * This lets a test reproduce a server response that diverges from the
 * optimistic state — e.g. a keyless add that the server resolves as a brand new
 * standalone line while leaving a matched keyed meta line at its pre-add
 * quantity. Each successful batch response carries `serverCart` as its body, so
 * it becomes the action's `result.data` used for the notice diff.
 *
 * @param serverCart The cart the batch endpoint should report as server state.
 */
function mockBatchFetchReturning( serverCart: Cart ): void {
	global.fetch = jest.fn(
		async ( _url: RequestInfo | URL, init?: RequestInit ) => {
			// The GET refresh has no body; reply with an empty cart and a nonce.
			if ( ! init?.body ) {
				return new Response(
					JSON.stringify( { items: [], totals: {}, errors: [] } ),
					{ headers: { Nonce: 'test-nonce-123' } }
				);
			}
			const parsed = JSON.parse( init.body as string ) as {
				requests: CapturedRequest[];
			};
			const responses = parsed.requests.map( () => ( {
				status: 200,
				body: serverCart,
			} ) );
			return new Response( JSON.stringify( { responses } ), {
				headers: { Nonce: 'test-nonce-123' },
			} );
		}
	) as unknown as typeof fetch;
}

/**
 * Replaces the registered `updateNotices` action with a spy and returns the
 * flat list of notices it receives across all invocations.
 *
 * The cart actions funnel every info/error notice through
 * `actions.updateNotices`, resolved by property access at call time on the
 * registered actions object. The caller `yield`s the result, and {@link
 * runAction} only `await`s each yielded value; a yielded generator object would
 * not be driven, so the spy records synchronously at call time and returns
 * `undefined`. The spy is installed after {@link loadCartStore}.
 *
 * @return The accumulating list of notices passed to `updateNotices`.
 */
function spyOnUpdateNotices(): Notice[] {
	const received: Notice[] = [];
	const actions = mockRegisteredStore?.actions as Store[ 'actions' ];
	actions.updateNotices = jest.fn( ( notices: Notice[] = [] ) => {
		received.push( ...notices );
		return undefined;
	} ) as unknown as Store[ 'actions' ][ 'updateNotices' ];
	return received;
}

/**
 * Replaces the registered `showNoticeError` action with a spy and returns the
 * list of errors it receives.
 *
 * The error/`catch` path of the cart actions surfaces a failed mutation by
 * calling `actions.showNoticeError( error )` — the error-notice boundary,
 * distinct from the auto-update info-notice boundary `actions.updateNotices`.
 * Asserting on this spy proves a genuine cap surfaced as an error notice rather
 * than an auto-update notice. The spy is installed after {@link loadCartStore}.
 *
 * @return The accumulating list of errors passed to `showNoticeError`.
 */
function spyOnShowNoticeError(): Error[] {
	const received: Error[] = [];
	const actions = mockRegisteredStore?.actions as Store[ 'actions' ];
	actions.showNoticeError = jest.fn( ( error: Error ) => {
		received.push( error );
		return undefined;
	} ) as unknown as Store[ 'actions' ][ 'showNoticeError' ];
	return received;
}

/**
 * Installs a `global.fetch` mock whose batch responses reject one targeted
 * mutation with an HTTP error status, reproducing a genuine server cap.
 *
 * The GET refresh still resolves the nonce gate. Each batch request whose body
 * targets `failForPath` gets a non-2xx response entry carrying the supplied
 * error `code`/`message`; every other request echoes the post-optimistic cart
 * as a success. A failed entry makes the mutation queue roll the optimistic
 * change back (no successful server state for it) and reject that request's
 * promise, which surfaces through the action's `catch` path.
 *
 * @param options             Failure configuration.
 * @param options.failForPath The Store API path whose mutation should fail,
 *                            e.g. `/wc/store/v1/cart/add-item`.
 * @param options.status      The HTTP status to report for the failed mutation.
 * @param options.code        The error code carried in the failed response body.
 * @param options.message     The human-readable error message in the body.
 * @return The array that accumulates captured mutation requests.
 */
function mockBatchFetchFailing( {
	failForPath,
	status = 400,
	code = 'woocommerce_rest_cart_product_no_stock',
	message = 'You cannot add that amount to the cart.',
}: {
	failForPath: string;
	status?: number;
	code?: string;
	message?: string;
} ): CapturedRequest[] {
	const captured: CapturedRequest[] = [];
	global.fetch = jest.fn(
		async ( _url: RequestInfo | URL, init?: RequestInit ) => {
			// The GET refresh has no body; reply with an empty cart and a nonce.
			if ( ! init?.body ) {
				return new Response(
					JSON.stringify( { items: [], totals: {}, errors: [] } ),
					{ headers: { Nonce: 'test-nonce-123' } }
				);
			}
			const parsed = JSON.parse( init.body as string ) as {
				requests: CapturedRequest[];
			};
			parsed.requests.forEach( ( request ) => captured.push( request ) );
			const serverCart = JSON.parse( JSON.stringify( mockState.cart ) );
			const responses = parsed.requests.map( ( request ) =>
				request.path === failForPath
					? { status, body: { code, message } }
					: { status: 200, body: serverCart }
			);
			return new Response( JSON.stringify( { responses } ), {
				headers: { Nonce: 'test-nonce-123' },
			} );
		}
	) as unknown as typeof fetch;
	return captured;
}

/**
 * Installs a `global.fetch` mock whose batch responses reject only the
 * mutation(s) targeting a specific product id, letting every other mutation
 * in the same batch succeed.
 *
 * Unlike {@link mockBatchFetchFailing} (which fails every request matching a
 * given path), this discriminates by the posted product id so two mutations
 * hitting the same endpoint (e.g. two `add-item` calls) can settle
 * differently within one batch — reproducing one accepted and one rejected
 * product in the same request cycle.
 *
 * @param options           Failure configuration.
 * @param options.failForId The product id whose mutation(s) should fail.
 * @param options.status    The HTTP status to report for the failed mutation.
 * @param options.code      The error code carried in the failed response body.
 * @param options.message   The human-readable error message in the body.
 */
function mockBatchFetchFailingProduct( {
	failForId,
	status = 400,
	code = 'woocommerce_rest_cart_product_no_stock',
	message = 'You cannot add that amount to the cart.',
}: {
	failForId: number;
	status?: number;
	code?: string;
	message?: string;
} ): void {
	global.fetch = jest.fn(
		async ( _url: RequestInfo | URL, init?: RequestInit ) => {
			// The GET refresh has no body; reply with an empty cart and a nonce.
			if ( ! init?.body ) {
				return new Response(
					JSON.stringify( { items: [], totals: {}, errors: [] } ),
					{ headers: { Nonce: 'test-nonce-123' } }
				);
			}
			const parsed = JSON.parse( init.body as string ) as {
				requests: CapturedRequest[];
			};
			const serverCart = JSON.parse( JSON.stringify( mockState.cart ) );
			const responses = parsed.requests.map( ( request ) =>
				request.body.id === failForId
					? { status, body: { code, message } }
					: { status: 200, body: serverCart }
			);
			return new Response( JSON.stringify( { responses } ), {
				headers: { Nonce: 'test-nonce-123' },
			} );
		}
	) as unknown as typeof fetch;
}

/**
 * Installs a `global.fetch` mock whose batch request fails entirely — a
 * non-2xx response to the outer `/batch` POST itself, not a per-item
 * rejection — reproducing a whole-batch/transport failure with no
 * per-product server error code.
 *
 * The GET refresh still resolves the nonce gate. The batch POST resolves with
 * a non-2xx HTTP status, which the mutation queue reports as one `Error`
 * (lacking a `code` property) shared by every tracked request in the cycle.
 */
function mockBatchFetchWholeBatchFailure(): void {
	global.fetch = jest.fn(
		async ( _url: RequestInfo | URL, init?: RequestInit ) => {
			// The GET refresh has no body; reply with an empty cart and a nonce.
			if ( ! init?.body ) {
				return new Response(
					JSON.stringify( { items: [], totals: {}, errors: [] } ),
					{ headers: { Nonce: 'test-nonce-123' } }
				);
			}
			// The whole batch request fails at the HTTP level (no per-item
			// responses are ever parsed).
			return new Response( 'Internal Server Error', { status: 500 } );
		}
	) as unknown as typeof fetch;
}

/**
 * The `quantityChanges` shape carried by the sync event's detail. Mirrors the
 * cart module's internal (unexported) `QuantityChanges` type.
 */
type QuantityChangesLike = {
	cartItemsPendingQuantity?: string[];
	cartItemsPendingDelete?: string[];
	productsPendingAdd?: number[];
};

/**
 * Installs a listener that captures every `wc-blocks_store_sync_required`
 * event dispatched on `window`, in dispatch order.
 *
 * @return An object with the accumulating `events` list and a `cleanup`
 *         function the caller must invoke once the test is done asserting,
 *         so the listener does not leak into later tests.
 */
function captureSyncEvents(): {
	events: Array< { type: string; quantityChanges: QuantityChangesLike } >;
	cleanup: () => void;
} {
	const events: Array< {
		type: string;
		quantityChanges: QuantityChangesLike;
	} > = [];
	const listener = ( event: Event ) => {
		events.push(
			(
				event as CustomEvent< {
					type: string;
					quantityChanges: QuantityChangesLike;
				} >
			 ).detail
		);
	};
	window.addEventListener( 'wc-blocks_store_sync_required', listener );
	return {
		events,
		cleanup: () =>
			window.removeEventListener(
				'wc-blocks_store_sync_required',
				listener
			),
	};
}

/**
 * Waits for a macrotask boundary, letting every microtask (promise
 * `.then`/`.catch` chain) queued so far run to completion.
 *
 * Used after driving an action to its generator's `done` return value, since
 * the screen-reader announcement may still be chained on the a11y module's
 * import promise and settle in a microtask queued during — but not
 * necessarily flushed by the end of — the action's own settlement.
 *
 * @return A promise that resolves after the next macrotask tick.
 */
function flushMicrotasks(): Promise< void > {
	return new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
}

/**
 * Builds a minimal server-confirmed cart line carrying a key.
 *
 * @param overrides Partial cart-line fields to override the defaults.
 * @return A cart line suitable for seeding `state.cart.items`.
 */
function makeKeyedLine( overrides: Partial< CartItem > = {} ): CartItem {
	return {
		key: 'server-key-abc',
		id: 42,
		type: 'simple',
		quantity: 3,
		name: 'Test Product',
		sold_individually: false,
		variation: [],
		item_data: [],
		...overrides,
	} as CartItem;
}

describe( 'WooCommerce Cart Interactivity API Store', () => {
	afterEach( () => {
		jest.clearAllMocks();
		// `getConfig` may have been given a per-test `mockReturnValue` (e.g. to
		// configure `addedToCartText`); `clearAllMocks` only clears call
		// history, so reset it explicitly to avoid leaking config into later
		// tests.
		( getConfig as jest.Mock ).mockReset();
		delete ( mockState as Partial< Store[ 'state' ] > ).cart;
	} );

	it( 'refreshCartItems passes cache: no-store to fetch to prevent browser caching', () => {
		const mockFetch = jest
			.fn()
			.mockResolvedValue(
				new Response(
					JSON.stringify( { items: [], totals: {}, errors: [] } )
				)
			);
		global.fetch = mockFetch;

		jest.isolateModules( () => require( '../cart' ) );

		const iterator = mockRegisteredStore?.actions.refreshCartItems();

		// Async actions are typed as void for consumers, but are actually generators internally.
		( iterator as unknown as Iterator< void > ).next();

		expect( mockFetch ).toHaveBeenCalledWith(
			'https://example.com/wp-json/wc/store/v1/cart',
			expect.objectContaining( {
				method: 'GET',
				cache: 'no-store',
			} )
		);
	} );

	describe( 'addCartItem endpoint selection', () => {
		it( 'issues add-item (never update-item) for a keyless add that matches a keyed line by product id', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await runAction(
				actions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);

			expect( captured ).toHaveLength( 1 );
			expect( captured[ 0 ].path ).toBe( '/wc/store/v1/cart/add-item' );
			expect( captured[ 0 ].path ).not.toContain( 'update-item' );
		} );

		it( 'posts the requested delta (not the matched line absolute quantity) for a keyless add against a keyed line', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await runAction(
				actions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);

			expect( captured[ 0 ].body.quantity ).toBe( 1 );
			expect( captured[ 0 ].body.quantity ).not.toBe( 4 );
		} );

		it( 'accumulates the running optimistic delta across rapid keyless adds', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			// Two rapid keyless adds queued before the batch flushes. Each must
			// post its own delta (1) computed against the running optimistic
			// quantity, never an absolute quantity off the matched line.
			await Promise.all( [
				runAction(
					actions.addCartItem( {
						id: 42,
						quantityToAdd: 1,
						type: 'simple',
					} )
				),
				runAction(
					actions.addCartItem( {
						id: 42,
						quantityToAdd: 1,
						type: 'simple',
					} )
				),
			] );

			expect( captured ).toHaveLength( 2 );
			expect(
				captured.every( ( r ) => r.path.endsWith( 'add-item' ) )
			).toBe( true );
			expect( captured[ 0 ].body.quantity ).toBe( 1 );
			expect( captured[ 1 ].body.quantity ).toBe( 1 );
		} );

		it( 'never includes the matched line key in the request body for a keyless add', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( { id: 42, quantity: 3, key: 'server-key-abc' } ),
			] );

			await runAction(
				actions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);

			expect( captured[ 0 ].body.key ).toBeUndefined();
		} );

		it( 'issues update-item with the absolute quantity for an explicit key (key-first path unchanged)', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( { id: 42, quantity: 3, key: 'server-key-abc' } ),
			] );

			await runAction(
				actions.addCartItem( {
					id: 42,
					key: 'server-key-abc',
					quantity: 5,
					type: 'simple',
				} )
			);

			expect( captured[ 0 ].path ).toBe(
				'/wc/store/v1/cart/update-item'
			);
			expect( captured[ 0 ].body.quantity ).toBe( 5 );
			expect( captured[ 0 ].body.key ).toBe( 'server-key-abc' );
		} );

		it( 'optimistically bumps a matched keyed line in place on a keyless re-add (no duplicate line)', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await runAction(
				actions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);

			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ].quantity ).toBe( 4 );
		} );

		it( 'optimistically pushes a new line when no line matches a keyless add', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await runAction(
				actions.addCartItem( {
					id: 99,
					quantityToAdd: 2,
					type: 'simple',
				} )
			);

			expect( mockState.cart.items ).toHaveLength( 2 );
			const added = mockState.cart.items.find(
				( item ) => item.id === 99
			);
			expect( added ).toBeDefined();
			expect( added?.quantity ).toBe( 2 );
		} );

		it( 'ignores the matched line item_data when deciding the endpoint and body for a keyless add', async () => {
			const captured = mockBatchFetch();

			// Same product id and quantity, only item_data differs. The
			// add/update decision must not depend on item_data, so both adds
			// must produce an identical endpoint and request body.
			const richItemData = [
				{ key: 'subscription', value: 'monthly' },
			] as CartItem[ 'item_data' ];

			const withEmptyItemData = await loadCartStore();
			seedCart( [
				makeKeyedLine( { id: 42, quantity: 3, item_data: [] } ),
			] );
			await runAction(
				withEmptyItemData.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);

			const withRichItemData = await loadCartStore();
			seedCart( [
				makeKeyedLine( {
					id: 42,
					quantity: 3,
					item_data: richItemData,
				} ),
			] );
			await runAction(
				withRichItemData.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);

			expect( captured ).toHaveLength( 2 );
			expect( captured[ 0 ].path ).toBe( '/wc/store/v1/cart/add-item' );
			expect( captured[ 1 ].path ).toBe( captured[ 0 ].path );
			expect( captured[ 1 ].body ).toEqual( captured[ 0 ].body );
		} );
	} );

	describe( 'addCartItem keyless-requires-delta invariant guard', () => {
		it( 'throws when called keyless with an absolute quantity (no quantityToAdd)', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await expect(
				runAction(
					actions.addCartItem( {
						id: 42,
						quantity: 5,
						type: 'simple',
					} )
				)
			).rejects.toThrow();
		} );

		it( 'does not throw and proceeds on the add-item path for a keyless quantityToAdd delta', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await expect(
				runAction(
					actions.addCartItem( {
						id: 42,
						quantityToAdd: 1,
						type: 'simple',
					} )
				)
			).resolves.toEqual( { success: true } );

			expect( captured ).toHaveLength( 1 );
			expect( captured[ 0 ].path ).toBe( '/wc/store/v1/cart/add-item' );
		} );

		it( 'does not throw for an explicit key with an absolute quantity (key-first stepper path unaffected)', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( { id: 42, quantity: 3, key: 'server-key-abc' } ),
			] );

			await expect(
				runAction(
					actions.addCartItem( {
						id: 42,
						key: 'server-key-abc',
						quantity: 5,
						type: 'simple',
					} )
				)
			).resolves.toEqual( { success: true } );
		} );

		it( 'still throws when both quantity and quantityToAdd are passed together', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await expect(
				runAction(
					actions.addCartItem( {
						id: 42,
						quantity: 5,
						quantityToAdd: 1,
						type: 'simple',
					} )
				)
			).rejects.toThrow();
		} );
	} );

	describe( 'addCartItem resolved outcome', () => {
		it( 'resolves { success: true } when the Store API accepts the request', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			const outcome = await runAction(
				actions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);

			expect( outcome ).toEqual( { success: true } );
		} );

		it( 'resolves { success: false, error: { code, message } } carrying the server-supplied code and message on a per-item rejection, without the promise rejecting', async () => {
			mockBatchFetchFailing( {
				failForPath: '/wc/store/v1/cart/add-item',
				status: 400,
				code: 'woocommerce_rest_product_out_of_stock',
				message: 'You cannot add that amount to the cart.',
			} );
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );
			spyOnShowNoticeError();

			const outcome = await runAction(
				actions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);

			expect( outcome ).toEqual( {
				success: false,
				error: {
					code: 'woocommerce_rest_product_out_of_stock',
					message: 'You cannot add that amount to the cart.',
				},
			} );
		} );

		it( 'resolves { success: false, error } with a non-empty message and no code on a whole-batch/transport failure', async () => {
			mockBatchFetchWholeBatchFailure();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );
			spyOnShowNoticeError();

			const outcome = ( await runAction(
				actions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'simple',
				} )
			) ) as AddCartItemOutcome;

			if ( outcome.success ) {
				throw new Error(
					'expected a failure outcome for a whole-batch/transport failure'
				);
			}
			expect( typeof outcome.error.message ).toBe( 'string' );
			expect( outcome.error.message.length ).toBeGreaterThan( 0 );
			expect( outcome.error.code ).toBeUndefined();
		} );

		it( 'resolves each call in a shared batch with only its own product outcome, never a shared or last-write-wins value', async () => {
			mockBatchFetchFailingProduct( {
				failForId: 99,
				code: 'woocommerce_rest_cart_product_no_stock',
				message: 'You cannot add that amount to the cart.',
			} );
			const actions = await loadCartStore();
			seedCart( [] );
			spyOnShowNoticeError();

			const [ acceptedOutcome, rejectedOutcome ] = await Promise.all( [
				runAction(
					actions.addCartItem( {
						id: 42,
						quantityToAdd: 1,
						type: 'simple',
					} )
				),
				runAction(
					actions.addCartItem( {
						id: 99,
						quantityToAdd: 1,
						type: 'simple',
					} )
				),
			] );

			expect( acceptedOutcome ).toEqual( { success: true } );
			expect( rejectedOutcome ).toEqual( {
				success: false,
				error: {
					code: 'woocommerce_rest_cart_product_no_stock',
					message: 'You cannot add that amount to the cart.',
				},
			} );
		} );

		it( 'still resolves { success: true } when a step after the successful request throws (post-success client bug)', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );
			spyOnShowNoticeError();
			// Simulate a client bug in post-success processing (e.g. a notices
			// import rejection or a11y chunk-load failure): the captured
			// success outcome must survive this throw.
			actions.updateNotices = jest.fn( () => {
				throw new Error( 'post-success client bug' );
			} ) as unknown as Store[ 'actions' ][ 'updateNotices' ];

			const outcome = await runAction(
				actions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);

			expect( outcome ).toEqual( { success: true } );
		} );

		it( 'resolves { success: true } and omits unrelated cart.errors from the outcome', async () => {
			mockBatchFetchReturning( {
				items: [ makeKeyedLine( { id: 42, quantity: 4 } ) ],
				totals: {},
				errors: [
					{
						code: 'woocommerce_rest_cart_coupon_error',
						message: 'The coupon has expired.',
					},
				],
			} as unknown as Cart );
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			const outcome = await runAction(
				actions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);

			expect( outcome ).toEqual( { success: true } );
		} );
	} );

	describe( 'batchAddCartItems endpoint selection', () => {
		it( 'issues add-item (never update-item) for a keyless batch item that matches a keyed line by product id', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await runAction(
				actions.batchAddCartItems( [
					{
						id: 42,
						quantityToAdd: 1,
						type: 'simple',
					},
				] )
			);

			expect( captured ).toHaveLength( 1 );
			expect( captured[ 0 ].path ).toBe( '/wc/store/v1/cart/add-item' );
			expect( captured[ 0 ].path ).not.toContain( 'update-item' );
		} );

		it( 'posts the requested delta (not the matched line absolute quantity) for a keyless batch item against a keyed line', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await runAction(
				actions.batchAddCartItems( [
					{
						id: 42,
						quantityToAdd: 1,
						type: 'simple',
					},
				] )
			);

			expect( captured[ 0 ].body.quantity ).toBe( 1 );
			expect( captured[ 0 ].body.quantity ).not.toBe( 4 );
		} );

		it( 'never includes the matched line key in the request body for a keyless batch item', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( { id: 42, quantity: 3, key: 'server-key-abc' } ),
			] );

			await runAction(
				actions.batchAddCartItems( [
					{
						id: 42,
						quantityToAdd: 1,
						type: 'simple',
					},
				] )
			);

			expect( captured[ 0 ].body.key ).toBeUndefined();
		} );

		it( 'issues update-item with the absolute quantity for a batch item that supplies an explicit key', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( { id: 42, quantity: 3, key: 'server-key-abc' } ),
			] );

			await runAction(
				actions.batchAddCartItems( [
					{
						id: 42,
						key: 'server-key-abc',
						quantity: 5,
						type: 'simple',
					},
				] )
			);

			expect( captured[ 0 ].path ).toBe(
				'/wc/store/v1/cart/update-item'
			);
			expect( captured[ 0 ].body.quantity ).toBe( 5 );
			expect( captured[ 0 ].body.key ).toBe( 'server-key-abc' );
		} );

		it( 'derives the keyless add-item delta identically to the single-item addCartItem path', async () => {
			// Same seeded keyed line and same keyless request through both
			// paths must produce the same endpoint and posted quantity.
			const singleCaptured = mockBatchFetch();
			const singleActions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );
			await runAction(
				singleActions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);

			const batchCaptured = mockBatchFetch();
			const batchActions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );
			await runAction(
				batchActions.batchAddCartItems( [
					{
						id: 42,
						quantityToAdd: 1,
						type: 'simple',
					},
				] )
			);

			expect( batchCaptured ).toHaveLength( 1 );
			expect( singleCaptured ).toHaveLength( 1 );
			expect( batchCaptured[ 0 ].path ).toBe( singleCaptured[ 0 ].path );
			expect( batchCaptured[ 0 ].body.quantity ).toBe(
				singleCaptured[ 0 ].body.quantity
			);
			expect( batchCaptured[ 0 ].body.key ).toBe(
				singleCaptured[ 0 ].body.key
			);
		} );

		it( 'optimistically bumps a matched keyed line in place on a keyless batch re-add (no duplicate line)', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await runAction(
				actions.batchAddCartItems( [
					{
						id: 42,
						quantityToAdd: 1,
						type: 'simple',
					},
				] )
			);

			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ].quantity ).toBe( 4 );
		} );

		it( 'optimistically pushes a new line when no line matches a keyless batch item', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await runAction(
				actions.batchAddCartItems( [
					{
						id: 99,
						quantityToAdd: 2,
						type: 'simple',
					},
				] )
			);

			expect( mockState.cart.items ).toHaveLength( 2 );
			const added = mockState.cart.items.find(
				( item ) => item.id === 99
			);
			expect( added ).toBeDefined();
			expect( added?.quantity ).toBe( 2 );
		} );
	} );

	describe( 'notice-diff suppression for keyless meta-only adds', () => {
		// The quantity-changed info notice template the auto-UPDATE branch emits.
		const QUANTITY_CHANGED = 'was changed to';

		it( 'emits no quantity-changed notice for a keyless add resolved server-side as a new standalone line', async () => {
			// The product is present only as a single keyed meta line at qty 3.
			// A keyless add optimistically bumps that line to 4, but the server
			// keeps the meta line at 3 and adds a separate standalone line. The
			// keyless-scoped baseline (3) must be compared against the server
			// quantity (3) so no spurious "quantity changed" notice fires.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-abc',
						id: 42,
						quantity: 3,
					} ),
					makeKeyedLine( {
						key: 'server-key-new',
						id: 42,
						quantity: 1,
					} ),
				] )
			);
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( { key: 'server-key-abc', id: 42, quantity: 3 } ),
			] );
			const notices = spyOnUpdateNotices();

			await runAction(
				actions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);

			expect(
				notices.some( ( n ) => n.notice.includes( QUANTITY_CHANGED ) )
			).toBe( false );
		} );

		it( 'emits no quantity-changed notice when only the first of two meta lines for the same product is bumped optimistically', async () => {
			// The product is present as two distinct keyed meta lines (qty 3 and
			// qty 2). A keyless add matches and optimistically bumps only the
			// first line (server-key-1) to 4. The server keeps both meta lines at
			// their pre-add quantities and adds a separate standalone line. The
			// bumped line's pre-optimistic baseline (3) must be diffed against the
			// server quantity (3) so no spurious notice fires; the untouched
			// second line (still 2 in both snapshots) must not notify either.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-1',
						id: 42,
						quantity: 3,
					} ),
					makeKeyedLine( {
						key: 'server-key-2',
						id: 42,
						quantity: 2,
					} ),
					makeKeyedLine( {
						key: 'server-key-new',
						id: 42,
						quantity: 1,
					} ),
				] )
			);
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( { key: 'server-key-1', id: 42, quantity: 3 } ),
				makeKeyedLine( { key: 'server-key-2', id: 42, quantity: 2 } ),
			] );
			const notices = spyOnUpdateNotices();

			await runAction(
				actions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);

			expect(
				notices.some( ( n ) => n.notice.includes( QUANTITY_CHANGED ) )
			).toBe( false );
		} );

		it( 'still emits the quantity-changed notice for a keyed mini-cart stepper change returned at its pre-stepper quantity', async () => {
			// A keyed update (explicit key + absolute quantity) is never recorded
			// in the keyless baseline set, so the override does not apply. The
			// server returning the line at its pre-stepper quantity (3) must still
			// diff against the post-optimistic snapshot (5) and notify.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-abc',
						id: 42,
						quantity: 3,
					} ),
				] )
			);
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( { key: 'server-key-abc', id: 42, quantity: 3 } ),
			] );
			const notices = spyOnUpdateNotices();

			await runAction(
				actions.addCartItem( {
					id: 42,
					key: 'server-key-abc',
					quantity: 5,
					type: 'simple',
				} )
			);

			expect(
				notices.some( ( n ) => n.notice.includes( QUANTITY_CHANGED ) )
			).toBe( true );
		} );

		it( 'still emits the quantity-changed notice when a keyless-add-bumped line diverges from its captured baseline', async () => {
			// A genuine concurrent server change: the matched keyed line is
			// reported at quantity 7, which differs from its pre-optimistic
			// baseline of 3. The notice must still fire for that line.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-abc',
						id: 42,
						quantity: 7,
					} ),
				] )
			);
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( { key: 'server-key-abc', id: 42, quantity: 3 } ),
			] );
			const notices = spyOnUpdateNotices();

			await runAction(
				actions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);

			expect(
				notices.some(
					( n ) =>
						n.notice.includes( QUANTITY_CHANGED ) &&
						n.notice.includes( '7' )
				)
			).toBe( true );
		} );

		it( 'suppresses the notice for a keyless batch add resolved server-side as a new standalone line', async () => {
			// Same meta-only scenario through the batch path: the matched keyed
			// line is bumped optimistically to 4, the server keeps it at 3 and
			// adds a standalone line. The batch must capture the baseline (3) and
			// suppress the spurious notice.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-abc',
						id: 42,
						quantity: 3,
					} ),
					makeKeyedLine( {
						key: 'server-key-new',
						id: 42,
						quantity: 1,
					} ),
				] )
			);
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( { key: 'server-key-abc', id: 42, quantity: 3 } ),
			] );
			const notices = spyOnUpdateNotices();

			await runAction(
				actions.batchAddCartItems( [
					{
						id: 42,
						quantityToAdd: 1,
						type: 'simple',
					},
				] )
			);

			expect(
				notices.some( ( n ) => n.notice.includes( QUANTITY_CHANGED ) )
			).toBe( false );
		} );

		it( 'suppresses the quantity-changed notice for a keyless re-add when the server returns the line at pre-add + delta', async () => {
			// Pre-add: matched line at qty 3. Keyless add delta: +1.
			// Expected total: 3 + 1 = 4. Server returns the line at 4.
			// Since serverTotal (4) === expectedTotal (4), the add was exact →
			// no "quantity changed" notice must fire.
			// This also indirectly guards the by-value pre-add capture: if
			// preAddTotal were captured after the optimistic bump (reading 4
			// instead of 3), expectedTotal would be 4+1=5 ≠ server 4, which
			// would keep the key un-suppressed and fire the notice, failing
			// this assertion.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-abc',
						id: 42,
						quantity: 4,
					} ),
				] )
			);
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( { key: 'server-key-abc', id: 42, quantity: 3 } ),
			] );
			const notices = spyOnUpdateNotices();

			await runAction(
				actions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);

			// Server total (4) === expected total (3+1=4) → suppress.
			// No "quantity changed" notice must fire.
			expect(
				notices.some( ( n ) => n.notice.includes( QUANTITY_CHANGED ) )
			).toBe( false );
		} );

		it( 'suppresses the quantity-changed notice for a keyless batch re-add when the server total matches pre-add + sum of deltas', async () => {
			// Pre-add: matched line at qty 3. Batch deltas: +1 and +1.
			// A real /batch endpoint compounds server-side: each add-item
			// sub-request runs sequentially against one WC_Cart session, so the
			// server accumulates both deltas and lands at 5, not 4.
			// Expected total: 3 + (1+1) = 5. Server returns the line at 5.
			// Since serverTotal (5) === expectedTotal (5), suppress → no notice.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-abc',
						id: 42,
						quantity: 5,
					} ),
				] )
			);
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( { key: 'server-key-abc', id: 42, quantity: 3 } ),
			] );
			const notices = spyOnUpdateNotices();

			await runAction(
				actions.batchAddCartItems( [
					{ id: 42, quantityToAdd: 1, type: 'simple' },
					{ id: 42, quantityToAdd: 1, type: 'simple' },
				] )
			);

			// Server total (5) === expected total (3+1+1=5) → suppress.
			// No "quantity changed" notice must fire.
			expect(
				notices.some( ( n ) => n.notice.includes( QUANTITY_CHANGED ) )
			).toBe( false );
		} );

		it( 'still emits the quantity-changed notice for a keyless batch re-add when the server total diverges from expected', async () => {
			// Same setup: pre-add qty 3, batch (+1,+1), expectedTotal = 5.
			// Server returns 6 (a genuine concurrent change or cap artefact).
			// Since serverTotal (6) !== expectedTotal (5), do not suppress →
			// the notice must fire reporting the server quantity 6.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-abc',
						id: 42,
						quantity: 6,
					} ),
				] )
			);
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( { key: 'server-key-abc', id: 42, quantity: 3 } ),
			] );
			const notices = spyOnUpdateNotices();

			await runAction(
				actions.batchAddCartItems( [
					{ id: 42, quantityToAdd: 1, type: 'simple' },
					{ id: 42, quantityToAdd: 1, type: 'simple' },
				] )
			);

			expect(
				notices.some(
					( n ) =>
						n.notice.includes( QUANTITY_CHANGED ) &&
						n.notice.includes( '6' )
				)
			).toBe( true );
		} );

		it( 'suppresses the notice for a keyless add when the client bumps a meta line but the server grows the standalone line', async () => {
			// Product 42 occupies two lines: a meta-differentiated line ordered
			// first (server-key-meta, qty 1) and a plain standalone line second
			// (server-key-standalone, qty 1). findItemInCart matches the meta line
			// first, so addCartItem bumps it optimistically. The server, however,
			// grows the standalone line instead and leaves the meta line unchanged.
			// Pre-add total: 1+1=2. Delta: +1. Expected total: 3.
			// Server returns meta(1) + standalone(2) = 3 === expected → suppress
			// for both pre-existing keys. No "quantity changed" notice must fire.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-meta',
						id: 42,
						quantity: 1,
						name: 'Test Product',
					} ),
					makeKeyedLine( {
						key: 'server-key-standalone',
						id: 42,
						quantity: 2,
						name: 'Test Product',
					} ),
				] )
			);
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( {
					key: 'server-key-meta',
					id: 42,
					quantity: 1,
				} ),
				makeKeyedLine( {
					key: 'server-key-standalone',
					id: 42,
					quantity: 1,
				} ),
			] );
			const notices = spyOnUpdateNotices();

			await runAction(
				actions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);

			// Server total (1+2=3) === expected total (1+1+1=3) → suppress.
			expect(
				notices.some( ( n ) => n.notice.includes( QUANTITY_CHANGED ) )
			).toBe( false );
		} );

		it( 'suppresses the notice for a keyless batch add when the client bumps a meta line but the server grows the standalone line, through the batch path', async () => {
			// Same meta-line/standalone-line scenario through the batch path.
			// Product 42 occupies two
			// lines: meta first (qty 1) then standalone (qty 1). The batch item
			// bumps the meta line optimistically; the server grows the standalone
			// line. Pre-add total: 1+1=2. Delta: +1. Expected total: 3.
			// Server returns meta(1)+standalone(2)=3 === expected → suppress.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-meta',
						id: 42,
						quantity: 1,
						name: 'Test Product',
					} ),
					makeKeyedLine( {
						key: 'server-key-standalone',
						id: 42,
						quantity: 2,
						name: 'Test Product',
					} ),
				] )
			);
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( {
					key: 'server-key-meta',
					id: 42,
					quantity: 1,
				} ),
				makeKeyedLine( {
					key: 'server-key-standalone',
					id: 42,
					quantity: 1,
				} ),
			] );
			const notices = spyOnUpdateNotices();

			await runAction(
				actions.batchAddCartItems( [
					{ id: 42, quantityToAdd: 1, type: 'simple' },
				] )
			);

			// Server total (1+2=3) === expected total (1+1+1=3) → suppress.
			expect(
				notices.some( ( n ) => n.notice.includes( QUANTITY_CHANGED ) )
			).toBe( false );
		} );

		it( 'suppresses the quantity-changed notice for a keyless variation re-add when the server returns the line at pre-add + delta', async () => {
			// A variation line (type: variation, id: 42, variation: [Color:Red])
			// is matched by id+variation. Keyless add delta: +1. Pre-add qty: 2.
			// Expected total: 2+1=3. Server returns the variation line at 3.
			// Since serverTotal (3) === expectedTotal (3) → suppress.
			const colorRedVariation = [
				{ attribute: 'Color', value: 'Red' },
			] as CartItem[ 'variation' ];
			mockBatchFetchReturning(
				makeServerCart( [
					{
						...makeKeyedLine( {
							key: 'server-key-var',
							id: 42,
							quantity: 3,
						} ),
						type: 'variation',
						variation: colorRedVariation,
					} as CartItem,
				] )
			);
			const actions = await loadCartStore();
			seedCart( [
				{
					...makeKeyedLine( {
						key: 'server-key-var',
						id: 42,
						quantity: 2,
					} ),
					type: 'variation',
					variation: colorRedVariation,
				} as CartItem,
			] );
			const notices = spyOnUpdateNotices();

			await runAction(
				actions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'variation',
					variation: colorRedVariation,
				} )
			);

			// Server total (3) === expected total (2+1=3) → suppress.
			expect(
				notices.some( ( n ) => n.notice.includes( QUANTITY_CHANGED ) )
			).toBe( false );
		} );

		it( 'leaves removeCartItem notice behavior unchanged (auto-DELETE still fires)', async () => {
			// removeCartItem must not pass the new baseline; the auto-DELETE
			// branch is untouched. Removing one of two lines while the server
			// reports the OTHER line auto-removed must still emit a removal
			// notice for that server-removed line.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-keep',
						id: 42,
						quantity: 3,
						name: 'Kept Product',
					} ),
				] )
			);
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( {
					key: 'server-key-keep',
					id: 42,
					quantity: 3,
					name: 'Kept Product',
				} ),
				makeKeyedLine( {
					key: 'server-key-gone',
					id: 7,
					quantity: 1,
					name: 'Vanished Product',
				} ),
			] );
			const notices = spyOnUpdateNotices();

			await runAction( actions.removeCartItem( 'server-key-keep' ) );

			// The server-removed line (server-key-gone) was present in the
			// post-optimistic snapshot and absent from the server cart, so the
			// auto-DELETE notice must still fire.
			expect(
				notices.some( ( n ) => n.notice.includes( 'Vanished Product' ) )
			).toBe( true );
		} );
	} );

	describe( 'genuine add-path cap surfaces as an error notice (not an auto-update notice)', () => {
		// The quantity-changed info notice template the auto-UPDATE branch emits.
		const QUANTITY_CHANGED = 'was changed to';

		it( 'routes an HTTP 400 add-item failure to an error notice and never to an auto-update notice', async () => {
			// A plain keyless re-add the server caps (e.g. out of stock) returns a
			// non-2xx batch entry. That rejects the mutation, so the action takes
			// the throw/catch path: the failure must surface as an error notice
			// via showNoticeError, not as an auto-update "quantity changed" notice
			// through updateNotices.
			mockBatchFetchFailing( {
				failForPath: '/wc/store/v1/cart/add-item',
				status: 400,
				code: 'woocommerce_rest_cart_product_no_stock',
				message: 'You cannot add that amount to the cart.',
			} );
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );
			const autoUpdateNotices = spyOnUpdateNotices();
			const errors = spyOnShowNoticeError();

			await runAction(
				actions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);

			// The cap surfaced through the error-notice boundary carrying the
			// server-supplied message and code.
			expect( errors ).toHaveLength( 1 );
			expect( errors[ 0 ].message ).toBe(
				'You cannot add that amount to the cart.'
			);
			expect( ( errors[ 0 ] as Error & { code?: string } ).code ).toBe(
				'woocommerce_rest_cart_product_no_stock'
			);

			// No auto-update "quantity changed" notice was emitted for the cap.
			expect(
				autoUpdateNotices.some( ( n ) =>
					n.notice.includes( QUANTITY_CHANGED )
				)
			).toBe( false );
		} );

		it( 'rolls the optimistic bump back when the add-item request is capped (HTTP 400)', async () => {
			// The optimistic update bumps the matched line 3 -> 4 before the
			// request flushes. Because the only mutation fails, the queue has no
			// successful server state and must roll the cart back to its
			// pre-cycle snapshot, leaving the line at its original quantity 3.
			mockBatchFetchFailing( {
				failForPath: '/wc/store/v1/cart/add-item',
				status: 400,
			} );
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );
			spyOnShowNoticeError();

			await runAction(
				actions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);

			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ].quantity ).toBe( 3 );
		} );
	} );

	describe( 'onCycleSettled cross-cutting effects', () => {
		it( 'dispatches exactly one sync event, one legacy event, and one announcement for a single successful addCartItem call', async () => {
			( getConfig as jest.Mock ).mockReturnValue( {
				messages: { addedToCartText: 'Added to your cart.' },
			} );
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [] );
			const { events, cleanup } = captureSyncEvents();

			await runAction(
				actions.addCartItem( {
					id: 1,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);
			await flushMicrotasks();

			expect( events ).toHaveLength( 1 );
			expect( events[ 0 ].type ).toBe( 'from_iAPI' );
			expect( triggerAddedToCartEvent ).toHaveBeenCalledTimes( 1 );
			expect( triggerAddedToCartEvent ).toHaveBeenCalledWith( {
				preserveCartData: true,
			} );
			expect( speak ).toHaveBeenCalledTimes( 1 );
			expect( speak ).toHaveBeenCalledWith(
				'Added to your cart.',
				'polite'
			);

			cleanup();
		} );

		it( 'dispatches exactly one sync event, one legacy event, and one announcement for a cycle of concurrent successful addCartItem calls', async () => {
			( getConfig as jest.Mock ).mockReturnValue( {
				messages: { addedToCartText: 'Added to your cart.' },
			} );
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [] );
			const { events, cleanup } = captureSyncEvents();

			await Promise.all( [
				runAction(
					actions.addCartItem( {
						id: 1,
						quantityToAdd: 1,
						type: 'simple',
					} )
				),
				runAction(
					actions.addCartItem( {
						id: 2,
						quantityToAdd: 1,
						type: 'simple',
					} )
				),
				runAction(
					actions.addCartItem( {
						id: 3,
						quantityToAdd: 1,
						type: 'simple',
					} )
				),
			] );
			await flushMicrotasks();

			// Sanity check: all three mutations landed in the same cycle/batch.
			expect( captured ).toHaveLength( 3 );
			expect( events ).toHaveLength( 1 );
			expect( triggerAddedToCartEvent ).toHaveBeenCalledTimes( 1 );
			expect( speak ).toHaveBeenCalledTimes( 1 );

			cleanup();
		} );

		it( 'still dispatches exactly one sync event for a cycle mixing a successful and a failed mutation, and surfaces the failure as its own error notice', async () => {
			( getConfig as jest.Mock ).mockReturnValue( {} );
			mockBatchFetchFailingProduct( { failForId: 99 } );
			const actions = await loadCartStore();
			seedCart( [] );
			const { events, cleanup } = captureSyncEvents();
			const errors = spyOnShowNoticeError();

			const [ acceptedOutcome, rejectedOutcome ] = await Promise.all( [
				runAction(
					actions.addCartItem( {
						id: 42,
						quantityToAdd: 1,
						type: 'simple',
					} )
				),
				runAction(
					actions.addCartItem( {
						id: 99,
						quantityToAdd: 1,
						type: 'simple',
					} )
				),
			] );
			await flushMicrotasks();

			expect( acceptedOutcome ).toEqual( { success: true } );
			expect( ( rejectedOutcome as AddCartItemOutcome ).success ).toBe(
				false
			);
			expect( events ).toHaveLength( 1 );
			expect( errors ).toHaveLength( 1 );

			cleanup();
		} );

		it( 'dispatches no sync event and no announcement when every mutation in the cycle fails, while the failed mutation still surfaces its own error notice', async () => {
			( getConfig as jest.Mock ).mockReturnValue( {
				messages: { addedToCartText: 'Added to your cart.' },
			} );
			mockBatchFetchFailing( {
				failForPath: '/wc/store/v1/cart/add-item',
				status: 400,
			} );
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );
			const { events, cleanup } = captureSyncEvents();
			const errors = spyOnShowNoticeError();

			await runAction(
				actions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);
			await flushMicrotasks();

			expect( events ).toHaveLength( 0 );
			expect( triggerAddedToCartEvent ).not.toHaveBeenCalled();
			expect( speak ).not.toHaveBeenCalled();
			expect( errors ).toHaveLength( 1 );

			cleanup();
		} );

		it( 'unions quantityChanges across successful add, keyed-update, and remove mutations in the same cycle', async () => {
			( getConfig as jest.Mock ).mockReturnValue( {} );
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( {
					key: 'server-key-abc',
					id: 42,
					quantity: 3,
				} ),
				makeKeyedLine( {
					key: 'server-key-gone',
					id: 7,
					quantity: 1,
				} ),
			] );
			const { events, cleanup } = captureSyncEvents();

			await Promise.all( [
				runAction(
					actions.addCartItem( {
						id: 99,
						quantityToAdd: 1,
						type: 'simple',
					} )
				),
				runAction(
					actions.addCartItem( {
						id: 42,
						key: 'server-key-abc',
						quantity: 5,
						type: 'simple',
					} )
				),
				runAction( actions.removeCartItem( 'server-key-gone' ) ),
			] );
			await flushMicrotasks();

			expect( events ).toHaveLength( 1 );
			expect( events[ 0 ].quantityChanges ).toEqual( {
				productsPendingAdd: [ 99 ],
				cartItemsPendingQuantity: [ 'server-key-abc' ],
				cartItemsPendingDelete: [ 'server-key-gone' ],
			} );
			expect( triggerAddedToCartEvent ).toHaveBeenCalledTimes( 1 );

			cleanup();
		} );

		it( 'dedupes a repeated product id in the sync event quantityChanges when the same product succeeds twice in one cycle', async () => {
			( getConfig as jest.Mock ).mockReturnValue( {} );
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [] );
			const { events, cleanup } = captureSyncEvents();

			await Promise.all( [
				runAction(
					actions.addCartItem( {
						id: 42,
						quantityToAdd: 1,
						type: 'simple',
					} )
				),
				runAction(
					actions.addCartItem( {
						id: 42,
						quantityToAdd: 1,
						type: 'simple',
					} )
				),
			] );
			await flushMicrotasks();

			expect( events ).toHaveLength( 1 );
			expect( events[ 0 ].quantityChanges ).toEqual( {
				productsPendingAdd: [ 42 ],
			} );

			cleanup();
		} );

		it( 'dispatches the sync event but not the legacy event or announcement for a successful remove-only cycle', async () => {
			( getConfig as jest.Mock ).mockReturnValue( {
				messages: { addedToCartText: 'Added to your cart.' },
			} );
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( {
					key: 'server-key-abc',
					id: 42,
					quantity: 3,
				} ),
			] );
			const { events, cleanup } = captureSyncEvents();

			await runAction( actions.removeCartItem( 'server-key-abc' ) );
			await flushMicrotasks();

			expect( events ).toHaveLength( 1 );
			expect( events[ 0 ].quantityChanges ).toEqual( {
				cartItemsPendingDelete: [ 'server-key-abc' ],
			} );
			expect( triggerAddedToCartEvent ).not.toHaveBeenCalled();
			expect( speak ).not.toHaveBeenCalled();

			cleanup();
		} );

		it( 'dispatches one merged sync event, one legacy event, and one announcement for a cycle mixing a successful add and a successful remove', async () => {
			( getConfig as jest.Mock ).mockReturnValue( {
				messages: { addedToCartText: 'Added to your cart.' },
			} );
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( {
					key: 'server-key-gone',
					id: 7,
					quantity: 1,
				} ),
			] );
			const { events, cleanup } = captureSyncEvents();

			await Promise.all( [
				runAction(
					actions.addCartItem( {
						id: 99,
						quantityToAdd: 1,
						type: 'simple',
					} )
				),
				runAction( actions.removeCartItem( 'server-key-gone' ) ),
			] );
			await flushMicrotasks();

			expect( events ).toHaveLength( 1 );
			expect( events[ 0 ].quantityChanges ).toEqual( {
				productsPendingAdd: [ 99 ],
				cartItemsPendingDelete: [ 'server-key-gone' ],
			} );
			expect( triggerAddedToCartEvent ).toHaveBeenCalledTimes( 1 );
			expect( speak ).toHaveBeenCalledTimes( 1 );

			cleanup();
		} );

		it( "dispatches exactly one sync/legacy/announcement for a batchAddCartItems call where the last item fails but an earlier item succeeds, unioning only the successful item's changes", async () => {
			( getConfig as jest.Mock ).mockReturnValue( {
				messages: { addedToCartText: 'Added to your cart.' },
			} );
			mockBatchFetchFailingProduct( { failForId: 99 } );
			const actions = await loadCartStore();
			seedCart( [] );
			const { events, cleanup } = captureSyncEvents();
			const notices = spyOnUpdateNotices();

			await runAction(
				actions.batchAddCartItems( [
					{ id: 42, quantityToAdd: 1, type: 'simple' },
					{ id: 99, quantityToAdd: 1, type: 'simple' },
				] )
			);
			await flushMicrotasks();

			expect( events ).toHaveLength( 1 );
			expect( events[ 0 ].quantityChanges ).toEqual( {
				productsPendingAdd: [ 42 ],
			} );
			expect( triggerAddedToCartEvent ).toHaveBeenCalledTimes( 1 );
			expect( speak ).toHaveBeenCalledTimes( 1 );
			// The failed item (id 99) still surfaces its own error notice.
			expect(
				notices.some(
					( n ) => n.type === 'error' && n.notice.includes( '99' )
				) ||
					notices.some(
						( n ) =>
							n.type === 'error' &&
							n.notice.includes( 'cannot add' )
					)
			).toBe( true );

			cleanup();
		} );

		it( 'announces once via the already-resolved a11y binding on a later call in the same store instance', async () => {
			( getConfig as jest.Mock ).mockReturnValue( {
				messages: { addedToCartText: 'Added to your cart.' },
			} );
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [] );

			// First call kicks off — and, given the many awaits already
			// driven through by `runAction`, very likely resolves — the
			// preloaded a11y module import.
			await runAction(
				actions.addCartItem( {
					id: 1,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);
			await flushMicrotasks();

			// A second call's announcement — whether it takes the
			// already-resolved synchronous branch or still has to chain on
			// the import promise — must still fire exactly once more.
			await runAction(
				actions.addCartItem( {
					id: 2,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);
			await flushMicrotasks();

			expect( speak ).toHaveBeenCalledTimes( 2 );
			expect( speak ).toHaveBeenNthCalledWith(
				2,
				'Added to your cart.',
				'polite'
			);
		} );

		it( 'does not let an a11y announcement failure affect the mutation outcome or the already-dispatched sync/legacy events', async () => {
			( getConfig as jest.Mock ).mockReturnValue( {
				messages: { addedToCartText: 'Added to your cart.' },
			} );
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [] );

			// Warm up: resolve the a11y binding via a first successful call.
			await runAction(
				actions.addCartItem( {
					id: 1,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);
			await flushMicrotasks();

			( speak as jest.Mock ).mockImplementation( () => {
				throw new Error( 'a11y unavailable' );
			} );

			const { events, cleanup } = captureSyncEvents();
			const outcome = await runAction(
				actions.addCartItem( {
					id: 2,
					quantityToAdd: 1,
					type: 'simple',
				} )
			);
			await flushMicrotasks();

			expect( outcome ).toEqual( { success: true } );
			expect( events ).toHaveLength( 1 );
			expect( triggerAddedToCartEvent ).toHaveBeenCalledTimes( 2 );

			cleanup();
		} );
	} );
} );
