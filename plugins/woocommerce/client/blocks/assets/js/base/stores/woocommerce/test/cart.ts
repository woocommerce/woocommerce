/**
 * External dependencies
 */
import type { Cart, CartItem, ProductResponseItem } from '@woocommerce/types';
import type { Notice } from '@woocommerce/stores/store-notices';

/**
 * Internal dependencies
 */
import type { Store, OptimisticCartItem, DraftItem } from '../cart';
import type { ProductsStoreState } from '../products';

type MockStore = { state: Store[ 'state' ]; actions: Store[ 'actions' ] };

let mockRegisteredStore: MockStore | null = null;
const mockState = {
	restUrl: 'https://example.com/wp-json/',
	nonce: 'test-nonce-123',
} as Store[ 'state' ];

/**
 * Mock state for the `woocommerce/products` store that the cart store
 * consults one-directionally (never the reverse) to resolve the in-context
 * product and to back its pairing ladder's attribute matching. Tests set
 * `mockProductsState.productInContext` (and `products`/`productVariations`
 * when attribute matching is under test) directly rather than exercising the
 * real `woocommerce/products` getters — those have their own test file.
 */
const mockProductsState: Partial< ProductsStoreState > = {
	products: {},
	productVariations: {},
};

/**
 * The value `getContext( 'woocommerce/cart' )` should return for the draft
 * collection resolver, controlled per test. `undefined` (or an object with
 * no `draftItems`) simulates a surface with no container of its own, so the
 * resolver falls back to the page-wide `mockState.draftItems`; an object
 * carrying its own `draftItems` array simulates a container that has
 * isolated its subtree.
 */
let mockCartContext: { draftItems?: DraftItem[] } | undefined;

/**
 * When `true`, the mocked `getContext` throws instead of returning
 * {@link mockCartContext}, reproducing the real Interactivity runtime's
 * behavior when called with no directive currently executing on the call
 * stack (i.e. outside of a directive's execution).
 */
let mockCartContextThrows = false;

/**
 * The value `getServerContext( 'woocommerce/cart' )` should return, controlled
 * per test. `undefined` simulates a surface that emits no `draftSeed` (or
 * emits no `woocommerce/cart` context bag at all).
 */
