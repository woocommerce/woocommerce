/**
 * External dependencies
 */
import type { Cart, CartItem } from '@woocommerce/types';
import type { Notice } from '@woocommerce/stores/store-notices';

/**
 * Internal dependencies
 */
import type { Store, OptimisticCartItem } from '../cart';

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
 * @return A promise that resolves once the generator has finished.
 */
async function runAction( action: unknown ): Promise< void > {
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
 * Defaults `has_cart_item_data` to `false` so a plain call produces a
 * standalone line — the kind the product-button count reflects and that
 * `findItemInCart` matches on a keyless lookup. Callers that need a
 * meta-differentiated line (a bundle child, booking, or add-on) must pass
 * `has_cart_item_data: true` explicitly as an override.
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
		has_cart_item_data: false,
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
			).resolves.toBeUndefined();

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
			).resolves.toBeUndefined();
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
			// The product is present only as a single keyed meta line (qty 3,
			// has_cart_item_data: true). findItemInCart excludes meta lines on a
			// keyless lookup, so it returns undefined — the optimistic update pushes
			// a brand-new plain line instead of bumping the meta line. The server
			// keeps the meta line at qty 3 and adds a separate standalone line
			// (qty 1). lineMatchesProduct is meta-inclusive, so the pre-add total
			// captures the meta line's qty 3; expectedTotal = 3+1 = 4. The server
			// total (3+1=4) equals expectedTotal (4) → no spurious notice fires.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-abc',
						id: 42,
						quantity: 3,
						has_cart_item_data: true,
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
					has_cart_item_data: true,
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

			expect(
				notices.some( ( n ) => n.notice.includes( QUANTITY_CHANGED ) )
			).toBe( false );
		} );

		it( 'emits no quantity-changed notice when only the first of two meta lines for the same product is bumped optimistically', async () => {
			// The product is present as two distinct keyed meta lines (qty 3 and
			// qty 2, both has_cart_item_data: true). findItemInCart excludes both
			// meta lines on a keyless lookup, so it returns undefined — the
			// optimistic update pushes a brand-new plain line instead of bumping
			// either meta line. The server keeps both meta lines at their pre-add
			// quantities (3 and 2) and adds a separate standalone line (qty 1).
			// lineMatchesProduct is meta-inclusive, so the pre-add total captures
			// both meta lines: preAddTotal = 3+2 = 5, deltaTotal = 1,
			// expectedTotal = 6. The server total (3+2+1=6) equals expectedTotal
			// (6) → no spurious notice fires for any of the three lines.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-1',
						id: 42,
						quantity: 3,
						has_cart_item_data: true,
					} ),
					makeKeyedLine( {
						key: 'server-key-2',
						id: 42,
						quantity: 2,
						has_cart_item_data: true,
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
					has_cart_item_data: true,
				} ),
				makeKeyedLine( {
					key: 'server-key-2',
					id: 42,
					quantity: 2,
					has_cart_item_data: true,
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
			// Same meta-only scenario through the batch path: the cart has only a
			// single meta line (qty 3, has_cart_item_data: true). findItemInCart
			// excludes it, so the optimistic update pushes a brand-new plain line.
			// The server keeps the meta line at 3 and adds a standalone line (qty 1).
			// lineMatchesProduct is meta-inclusive → preAddTotal = 3, deltaTotal = 1,
			// expectedTotal = 4; serverTotal = 3+1 = 4 → suppress, no spurious
			// notice.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-abc',
						id: 42,
						quantity: 3,
						has_cart_item_data: true,
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
					has_cart_item_data: true,
				} ),
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
			// Product 42 occupies two lines: a meta-differentiated line (server-key-
			// meta, qty 1, has_cart_item_data: true) and a plain standalone line
			// (server-key-standalone, qty 1). findItemInCart excludes the meta line
			// on a keyless lookup and returns the standalone line directly, so the
			// optimistic update bumps the standalone line from 1 to 2. The server
			// also grows the standalone line to 2 and keeps the meta line at 1.
			// lineMatchesProduct is meta-inclusive → preAddTotal = 1+1 = 2,
			// deltaTotal = 1, expectedTotal = 3. Server total: meta(1)+standalone(2)
			// = 3 === expected → suppress for both pre-existing keys. No "quantity
			// changed" notice must fire.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-meta',
						id: 42,
						quantity: 1,
						name: 'Test Product',
						has_cart_item_data: true,
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
					has_cart_item_data: true,
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
			// Product 42 occupies two lines: meta first (qty 1, has_cart_item_data:
			// true) then standalone (qty 1). findItemInCart excludes the meta line
			// and returns the standalone line, so the batch item bumps the standalone
			// line optimistically. The server also grows the standalone line to 2 and
			// keeps the meta line at 1. lineMatchesProduct is meta-inclusive →
			// preAddTotal = 1+1 = 2, deltaTotal = 1, expectedTotal = 3. Server total:
			// meta(1)+standalone(2)=3 === expected → suppress.
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'server-key-meta',
						id: 42,
						quantity: 1,
						name: 'Test Product',
						has_cart_item_data: true,
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
					has_cart_item_data: true,
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

	describe( 'findItemInCart meta-exclusion guard', () => {
		it( 'returns undefined (no match) for a keyless lookup when the cart contains only a meta line for that product', async () => {
			// A line with has_cart_item_data: true is a meta-differentiated line
			// (e.g. a bundle child or add-on). The keyless matcher must not return
			// it as the standalone line for the product. The derived count should
			// be 0 (undefined match).
			await loadCartStore();
			seedCart( [
				makeKeyedLine( {
					key: 'meta-key-1',
					id: 42,
					quantity: 2,
					has_cart_item_data: true,
				} ),
			] );

			const result = mockState.findItemInCart( { id: 42 } );

			expect( result ).toBeUndefined();
		} );

		it( 'returns only the standalone line when the cart has both a standalone and a meta line for the same product', async () => {
			// When a product has two lines — one standalone (has_cart_item_data
			// falsy) and one meta (has_cart_item_data: true) — the keyless matcher
			// must return only the standalone line.
			const standaloneLine = makeKeyedLine( {
				key: 'standalone-key',
				id: 42,
				quantity: 1,
				has_cart_item_data: false,
			} );
			await loadCartStore();
			seedCart( [
				standaloneLine,
				makeKeyedLine( {
					key: 'meta-key',
					id: 42,
					quantity: 3,
					has_cart_item_data: true,
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
				has_cart_item_data: false,
			} );
			await loadCartStore();
			seedCart( [
				makeKeyedLine( {
					key: 'meta-key',
					id: 42,
					quantity: 3,
					has_cart_item_data: true,
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
			// and has_cart_item_data falsy should be returned. A meta line with
			// matching attributes must be excluded.
			const colorRedVariation = [
				{ attribute: 'Color', value: 'Red' },
			] as CartItem[ 'variation' ];
			const standaloneLine = {
				...makeKeyedLine( {
					key: 'var-standalone',
					id: 42,
					quantity: 2,
					has_cart_item_data: false,
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
						has_cart_item_data: true,
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
						has_cart_item_data: false,
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

		it( 'returns the line for a keyed lookup regardless of has_cart_item_data (keyed lookups unaffected)', async () => {
			// The key short-circuit runs before the meta-exclusion guard, so
			// keyed lookups — e.g. the mini-cart stepper — always return the
			// exact line with that key, whether it is a meta line or not.
			const metaLine = makeKeyedLine( {
				key: 'meta-key',
				id: 42,
				quantity: 2,
				has_cart_item_data: true,
			} );
			await loadCartStore();
			seedCart( [ metaLine ] );

			const result = mockState.findItemInCart( {
				id: 42,
				key: 'meta-key',
			} );

			expect( result ).toBe( metaLine );
		} );

		it( 'treats an optimistic line without has_cart_item_data as plain and returns it from a keyless lookup', async () => {
			// OptimisticCartItem does not carry has_cart_item_data (the field is
			// absent — undefined). The guard must be falsy-safe: undefined is
			// treated as falsy (plain), so the optimistic line IS matched. This
			// preserves rapid-click compounding and the common re-add count.
			const optimisticLine: OptimisticCartItem = {
				id: 42,
				quantity: 1,
				type: 'simple',
			};
			await loadCartStore();
			seedCart( [ optimisticLine ] );

			const result = mockState.findItemInCart( { id: 42 } );

			expect( result ).toBe( optimisticLine );
		} );

		it( 'lineMatchesProduct still includes meta lines when summing the pre-add total (meta-inclusive, unchanged)', async () => {
			// lineMatchesProduct is intentionally meta-inclusive. It sums ALL
			// lines of a product — including meta lines — for the pre-add total
			// and the keyless-add suppress-keys baseline. This diverges from
			// findItemInCart on purpose.
			//
			// Verification: the cart holds a meta line (qty 2) and a standalone
			// line (qty 1) for product 42. A keyless add (+1) computes its
			// suppress-key baseline using lineMatchesProduct, which counts both
			// lines → preAddTotal = 3, deltaTotal = 1, expectedTotal = 4.
			// The server returns the standalone line at 2 and the meta line
			// unchanged at 2 → serverTotal = 4. Because serverTotal (4) ===
			// expectedTotal (4), the pre-existing keys are suppressed and no
			// spurious "quantity changed" notice fires.
			//
			// If lineMatchesProduct had been changed to skip meta lines it would
			// compute preAddTotal = 1 (standalone only), expectedTotal = 2, and
			// compare against serverTotal = 4 → mismatch → notice fires, failing
			// the assertion below.
			const QUANTITY_CHANGED = 'was changed to';
			mockBatchFetchReturning(
				makeServerCart( [
					makeKeyedLine( {
						key: 'standalone-key',
						id: 42,
						quantity: 2,
					} ),
					makeKeyedLine( {
						key: 'meta-key',
						id: 42,
						quantity: 2,
						has_cart_item_data: true,
					} ),
				] )
			);
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( {
					key: 'standalone-key',
					id: 42,
					quantity: 1,
					has_cart_item_data: false,
				} ),
				makeKeyedLine( {
					key: 'meta-key',
					id: 42,
					quantity: 2,
					has_cart_item_data: true,
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

			// Server total (2+2=4) === expected total (3+1=4) → suppress.
			// No "quantity changed" notice must fire.
			expect(
				notices.some( ( n ) => n.notice.includes( QUANTITY_CHANGED ) )
			).toBe( false );
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
} );
