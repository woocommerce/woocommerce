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
import type { Store, OptimisticCartItem, AddCartItemResult } from '../cart';
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
		// Defaults to an empty config so the `addCartItem` a11y-announcement
		// block (`const { messages } = getConfig(...)`) destructures safely
		// when a test doesn't care about the screen-reader announcement.
		// Tests that do care override this once via `mockReturnValueOnce`.
		getConfig: jest.fn( () => ( {} ) ),
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
 * Async actions are typed as `void` for consumers but are generators
 * internally. Each yielded value is awaited (resolving the batched cart
 * request and any dynamic imports) and the resolved value is fed back into the
 * generator until it is done.
 *
 * When a yielded promise rejects, the rejection is routed back into the
 * generator via `iterator.throw()` (mirroring the real Interactivity runtime),
 * so the action's own `try/catch` runs — e.g. `addCartItem` catching a capped
 * request and emitting an error notice. A rejection the generator does not
 * catch re-throws here so `await runAction(...)` still rejects.
 *
 * @param action The async action return value cast to a generator.
 * @return A promise that resolves to the generator's final return value (e.g.
 *         `addCartItem`'s `AddCartItemResult`) once it has finished. Actions
 *         with no explicit `return` resolve `undefined`.
 */
async function runAction< T = void >( action: unknown ): Promise< T > {
	const iterator = action as Generator< unknown, T, unknown >;
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
 * Builds a minimal server-confirmed cart line carrying a key.
 *
 * Defaults `is_standalone_line` to `true` so a plain call produces a
 * standalone line — the kind the product-button count reflects and that
 * `findItemInCart` matches on a keyless lookup. Callers that need a
 * meta-differentiated line (a bundle child, booking, or add-on) must pass
 * `is_standalone_line: false` explicitly as an override.
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
		is_standalone_line: true,
		...overrides,
	} as CartItem;
}