let mockServerContext: { draftSeed?: DraftItem } | undefined;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: jest.fn(),
		getContext: jest.fn( () => {
			if ( mockCartContextThrows ) {
				throw new Error(
					'Cannot call `getContext()` when there is no scope.'
				);
			}
			return mockCartContext;
		} ),
		getServerContext: jest.fn( () => mockServerContext ),
		store: jest.fn( ( name: string, definition ) => {
			// The cart store consults `woocommerce/products` one-directionally;
			// keep its mock state independent of the cart's own so seeding one
			// never bleeds into the other.
			if ( name === 'woocommerce/products' ) {
				if ( definition?.state ) {
					Object.defineProperties(
						mockProductsState,
						Object.getOwnPropertyDescriptors( definition.state )
					);
				}
				return { state: mockProductsState };
			}
			// The cart store calls `store()` twice: once to read `state` and
			// once to register `actions`. Merge the definition's `state`
			// descriptors (e.g. the `findItem` getter) onto the shared mock
			// state so the real getter runs against seeded cart lines, and
			// carry the action generators through both calls.
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

// The cart store's side-effecting import of `woocommerce/products` is
// replaced with an empty virtual module: the mocked `store()` above already
// handles the `'woocommerce/products'` registration call the cart store
// makes directly, so the real products store module never needs to load
// here.
jest.mock( '@woocommerce/stores/woocommerce/products', () => ( {} ), {
	virtual: true,
} );

jest.mock( '../legacy-events', () => ( {
	triggerAddedToCartEvent: jest.fn(),
} ) );

// eslint-disable-next-line @typescript-eslint/no-var-requires
const { triggerAddedToCartEvent } = jest.requireMock( '../legacy-events' ) as {
	triggerAddedToCartEvent: jest.Mock;
};

// eslint-disable-next-line @typescript-eslint/no-var-requires
const { getServerContext: mockGetServerContext } = jest.requireMock(
	'@wordpress/interactivity'
) as { getServerContext: jest.Mock };

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
 * `refresh()` is then driven to completion so that the singleton
 * nonce-ready promise resolves and queued mutations are allowed to flush; tests
 * seed `state.cart` afterwards via {@link seedCart}.
 *
 * @return A promise resolving to the freshly registered cart store actions.
 */
async function loadCartStore(): Promise< Store[ 'actions' ] > {
	jest.isolateModules( () => require( '../cart' ) );
	const actions = mockRegisteredStore?.actions as Store[ 'actions' ];
	// Drive the refresh so the module-level nonce-ready promise resolves.
	await runAction( actions.refresh() );
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
		delete ( mockState as Partial< Store[ 'state' ] > ).cart;
		mockCartContext = undefined;
		mockCartContextThrows = false;
		mockServerContext = undefined;
		delete mockProductsState.productInContext;
		mockProductsState.products = {};
		mockProductsState.productVariations = {};
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

		it( 'suppresses the notice for a keyless add when the client bumps a meta line but the server grows the standalone line', async () => {
			// Product 42 occupies two lines: a meta-differentiated line ordered
			// first (server-key-meta, qty 1) and a plain standalone line second
			// (server-key-standalone, qty 1). The identity matcher matches the
			// meta line first, so addCartItem bumps it optimistically. The
			// server, however, grows the standalone line instead and leaves
			// the meta line unchanged.
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

		it( 'leaves removeItem notice behavior unchanged (auto-DELETE still fires)', async () => {
			// removeItem must not pass the new baseline; the auto-DELETE
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

			await runAction( actions.removeItem( 'server-key-keep' ) );

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

	describe( 'draftItems / upsertDraftItem / removeDraftItem', () => {
		/**
		 * Builds a minimal draft payload.
		 *
		 * @param overrides Partial draft fields to override the defaults.
		 * @return A draft carrying only `id` and `quantity` unless overridden.
		 */
		function makeDraft( overrides: Partial< DraftItem > = {} ): DraftItem {
			return { id: 42, quantity: 1, ...overrides } as DraftItem;
		}

		it( 'starts as an empty array', async () => {
			mockBatchFetch();
			await loadCartStore();

			expect( mockState.draftItems ).toEqual( [] );
		} );

		it( 'appends a new draft to the page-wide collection', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();

			actions.upsertDraftItem( makeDraft() );

			expect( mockState.draftItems ).toEqual( [ makeDraft() ] );
		} );

		it( 'merges a second upsertDraftItem for the same product id instead of duplicating it', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();

			actions.upsertDraftItem( makeDraft( { quantity: 2 } ) );
			actions.upsertDraftItem( makeDraft( { quantity: 5 } ) );

			expect( mockState.draftItems ).toEqual( [
				makeDraft( { quantity: 5 } ),
			] );
		} );

		it( 'keeps drafts for the same product id independent across the page-wide collection and a container collection', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();

			// No container context active: writes land in the page-wide
			// collection.
			actions.upsertDraftItem( makeDraft( { quantity: 1 } ) );

			// A container establishes its own collection by exposing
			// `draftItems` in its `woocommerce/cart` context; writes made
			// while that context is active land there instead.
			const containerCollection: DraftItem[] = [];
			mockCartContext = { draftItems: containerCollection };
			actions.upsertDraftItem( makeDraft( { quantity: 9 } ) );

			expect( mockState.draftItems ).toEqual( [
				makeDraft( { quantity: 1 } ),
			] );
			expect( containerCollection ).toEqual( [
				makeDraft( { quantity: 9 } ),
			] );
		} );

		it( 'stores namespaced extension props at the draft payload root', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();

			actions.upsertDraftItem(
				makeDraft( { 'my-plugin/gift-note': 'Happy birthday!' } )
			);

			expect( mockState.draftItems[ 0 ] ).toEqual(
				expect.objectContaining( {
					id: 42,
					quantity: 1,
					'my-plugin/gift-note': 'Happy birthday!',
				} )
			);
		} );

		it( 'removes the matching draft, leaving the collection empty', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			actions.upsertDraftItem( makeDraft() );

			actions.removeDraftItem( { id: 42 } );

			expect( mockState.draftItems ).toEqual( [] );
		} );

		it( 'leaves other drafts in the collection in place after a removal', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			actions.upsertDraftItem( makeDraft( { id: 42 } ) );
			actions.upsertDraftItem( makeDraft( { id: 43 } ) );

			actions.removeDraftItem( { id: 42 } );

			expect( mockState.draftItems ).toEqual( [
				makeDraft( { id: 43 } ),
			] );
		} );

		it( 'no-ops removeDraftItem when the collection has no matching draft', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();

			actions.removeDraftItem( { id: 42 } );

			expect( mockState.draftItems ).toEqual( [] );
		} );

		it( 'rejects an upsert that would change an existing draft id, applying no state change', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			actions.upsertDraftItem( makeDraft() );

			// `id: 42` names the draft to target; the payload's own `id: 99`
			// disagrees with it, which is an in-place rename attempt.
			actions.upsertDraftItem( { id: 99, quantity: 5 }, { id: 42 } );

			expect( mockState.draftItems ).toEqual( [ makeDraft() ] );
			expect( console ).toHaveWarned();
		} );

		it( 'rejects creating a new draft without a numeric quantity, applying no state change', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();

			actions.upsertDraftItem( { id: 42 } );

			expect( mockState.draftItems ).toEqual( [] );
			expect( console ).toHaveWarned();
		} );

		it( 'rejects an upsert missing both a payload id and an explicit target id', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();

			actions.upsertDraftItem( { quantity: 1 } );

			expect( mockState.draftItems ).toEqual( [] );
			expect( console ).toHaveWarned();
		} );

		it( 'keeps drafts independent from the cart mirror in both directions', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			actions.upsertDraftItem( makeDraft( { quantity: 7 } ) );

			// Mutating the draft never mutates the cart mirror...
			expect( mockState.cart.items[ 0 ].quantity ).toBe( 3 );

			// ...and mutating the cart mirror never mutates the draft.
			mockState.cart.items[ 0 ].quantity = 10;
			expect( mockState.draftItems[ 0 ].quantity ).toBe( 7 );
		} );
	} );

	describe( 'draft collection resolution (resolveDraftItems)', () => {
		it( 'upsertDraftItem writes to the nearest container collection when one is active', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			const containerCollection: DraftItem[] = [];
			mockCartContext = { draftItems: containerCollection };

			actions.upsertDraftItem( { id: 42, quantity: 1 } );

			expect( mockState.draftItems ).toEqual( [] );
			expect( containerCollection ).toEqual( [
				{ id: 42, quantity: 1 },
			] );
		} );

		it( 'removeDraftItem removes from the nearest container collection when one is active', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			const containerCollection: DraftItem[] = [
				{ id: 42, quantity: 1 },
			];
			mockCartContext = { draftItems: containerCollection };

			actions.removeDraftItem( { id: 42 } );

			expect( containerCollection ).toEqual( [] );
		} );

		it( 'degrades to the page-wide collection, without throwing, when read outside a directive', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			mockCartContextThrows = true;

			expect( () =>
				actions.upsertDraftItem( { id: 42, quantity: 1 } )
			).not.toThrow();

			expect( mockState.draftItems ).toEqual( [
				{ id: 42, quantity: 1 },
			] );
		} );
	} );

	describe( 'seedDraftIfAbsent', () => {
		it( 'copies the server-seeded draftSeed into the page-wide collection when it holds no draft for that product', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			mockServerContext = { draftSeed: { id: 42, quantity: 3 } };

			actions.seedDraftIfAbsent();

			expect( mockState.draftItems ).toEqual( [
				{ id: 42, quantity: 3 },
			] );
		} );

		it( 'seeds the nearest container collection (a context override), not always the page-wide one', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			const containerCollection: DraftItem[] = [];
			mockCartContext = { draftItems: containerCollection };
			mockServerContext = { draftSeed: { id: 42, quantity: 2 } };

			actions.seedDraftIfAbsent();

			expect( mockState.draftItems ).toEqual( [] );
			expect( containerCollection ).toEqual( [
				{ id: 42, quantity: 2 },
			] );
		} );

		it( 'copies the variation and namespaced extension props from the seed verbatim', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			mockServerContext = {
				draftSeed: {
					id: 42,
					quantity: 1,
					variation: [ { attribute: 'Color', value: 'red' } ],
					'my-plugin/gift-note': 'Happy birthday!',
				} as DraftItem,
			};

			actions.seedDraftIfAbsent();

			expect( mockState.draftItems ).toEqual( [
				{
					id: 42,
					quantity: 1,
					variation: [ { attribute: 'Color', value: 'red' } ],
					'my-plugin/gift-note': 'Happy birthday!',
				},
			] );
		} );

		it( 'never overwrites an already-edited live draft for the same product id (re-render never clobbers)', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			// The shopper already edited this collection's draft before the
			// region re-rendered and re-ran the init.
			actions.upsertDraftItem( { id: 42, quantity: 9 } );
			mockServerContext = { draftSeed: { id: 42, quantity: 1 } };

			actions.seedDraftIfAbsent();

			expect( mockState.draftItems ).toEqual( [
				{ id: 42, quantity: 9 },
			] );
		} );

		it( 'is a no-op, leaving draftItems untouched, when the server context carries no draftSeed', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			mockServerContext = undefined;

			actions.seedDraftIfAbsent();

			expect( mockState.draftItems ).toEqual( [] );
		} );

		it( 'reads the seed via getServerContext( "woocommerce/cart" )', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			mockServerContext = { draftSeed: { id: 42, quantity: 1 } };

			actions.seedDraftIfAbsent();

			expect( mockGetServerContext ).toHaveBeenCalledWith(
				'woocommerce/cart'
			);
		} );
	} );

	describe( 'findItem / itemInContext / inCartQuantity', () => {
		/**
		 * Seeds `mockProductsState.productInContext` with a minimal product.
		 *
		 * @param overrides Partial product fields to override the defaults.
		 * @return The product object assigned to `productInContext`.
		 */
		function seedProductInContext(
			overrides: Partial< ProductResponseItem > = {}
		) {
			const product = {
				id: 42,
				type: 'simple',
				grouped_products: [],
				...overrides,
			} as ProductResponseItem;
			mockProductsState.productInContext = product;
			return product;
		}

		/**
		 * Builds a minimal server-confirmed cart line, optionally carrying
		 * namespaced extension data under `extensions[namespace]`.
		 *
		 * @param overrides Partial cart-line fields to override the defaults.
		 * @return A cart line suitable for seeding `state.cart.items`.
		 */
		function makeLine( overrides: Partial< CartItem > = {} ): CartItem {
			return makeKeyedLine( { extensions: {}, ...overrides } );
		}

		it( 'itemInContext has no cartItem/draft when no product is in context', async () => {
			mockBatchFetch();
			await loadCartStore();
			seedCart( [] );

			expect( mockState.itemInContext ).toEqual( {
				cartItem: undefined,
				draft: undefined,
			} );
		} );

		it( 'itemInContext has no cartItem when the in-context product is not in the cart', async () => {
			mockBatchFetch();
			await loadCartStore();
			seedCart( [ makeLine( { id: 99 } ) ] );
			seedProductInContext( { id: 42 } );

			expect( mockState.itemInContext.cartItem ).toBeUndefined();
		} );

		it( 'itemInContext pairs via product identity when exactly one line matches', async () => {
			mockBatchFetch();
			await loadCartStore();
			const line = makeLine( { id: 42 } );
			seedCart( [ line, makeLine( { id: 99 } ) ] );
			seedProductInContext( { id: 42 } );

			expect( mockState.itemInContext ).toEqual( {
				cartItem: line,
				draft: undefined,
			} );
		} );

		it( 'itemInContext includes the resolved collection draft for the in-context product alongside the paired line', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			const line = makeLine( { id: 42 } );
			seedCart( [ line ] );
			seedProductInContext( { id: 42 } );
			actions.upsertDraftItem( { id: 42, quantity: 3 } );

			expect( mockState.itemInContext ).toEqual( {
				cartItem: line,
				draft: { id: 42, quantity: 3 },
			} );
		} );

		it( 'itemInContext disambiguates same-id lines via a namespaced extension-prop match against the draft', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			const giftA = makeLine( {
				id: 42,
				key: 'line-a',
				extensions: { 'my-plugin': { giftNote: 'A' } },
			} );
			const giftB = makeLine( {
				id: 42,
				key: 'line-b',
				extensions: { 'my-plugin': { giftNote: 'B' } },
			} );
			seedCart( [ giftA, giftB ] );
			seedProductInContext( { id: 42 } );
			actions.upsertDraftItem( {
				id: 42,
				quantity: 1,
				'my-plugin/giftNote': 'B',
			} );

			expect( mockState.itemInContext.cartItem ).toEqual( giftB );
		} );

		it( 'itemInContext never guesses: ambiguous identity/extension matches leave cartItem undefined', async () => {
			mockBatchFetch();
			await loadCartStore();
			// Same id, same (empty) extensions — nothing distinguishes them.
			seedCart( [
				makeLine( { id: 42, key: 'line-a' } ),
				makeLine( { id: 42, key: 'line-b' } ),
			] );
			seedProductInContext( { id: 42 } );

			expect( mockState.itemInContext.cartItem ).toBeUndefined();
		} );

		it( 'itemInContext leaves cartItem undefined when the draft extension prop matches no line, though the product is in the cart', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [
				makeLine( {
					id: 42,
					extensions: { 'my-plugin': { giftNote: 'A' } },
				} ),
			] );
			seedProductInContext( { id: 42 } );
			actions.upsertDraftItem( {
				id: 42,
				quantity: 1,
				'my-plugin/giftNote': 'C',
			} );

			expect( mockState.itemInContext.cartItem ).toBeUndefined();
		} );

		it( 'findItem returns the same envelope for an explicit id, key, or filter', async () => {
			mockBatchFetch();
			await loadCartStore();
			const line = makeLine( { id: 42, key: 'the-key' } );
			seedCart( [ line ] );

			const byId = mockState.findItem( { id: 42 } );
			const byKey = mockState.findItem( { key: 'the-key' } );
			const byFilter = mockState.findItem( {
				filter: ( item ) => item.id === 42,
			} );

			expect( byId.cartItem ).toEqual( line );
			expect( byKey.cartItem ).toEqual( line );
			expect( byFilter.cartItem ).toEqual( line );
		} );

		it( 'findItem resolves the draft from the page-wide collection when no container context is active', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			actions.upsertDraftItem( { id: 42, quantity: 2 } );

			expect( mockState.findItem( { id: 42 } ).draft ).toEqual( {
				id: 42,
				quantity: 2,
			} );
		} );

		it( 'findItem resolves the draft from the nearest container collection, not the page-wide one, when a container is active', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			mockCartContext = { draftItems: [] };
			actions.upsertDraftItem( { id: 42, quantity: 5 } );

			expect( mockState.findItem( { id: 42 } ).draft ).toEqual( {
				id: 42,
				quantity: 5,
			} );

			// Outside that container's context, the page-wide collection
			// carries no matching draft.
			mockCartContext = undefined;
			expect( mockState.findItem( { id: 42 } ).draft ).toBeUndefined();
		} );

		it( 'inCartQuantity is 0 when no product is in context', async () => {
			mockBatchFetch();
			await loadCartStore();
			seedCart( [] );

			expect( mockState.inCartQuantity ).toBe( 0 );
		} );

		it( 'inCartQuantity returns the paired line quantity for a simple in-context product', async () => {
			mockBatchFetch();
			await loadCartStore();
			seedCart( [ makeLine( { id: 42, quantity: 4 } ) ] );
			seedProductInContext( { id: 42, type: 'simple' } );

			expect( mockState.inCartQuantity ).toBe( 4 );
		} );

		it( 'inCartQuantity aggregates children in-cart quantities for a grouped in-context product', async () => {
			mockBatchFetch();
			await loadCartStore();
			seedCart( [
				makeLine( { id: 1, quantity: 2 } ),
				makeLine( { id: 2, quantity: 5 } ),
				// id 3 has no line — contributes 0.
			] );
			seedProductInContext( {
				id: 99,
				type: 'grouped',
				grouped_products: [ 1, 2, 3 ],
			} );

			expect( mockState.inCartQuantity ).toBe( 7 );
		} );

		it( 'inCartQuantity resolves the correct per-variation quantity for a variable in-context product', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			const greenLine = makeLine( {
				id: 55,
				type: 'variation',
				quantity: 3,
				variation: [
					{
						attribute: 'Color',
						value: 'green',
						raw_attribute: 'attribute_pa_color',
					},
				],
			} );
			const blueLine = makeLine( {
				id: 56,
				type: 'variation',
				quantity: 9,
				variation: [
					{
						attribute: 'Color',
						value: 'blue',
						raw_attribute: 'attribute_pa_color',
					},
				],
			} );
			seedCart( [ greenLine, blueLine ] );
			seedProductInContext( { id: 55, type: 'variation' } );
			// The resolved collection's draft selection names which variation
			// is "selected".
			actions.upsertDraftItem( {
				id: 55,
				quantity: 1,
				variation: [ { attribute: 'Color', value: 'green' } ],
			} );

			expect( mockState.inCartQuantity ).toBe( 3 );
		} );
	} );

	describe( 'addItem / updateItem / removeItem / refresh', () => {
		/**
		 * Seeds `mockProductsState.productInContext` with a minimal product.
		 *
		 * @param overrides Partial product fields to override the defaults.
		 * @return The product object assigned to `productInContext`.
		 */
		function seedProductInContext(
			overrides: Partial< ProductResponseItem > = {}
		) {
			const product = {
				id: 42,
				type: 'simple',
				grouped_products: [],
				...overrides,
			} as ProductResponseItem;
			mockProductsState.productInContext = product;
			return product;
		}

		describe( 'addItem', () => {
			it( 'posts only the resolved collection draft of the in-context simple/variable product, never another collection or product', async () => {
				const captured = mockBatchFetch();
				const actions = await loadCartStore();
				seedCart( [] );
				seedProductInContext( { id: 42, type: 'simple' } );

				// The page-wide draft for the in-context product: must post.
				actions.upsertDraftItem( { id: 42, quantity: 2 } );
				// A different product in the same collection: must not post.
				actions.upsertDraftItem( { id: 99, quantity: 5 } );
				// The same product in a different (container) collection:
				// must not post.
				const containerCollection: DraftItem[] = [];
				mockCartContext = { draftItems: containerCollection };
				actions.upsertDraftItem( { id: 42, quantity: 9 } );
				// `addItem()` below runs from the page-wide surface, with no
				// container context active.
				mockCartContext = undefined;

				await runAction( actions.addItem() );

				expect( captured ).toHaveLength( 1 );
				expect( captured[ 0 ].path ).toBe(
					'/wc/store/v1/cart/add-item'
				);
				expect( captured[ 0 ].body ).toEqual( { id: 42, quantity: 2 } );
			} );

			it( 'sends nothing when the in-context product has no draft in the resolved collection', async () => {
				const captured = mockBatchFetch();
				const actions = await loadCartStore();
				seedCart( [] );
				seedProductInContext( { id: 42, type: 'simple' } );

				await runAction( actions.addItem() );

				expect( captured ).toHaveLength( 0 );
			} );

			it( 'sends nothing when no product is in context', async () => {
				const captured = mockBatchFetch();
				const actions = await loadCartStore();
				seedCart( [] );

				await runAction( actions.addItem() );

				expect( captured ).toHaveLength( 0 );
			} );

			it( 'posts one batched request set of a grouped product children drafts whose quantity is greater than 0', async () => {
				const captured = mockBatchFetch();
				const fetchSpy = global.fetch as jest.Mock;
				const actions = await loadCartStore();
				// The initial refresh already issued one GET; reset the count so
				// only the batch call made by this test is measured below.
				fetchSpy.mockClear();
				seedCart( [] );
				seedProductInContext( {
					id: 200,
					type: 'grouped',
					grouped_products: [ 1, 2, 3, 4 ],
				} );

				actions.upsertDraftItem( { id: 1, quantity: 2 } );
				// Quantity 0: must be excluded.
				actions.upsertDraftItem( { id: 2, quantity: 0 } );
				// id 3 has no draft at all: must be excluded.
				actions.upsertDraftItem( { id: 4, quantity: 3 } );
				// Same child id, different (container) collection: must not
				// be posted instead.
				const containerCollection: DraftItem[] = [];
				mockCartContext = { draftItems: containerCollection };
				actions.upsertDraftItem( { id: 1, quantity: 99 } );
				mockCartContext = undefined;

				await runAction( actions.addItem() );

				expect( captured ).toHaveLength( 2 );
				expect(
					captured.every(
						( r ) => r.path === '/wc/store/v1/cart/add-item'
					)
				).toBe( true );
				expect( captured ).toEqual(
					expect.arrayContaining( [
						expect.objectContaining( {
							body: { id: 1, quantity: 2 },
						} ),
						expect.objectContaining( {
							body: { id: 4, quantity: 3 },
						} ),
					] )
				);
				// A single auto-batched POST for both children (one call to the
				// batch endpoint), not one call per child.
				expect( fetchSpy ).toHaveBeenCalledTimes( 1 );
			} );

			it( 'sends nothing for a grouped product whose children all have a zero or absent draft quantity', async () => {
				const captured = mockBatchFetch();
				const actions = await loadCartStore();
				seedCart( [] );
				seedProductInContext( {
					id: 200,
					type: 'grouped',
					grouped_products: [ 1, 2 ],
				} );
				actions.upsertDraftItem( { id: 1, quantity: 0 } );

				await runAction( actions.addItem() );

				expect( captured ).toHaveLength( 0 );
			} );

			it( 'posts an explicit payload verbatim, extension props included, bypassing collection/product resolution', async () => {
				const captured = mockBatchFetch();
				const actions = await loadCartStore();
				seedCart( [] );
				// No product in context and no drafts anywhere: the explicit
				// payload path must not depend on either.

				await runAction(
					actions.addItem( {
						id: 7,
						quantity: 1,
						'my-plugin/gift-note': 'Hi',
					} )
				);

				expect( captured ).toHaveLength( 1 );
				expect( captured[ 0 ].body ).toEqual( {
					id: 7,
					quantity: 1,
					'my-plugin/gift-note': 'Hi',
				} );
			} );

			it( 'fires the legacy added-to-cart event once on success', async () => {
				mockBatchFetch();
				const actions = await loadCartStore();
				seedCart( [] );
				seedProductInContext( { id: 42, type: 'simple' } );
				actions.upsertDraftItem( { id: 42, quantity: 1 } );

				await runAction( actions.addItem() );

				expect( triggerAddedToCartEvent ).toHaveBeenCalledTimes( 1 );
				expect( triggerAddedToCartEvent ).toHaveBeenCalledWith( {
					preserveCartData: true,
				} );
			} );

			it( 'rolls a failed batch back to the pre-cycle cart snapshot and dispatches a store notice', async () => {
				mockBatchFetchFailing( {
					failForPath: '/wc/store/v1/cart/add-item',
					status: 400,
				} );
				const actions = await loadCartStore();
				seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );
				seedProductInContext( { id: 42, type: 'simple' } );
				actions.upsertDraftItem( { id: 42, quantity: 1 } );
				const notices = spyOnUpdateNotices();

				await runAction( actions.addItem() );

				expect( mockState.cart.items ).toHaveLength( 1 );
				expect( mockState.cart.items[ 0 ].quantity ).toBe( 3 );
				expect( notices.length ).toBeGreaterThan( 0 );
			} );
		} );

		describe( 'updateItem', () => {
			it( 'reproduces the keyed absolute-quantity update-item behavior of addCartItem', async () => {
				const captured = mockBatchFetch();
				const actions = await loadCartStore();
				seedCart( [
					makeKeyedLine( {
						id: 42,
						key: 'server-key-abc',
						quantity: 3,
					} ),
				] );

				await runAction(
					actions.updateItem( {
						key: 'server-key-abc',
						quantity: 5,
					} )
				);

				expect( captured ).toHaveLength( 1 );
				expect( captured[ 0 ].path ).toBe(
					'/wc/store/v1/cart/update-item'
				);
				expect( captured[ 0 ].body.quantity ).toBe( 5 );
				expect( captured[ 0 ].body.key ).toBe( 'server-key-abc' );
			} );

			it( 'optimistically applies the absolute quantity before commit', async () => {
				mockBatchFetch();
				const actions = await loadCartStore();
				seedCart( [
					makeKeyedLine( {
						id: 42,
						key: 'server-key-abc',
						quantity: 3,
					} ),
				] );

				await runAction(
					actions.updateItem( {
						key: 'server-key-abc',
						quantity: 5,
					} )
				);

				expect( mockState.cart.items[ 0 ].quantity ).toBe( 5 );
			} );

			it( 'fires the legacy added-to-cart event on success, matching addCartItem', async () => {
				mockBatchFetch();
				const actions = await loadCartStore();
				seedCart( [
					makeKeyedLine( {
						id: 42,
						key: 'server-key-abc',
						quantity: 3,
					} ),
				] );

				await runAction(
					actions.updateItem( {
						key: 'server-key-abc',
						quantity: 5,
					} )
				);

				expect( triggerAddedToCartEvent ).toHaveBeenCalledWith( {
					preserveCartData: true,
				} );
			} );

			it( 'no-ops when no cart line matches the given key', async () => {
				const captured = mockBatchFetch();
				const actions = await loadCartStore();
				seedCart( [ makeKeyedLine( { id: 42, key: 'other-key' } ) ] );

				await runAction(
					actions.updateItem( {
						key: 'unknown-key',
						quantity: 5,
					} )
				);

				expect( captured ).toHaveLength( 0 );
			} );
		} );

		describe( 'removeItem', () => {
			it( 'reproduces the existing removeCartItem line-removal behavior', async () => {
				const captured = mockBatchFetch();
				const actions = await loadCartStore();
				seedCart( [
					makeKeyedLine( { id: 42, key: 'keep-key' } ),
					makeKeyedLine( { id: 43, key: 'remove-key' } ),
				] );

				await runAction( actions.removeItem( 'remove-key' ) );

				expect( captured ).toHaveLength( 1 );
				expect( captured[ 0 ].path ).toBe(
					'/wc/store/v1/cart/remove-item'
				);
				expect( captured[ 0 ].body ).toEqual( { key: 'remove-key' } );
			} );

			it( 'optimistically removes the line before commit', async () => {
				mockBatchFetch();
				const actions = await loadCartStore();
				seedCart( [
					makeKeyedLine( { id: 42, key: 'keep-key' } ),
					makeKeyedLine( { id: 43, key: 'remove-key' } ),
				] );

				await runAction( actions.removeItem( 'remove-key' ) );

				expect(
					mockState.cart.items.some(
						( item ) => item.key === 'remove-key'
					)
				).toBe( false );
				expect(
					mockState.cart.items.some(
						( item ) => item.key === 'keep-key'
					)
				).toBe( true );
			} );
		} );

		describe( 'refresh', () => {
			it( 'reproduces the existing refreshCartItems cache-busting refresh behavior', () => {
				const mockFetch = jest.fn().mockResolvedValue(
					new Response(
						JSON.stringify( {
							items: [],
							totals: {},
							errors: [],
						} )
					)
				);
				global.fetch = mockFetch;

				jest.isolateModules( () => require( '../cart' ) );
				const iterator = mockRegisteredStore?.actions.refresh();

				// Async actions are typed as void for consumers, but are
				// actually generators internally.
				( iterator as unknown as Iterator< void > ).next();

				expect( mockFetch ).toHaveBeenCalledWith(
					'https://example.com/wp-json/wc/store/v1/cart',
					expect.objectContaining( {
						method: 'GET',
						cache: 'no-store',
					} )
				);
			} );
		} );
	} );

	describe( 'retired surface', () => {
		it( 'no longer exposes batchAddCartItems, findItemInCart, removeCartItem, or refreshCartItems', async () => {
			const actions = await loadCartStore();
			const untypedActions = actions as unknown as Record<
				string,
				unknown
			>;
			const untypedState = mockRegisteredStore?.state as
				| Record< string, unknown >
				| undefined;

			expect( untypedActions.batchAddCartItems ).toBeUndefined();
			expect( untypedActions.removeCartItem ).toBeUndefined();
			expect( untypedActions.refreshCartItems ).toBeUndefined();
			expect( untypedState?.findItemInCart ).toBeUndefined();
		} );

		it( 'trims isInCart from the envelope: findItem/itemInContext expose only cartItem/draft, even when a product shares identity with more than one ambiguous line', async () => {
			mockBatchFetch();
			await loadCartStore();
			// Same id, same (empty) extensions — nothing distinguishes them,
			// so the pairing ladder is maximally ambiguous. This is the exact
			// case the tri-state used to flag; the envelope still must not
			// carry `isInCart`.
			seedCart( [
				makeKeyedLine( { id: 42, key: 'line-a', extensions: {} } ),
				makeKeyedLine( { id: 42, key: 'line-b', extensions: {} } ),
			] );

			const envelope = mockState.findItem( { id: 42 } );

			expect( envelope ).toEqual( {
				cartItem: undefined,
				draft: undefined,
			} );
			expect( Object.keys( envelope ) ).not.toContain( 'isInCart' );
		} );

		it( 'still resolves an existing keyed line for addCartItem via the internalized private matcher', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( { id: 42, key: 'server-key-abc', quantity: 3 } ),
			] );

			await runAction(
				actions.addCartItem( {
					id: 42,
					key: 'server-key-abc',
					quantity: 5,
					type: 'simple',
				} )
			);

			expect( captured ).toHaveLength( 1 );
			expect( captured[ 0 ].path ).toBe(
				'/wc/store/v1/cart/update-item'
			);
			expect( captured[ 0 ].body.quantity ).toBe( 5 );
		} );
	} );
} );