describe( 'WooCommerce Cart Interactivity API Store', () => {
	afterEach( () => {
		jest.clearAllMocks();
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
					} )
				),
				runAction(
					actions.addCartItem( {
						id: 42,
						quantityToAdd: 1,
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
					} )
				)
			).rejects.toThrow();
		} );

		it( 'does not throw and proceeds on the add-item path for a keyless quantityToAdd delta', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await expect(
				runAction< AddCartItemResult >(
					actions.addCartItem( {
						id: 42,
						quantityToAdd: 1,
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
				runAction< AddCartItemResult >(
					actions.addCartItem( {
						id: 42,
						key: 'server-key-abc',
						quantity: 5,
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
					} )
				)
			).rejects.toThrow();
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

		it( 'accumulates optimistic bumps for two keyless batch items targeting the same pre-existing line (no lost update)', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await runAction(
				actions.batchAddCartItems( [
					{ id: 42, quantityToAdd: 1 },
					{ id: 42, quantityToAdd: 1 },
				] )
			);

			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ].quantity ).toBe( 5 );
		} );

		it( 'collapses two keyless batch items for the same not-yet-in-cart product into one optimistic line at quantity 2', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [] );

			await runAction(
				actions.batchAddCartItems( [
					{ id: 99, quantityToAdd: 1 },
					{ id: 99, quantityToAdd: 1 },
				] )
			);

			const matching = mockState.cart.items.filter(
				( item ) => item.id === 99
			);
			expect( matching ).toHaveLength( 1 );
			expect( matching[ 0 ].quantity ).toBe( 2 );
		} );
	} );

	describe( 'no spurious quantity-changed notice on exact keyless adds', () => {
		// The quantity-changed info notice template the auto-UPDATE branch emits.
		const QUANTITY_CHANGED = 'was changed to';

		it( 'emits no quantity-changed notice for a keyless add resolved server-side as a new standalone line', async () => {
			// The cart holds only a meta line (qty 3, is_standalone_line: false).
			// findItemInCart excludes meta lines on a keyless lookup, so no
			// existing line matches and the optimistic update pushes a
			// brand-new keyless line (no key) rather than bumping the meta
			// line. The post-optimistic snapshot is therefore [meta line qty 3
			// (key server-key-abc), new line qty 1 (no key)].
			//
			// The server keeps the meta line unchanged at qty 3 and adds its
			// own new line under a server-assigned key (server-key-new, qty
			// 1). Diffing by key: the meta line's key and quantity match
			// exactly between the two carts, so it does not notify. The
			// server's new line has a key that never appears in the
			// post-optimistic snapshot (the client's pushed line carried no
			// key), so there is nothing to diff it against and it is skipped
			// too. The diff is empty — no notice fires.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-abc',
						id: 42,
						quantity: 3,
						is_standalone_line: false,
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
				makeKeyedLine( {
					key: 'server-key-abc',
					id: 42,
					quantity: 3,
					is_standalone_line: false,
				} ),
			] );
			const notices = spyOnUpdateNotices();

			await runAction(
				actions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
				} )
			);

			expect(
				notices.some( ( n ) => n.notice.includes( QUANTITY_CHANGED ) )
			).toBe( false );
		} );

		it( 'emits no quantity-changed notice when only the first of two meta lines for the same product is bumped optimistically', async () => {
			// The cart holds two meta lines (qty 3 and qty 2, both
			// is_standalone_line: false). findItemInCart excludes both on a
			// keyless lookup, so the optimistic update pushes a brand-new
			// keyless line (no key) rather than bumping either meta line. The
			// post-optimistic snapshot is [meta line 1 qty 3, meta line 2 qty
			// 2, new line qty 1 (no key)].
			//
			// The server keeps both meta lines unchanged and adds its own new
			// line under a server-assigned key. Diffing by key: both meta
			// lines match exactly, and the server's new line has no
			// counterpart key in the post-optimistic snapshot to diff
			// against. No notice fires for any of the three lines.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-1',
						id: 42,
						quantity: 3,
						is_standalone_line: false,
					} ),
					makeKeyedLine( {
						key: 'server-key-2',
						id: 42,
						quantity: 2,
						is_standalone_line: false,
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
				makeKeyedLine( {
					key: 'server-key-1',
					id: 42,
					quantity: 3,
					is_standalone_line: false,
				} ),
				makeKeyedLine( {
					key: 'server-key-2',
					id: 42,
					quantity: 2,
					is_standalone_line: false,
				} ),
			] );
			const notices = spyOnUpdateNotices();

			await runAction(
				actions.addCartItem( {
					id: 42,
					quantityToAdd: 1,
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

		it( 'emits no quantity-changed notice for a keyless batch add resolved server-side as a new standalone line', async () => {
			// Same meta-only scenario through the batch path: the cart has
			// only a single meta line (qty 3, is_standalone_line: false).
			// findItemInCart excludes it, so the optimistic update pushes a
			// brand-new keyless line (no key) instead. The batch's
			// post-optimistic snapshot — captured after the (only, and
			// therefore last) item's optimistic update — is [meta line qty 3,
			// new line qty 1 (no key)].
			//
			// The server keeps the meta line unchanged and adds its own new
			// line under a server-assigned key. Diffing by key: the meta line
			// matches exactly; the server's new line has no counterpart key
			// to diff against. No notice fires.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-abc',
						id: 42,
						quantity: 3,
						is_standalone_line: false,
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
				makeKeyedLine( {
					key: 'server-key-abc',
					id: 42,
					quantity: 3,
					is_standalone_line: false,
				} ),
			] );
			const notices = spyOnUpdateNotices();

			await runAction(
				actions.batchAddCartItems( [
					{
						id: 42,
						quantityToAdd: 1,
					},
				] )
			);

			expect(
				notices.some( ( n ) => n.notice.includes( QUANTITY_CHANGED ) )
			).toBe( false );
		} );

		it( 'emits no quantity-changed notice for a keyless re-add when the server returns the line at pre-add + delta', async () => {
			// The matched line (key server-key-abc) starts at qty 3. A
			// keyless add with delta +1 bumps it in place, so the
			// post-optimistic snapshot has that same key at qty 4. The server
			// confirms the line at qty 4 too — an exact add. Diffing by key
			// finds the same key at the same quantity on both sides, so no
			// notice fires.
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
				} )
			);

			expect(
				notices.some( ( n ) => n.notice.includes( QUANTITY_CHANGED ) )
			).toBe( false );
		} );

		it( 'emits no quantity-changed notice for a keyless batch re-add when the server total matches the post-optimistic quantity', async () => {
			// The matched line (key server-key-abc) starts at qty 3. Two
			// keyless batch items each add a delta of +1. Both deltas are
			// computed against the same pre-batch quantity (3) at .map()
			// time, but applyOptimistic runs sequentially and each bump
			// stacks on the previous one, so the line lands at qty 5 by the
			// time the batch's post-optimistic snapshot is captured (after
			// the last item's applyOptimistic). A real /batch endpoint
			// compounds the same way server-side — each add-item sub-request
			// runs sequentially against one WC_Cart session — so the server
			// also lands on 5. Diffing by key finds the same key at the same
			// quantity (5) on both sides, so no notice fires.
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
					{ id: 42, quantityToAdd: 1 },
					{ id: 42, quantityToAdd: 1 },
				] )
			);

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
					{ id: 42, quantityToAdd: 1 },
					{ id: 42, quantityToAdd: 1 },
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

		it( 'emits no quantity-changed notice for a keyless add when the client bumps a meta line but the server grows the standalone line', async () => {
			// Product 42 occupies two lines: a meta-differentiated line
			// (server-key-meta, qty 1, is_standalone_line: false) and a plain
			// standalone line (server-key-standalone, qty 1). findItemInCart
			// excludes the meta line on a keyless lookup and returns the
			// standalone line directly, so the optimistic update bumps the
			// standalone line in place from 1 to 2. Post-optimistic snapshot:
			// meta line unchanged at 1, standalone line at 2.
			//
			// The server also grows the standalone line to 2 and keeps the
			// meta line at 1 — an exact add. Diffing by key finds both lines
			// at the same quantity on both sides, so no notice fires.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-meta',
						id: 42,
						quantity: 1,
						name: 'Test Product',
						is_standalone_line: false,
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
					is_standalone_line: false,
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
				} )
			);

			expect(
				notices.some( ( n ) => n.notice.includes( QUANTITY_CHANGED ) )
			).toBe( false );
		} );

		it( 'emits no quantity-changed notice for a keyless batch add when the client bumps a meta line but the server grows the standalone line, through the batch path', async () => {
			// Same meta-line/standalone-line scenario through the batch path.
			// Product 42 occupies two lines: meta first (qty 1,
			// is_standalone_line: false) then standalone (qty 1).
			// findItemInCart excludes the meta line and returns the
			// standalone line, so the (only, and therefore last) batch item
			// bumps the standalone line optimistically from 1 to 2. The
			// post-optimistic snapshot — captured after that item's
			// optimistic update — has the meta line unchanged at 1 and the
			// standalone line at 2.
			//
			// The server also grows the standalone line to 2 and keeps the
			// meta line at 1. Diffing by key finds both lines at the same
			// quantity on both sides, so no notice fires.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-meta',
						id: 42,
						quantity: 1,
						name: 'Test Product',
						is_standalone_line: false,
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
					is_standalone_line: false,
				} ),
				makeKeyedLine( {
					key: 'server-key-standalone',
					id: 42,
					quantity: 1,
				} ),
			] );
			const notices = spyOnUpdateNotices();

			await runAction(
				actions.batchAddCartItems( [ { id: 42, quantityToAdd: 1 } ] )
			);

			expect(
				notices.some( ( n ) => n.notice.includes( QUANTITY_CHANGED ) )
			).toBe( false );
		} );

		it( 'emits no quantity-changed notice for a keyless variation re-add when the server returns the line at pre-add + delta', async () => {
			// A variation line (type: variation, id: 42, variation:
			// [Color:Red]) is matched by id+variation. It starts at qty 2; a
			// keyless add with delta +1 bumps it in place, so the
			// post-optimistic snapshot has that same key at qty 3. The server
			// confirms the variation line at qty 3 too. Diffing by key finds
			// the same key at the same quantity on both sides, so no notice
			// fires.
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
					variation: colorRedVariation,
				} )
			);

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

	describe( 'findItemInCart meta-exclusion guard', () => {
		it( 'returns undefined (no match) for a keyless lookup when the cart contains only a meta line for that product', async () => {
			// A line with is_standalone_line: false is a meta-differentiated line
			// (e.g. a bundle child or add-on). The keyless matcher must not return
			// it as the standalone line for the product. The derived count should
			// be 0 (undefined match).
			await loadCartStore();
			seedCart( [
				makeKeyedLine( {
					key: 'meta-key-1',
					id: 42,
					quantity: 2,
					is_standalone_line: false,
				} ),
			] );

			const result = mockState.findItemInCart( { id: 42 } );

			expect( result ).toBeUndefined();
		} );

		it( 'returns only the standalone line when the cart has both a standalone and a meta line for the same product', async () => {
			// When a product has two lines — one standalone (is_standalone_line:
			// true) and one meta (is_standalone_line: false) — the keyless matcher
			// must return only the standalone line.
			const standaloneLine = makeKeyedLine( {
				key: 'standalone-key',
				id: 42,
				quantity: 1,
				is_standalone_line: true,
			} );
			await loadCartStore();
			seedCart( [
				standaloneLine,
				makeKeyedLine( {
					key: 'meta-key',
					id: 42,
					quantity: 3,
					is_standalone_line: false,
				} ),
			] );

			const result = mockState.findItemInCart( { id: 42 } );

			expect( result ).toBe( standaloneLine );
			expect( result?.key ).toBe( 'standalone-key' );
		} );

		it( 'returns only the standalone line when the meta line appears before the standalone line in the cart', async () => {
			// Order must not matter: even when the meta line is first in the
			// array, the matcher must skip it and return the standalone line.
			const standaloneLine = makeKeyedLine( {
				key: 'standalone-key',
				id: 42,
				quantity: 1,
				is_standalone_line: true,
			} );
			await loadCartStore();
			seedCart( [
				makeKeyedLine( {
					key: 'meta-key',
					id: 42,
					quantity: 3,
					is_standalone_line: false,
				} ),
				standaloneLine,
			] );

			const result = mockState.findItemInCart( { id: 42 } );

			expect( result ).toBe( standaloneLine );
			expect( result?.key ).toBe( 'standalone-key' );
		} );

		it( 'continues to match the correct variation standalone line and excludes a same-product meta line', async () => {
			// For variation products, the meta-exclusion guard must AND with the
			// attribute check: only the variation line that matches both attributes
			// and is_standalone_line: true should be returned. A meta line with
			// matching attributes must be excluded.
			const colorRedVariation = [
				{ attribute: 'Color', value: 'Red' },
			] as CartItem[ 'variation' ];
			const standaloneLine = {
				...makeKeyedLine( {
					key: 'var-standalone',
					id: 42,
					quantity: 2,
					is_standalone_line: true,
				} ),
				type: 'variation',
				variation: colorRedVariation,
			} as CartItem;
			await loadCartStore();
			seedCart( [
				{
					...makeKeyedLine( {
						key: 'var-meta',
						id: 42,
						quantity: 5,
						is_standalone_line: false,
					} ),
					type: 'variation',
					variation: colorRedVariation,
				} as CartItem,
				standaloneLine,
			] );

			const result = mockState.findItemInCart( {
				id: 42,
				type: 'variation',
				variation: colorRedVariation,
			} );

			expect( result ).toBe( standaloneLine );
			expect( result?.key ).toBe( 'var-standalone' );
		} );

		it( 'returns undefined when no variation in the cart matches the requested attributes', async () => {
			// A request for a variation that is not in the cart at all must
			// return undefined, even if other variations exist.
			const colorRedVariation = [
				{ attribute: 'Color', value: 'Red' },
			] as CartItem[ 'variation' ];
			const colorBlueVariation = [
				{ attribute: 'Color', value: 'Blue' },
			] as CartItem[ 'variation' ];
			await loadCartStore();
			seedCart( [
				{
					...makeKeyedLine( {
						key: 'var-blue',
						id: 42,
						quantity: 1,
						is_standalone_line: true,
					} ),
					type: 'variation',
					variation: colorBlueVariation,
				} as CartItem,
			] );

			const result = mockState.findItemInCart( {
				id: 42,
				type: 'variation',
				variation: colorRedVariation,
			} );

			expect( result ).toBeUndefined();
		} );

		it( 'returns the line for a keyed lookup regardless of is_standalone_line (keyed lookups unaffected)', async () => {
			// The key short-circuit runs before the meta-exclusion guard, so
			// keyed lookups — e.g. the mini-cart stepper — always return the
			// exact line with that key, whether it is a meta line or not.
			const metaLine = makeKeyedLine( {
				key: 'meta-key',
				id: 42,
				quantity: 2,
				is_standalone_line: false,
			} );
			await loadCartStore();
			seedCart( [ metaLine ] );

			const result = mockState.findItemInCart( {
				id: 42,
				key: 'meta-key',
			} );

			expect( result ).toBe( metaLine );
		} );

		it( 'treats an optimistic line without is_standalone_line as plain and returns it from a keyless lookup', async () => {
			// OptimisticCartItem does not carry is_standalone_line (the field is
			// absent — undefined). The isCartItem() guard short-circuits the &&
			// before the field is read, so the optimistic line IS matched. This
			// preserves rapid-click compounding and the common re-add count.
			const optimisticLine: OptimisticCartItem = {
				id: 42,
				quantity: 1,
			};
			await loadCartStore();
			seedCart( [ optimisticLine ] );

			const result = mockState.findItemInCart( { id: 42 } );

			expect( result ).toBe( optimisticLine );
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
				} )
			);

			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ].quantity ).toBe( 3 );
		} );
	} );

	describe( 'addCartItem post-add side effect suppression', () => {
		describe.each( [
			[ 'option absent', undefined ],
			[ 'option explicitly false', false ],
		] )( '%s', ( _label, suppressPostAddSideEffects ) => {
			const options =
				suppressPostAddSideEffects === undefined
					? undefined
					: { suppressPostAddSideEffects };

			it( 'fires the screen-reader announcement, the legacy added-to-cart event, and the sync event on a successful add', async () => {
				( getConfig as jest.Mock ).mockReturnValueOnce( {
					messages: { addedToCartText: 'Added to your cart.' },
				} );
				mockBatchFetch();
				const actions = await loadCartStore();
				seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );
				const syncEvents: CustomEvent[] = [];
				const onSync = ( e: Event ) =>
					syncEvents.push( e as CustomEvent );
				window.addEventListener(
					'wc-blocks_store_sync_required',
					onSync
				);

				await runAction(
					actions.addCartItem( { id: 42, quantityToAdd: 1 }, options )
				);

				window.removeEventListener(
					'wc-blocks_store_sync_required',
					onSync
				);

				expect( triggerAddedToCartEvent ).toHaveBeenCalledWith( {
					preserveCartData: true,
				} );
				expect( syncEvents ).toHaveLength( 1 );
				expect( speak as jest.Mock ).toHaveBeenCalledWith(
					'Added to your cart.',
					'polite'
				);
			} );

			it( 'shows its own error notice on server rejection', async () => {
				mockBatchFetchFailing( {
					failForPath: '/wc/store/v1/cart/add-item',
					status: 400,
					code: 'woocommerce_rest_cart_product_no_stock',
					message: 'You cannot add that amount to the cart.',
				} );
				const actions = await loadCartStore();
				seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );
				const errors = spyOnShowNoticeError();

				await runAction(
					actions.addCartItem( { id: 42, quantityToAdd: 1 }, options )
				);

				expect( errors ).toHaveLength( 1 );
			} );
		} );

		it( 'suppresses the screen-reader announcement, the legacy added-to-cart event, and the sync event on a successful add when suppressPostAddSideEffects is true', async () => {
			( getConfig as jest.Mock ).mockReturnValueOnce( {
				messages: { addedToCartText: 'Added to your cart.' },
			} );
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );
			const syncEvents: CustomEvent[] = [];
			const onSync = ( e: Event ) => syncEvents.push( e as CustomEvent );
			window.addEventListener( 'wc-blocks_store_sync_required', onSync );

			await runAction(
				actions.addCartItem(
					{ id: 42, quantityToAdd: 1 },
					{ suppressPostAddSideEffects: true }
				)
			);

			window.removeEventListener(
				'wc-blocks_store_sync_required',
				onSync
			);

			expect( triggerAddedToCartEvent ).not.toHaveBeenCalled();
			expect( syncEvents ).toHaveLength( 0 );
			expect( speak as jest.Mock ).not.toHaveBeenCalled();
		} );

		it( 'suppresses its own error notice on server rejection when suppressPostAddSideEffects is true', async () => {
			mockBatchFetchFailing( {
				failForPath: '/wc/store/v1/cart/add-item',
				status: 400,
			} );
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );
			const errors = spyOnShowNoticeError();

			await runAction(
				actions.addCartItem(
					{ id: 42, quantityToAdd: 1 },
					{ suppressPostAddSideEffects: true }
				)
			);

			expect( errors ).toHaveLength( 0 );
		} );

		it( 'still applies the optimistic render and commits the server-reconciled cart when suppressPostAddSideEffects is true', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await runAction(
				actions.addCartItem(
					{ id: 42, quantityToAdd: 1 },
					{ suppressPostAddSideEffects: true }
				)
			);

			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ].quantity ).toBe( 4 );
		} );

		it( 'still emits the showCartUpdatesNotices-gated auto-update notice when suppressPostAddSideEffects is true', async () => {
			// A genuine concurrent server change (quantity 7, diverging from
			// the +1 requested delta landing on 4) must still surface through
			// the showCartUpdatesNotices-gated notice block even though the
			// per-item side effects are suppressed.
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
				makeKeyedLine( {
					key: 'server-key-abc',
					id: 42,
					quantity: 3,
				} ),
			] );
			const notices = spyOnUpdateNotices();

			await runAction(
				actions.addCartItem(
					{ id: 42, quantityToAdd: 1 },
					{ suppressPostAddSideEffects: true }
				)
			);

			expect(
				notices.some(
					( n ) =>
						n.notice.includes( 'was changed to' ) &&
						n.notice.includes( '7' )
				)
			).toBe( true );
		} );
	} );

	describe( 'addCartItem resolved result value', () => {
		it( 'resolves { success: true } when the server accepts the item', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await expect(
				runAction< AddCartItemResult >(
					actions.addCartItem( { id: 42, quantityToAdd: 1 } )
				)
			).resolves.toEqual( { success: true } );
		} );

		it( 'resolves { success: false, error } carrying the server message and code when the server rejects the item, without throwing', async () => {
			mockBatchFetchFailing( {
				failForPath: '/wc/store/v1/cart/add-item',
				status: 400,
				code: 'woocommerce_rest_cart_product_no_stock',
				message: 'You cannot add that amount to the cart.',
			} );
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			const result = await runAction< AddCartItemResult >(
				actions.addCartItem( { id: 42, quantityToAdd: 1 } )
			);

			expect( result.success ).toBe( false );
			// Narrowed via the assertion above rather than an `if`, so this
			// stays a single unconditional set of expectations.
			const { error } = result as AddCartItemResult & {
				success: false;
			};
			expect( error.message ).toBe(
				'You cannot add that amount to the cart.'
			);
			expect( ( error as Error & { code?: string } ).code ).toBe(
				'woocommerce_rest_cart_product_no_stock'
			);
		} );

		it( 'never rejects on a server rejection — the promise always fulfills', async () => {
			mockBatchFetchFailing( {
				failForPath: '/wc/store/v1/cart/add-item',
				status: 400,
			} );
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await expect(
				runAction< AddCartItemResult >(
					actions.addCartItem( { id: 42, quantityToAdd: 1 } )
				)
			).resolves.toEqual( expect.objectContaining( { success: false } ) );
		} );
	} );
} );
