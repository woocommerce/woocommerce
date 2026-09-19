/**
 * External dependencies
 */
import { getConfig } from '@wordpress/interactivity';
import { speak } from '@wordpress/a11y';
import type { Cart, CartItem } from '@woocommerce/types';
import type { Notice } from '@woocommerce/stores/store-notices';

/**
 * Internal dependencies
 */
import type {
	CartActionsState,
	OptimisticCartItem,
	AddCartItemOutcome,
} from '../cart-actions';
import type { ScopeState, ProductScopeContext } from '../scope';
import type { CatalogState } from '../catalog';
import { triggerAddedToCartEvent } from '../legacy-events';
import { showNoticeError, updateNotices } from '../notices';

type MockState = CatalogState &
	ScopeState &
	CartActionsState & {
		restUrl: string;
		nonce: string;
	};

/**
 * The cart plane's raw, undriven generator-function actions, exactly as
 * `cart-actions.ts` exports them — not `CartActionsActions`, whose
 * signatures are the Promise-returning shape `store()` hands back to real
 * callers (see `cartActions`'s own docblock in `cart-actions.ts`). This
 * suite drives the raw generators itself, via {@link runAction}.
 */
type RawCartActions = typeof import('../cart-actions').cartActions;

let mockContext: ProductScopeContext | null = null;
let mockState: MockState;
let cartActions: RawCartActions;

/**
 * The `restUrl` / `nonce` every test's mock state seeds, mirroring what
 * `BlocksSharedState.php` seeds onto the real store.
 */
function baseState(): Pick< MockState, 'restUrl' | 'nonce' > {
	return {
		restUrl: 'https://example.com/wp-json/',
		nonce: 'test-nonce-123',
	};
}

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: jest.fn(),
		getContext: jest.fn( () => mockContext ),
		// `notices.ts` pulls in `does-cart-item-match-attributes.ts`, which
		// registers the (untouched, unrelated) `woocommerce/products` store
		// as a side effect of module load. `cart-actions.ts` itself never
		// calls `store()` — see `bindCartState`'s docblock — so this generic
		// stand-in only ever backs that one incidental registration.
		store: jest.fn( () => ( {
			state: { products: {}, productVariations: {} },
			actions: {},
		} ) ),
	} ),
	{ virtual: true }
);

jest.mock( '../legacy-events', () => ( {
	triggerAddedToCartEvent: jest.fn(),
} ) );

jest.mock( '@wordpress/a11y', () => ( {
	speak: jest.fn(),
} ) );

// `showNoticeError` / `updateNotices` are mocked so tests can assert what a
// mutation reports without a real store-notices round trip; every other
// export (the pure diff/matching helpers `addCartItem`/`removeCartItem`
// actually rely on) keeps its real implementation via `requireActual`.
jest.mock( '../notices', () => ( {
	...jest.requireActual( '../notices' ),
	showNoticeError: jest.fn(),
	updateNotices: jest.fn(),
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
	body: OptimisticCartItem & Record< string, unknown >;
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
 * Loads a fresh copy of the cart plane: binds `scope.ts` and `cart-actions.ts`
 * to a shared mock state, exactly as `index.ts` does for the real store, and
 * resolves the nonce gate.
 *
 * The modules are re-required in isolation so each test starts from a clean
 * mutation queue and a fresh module-level nonce-ready promise. The initial
 * `refreshCart()` is then driven to completion so that the singleton
 * nonce-ready promise resolves and queued mutations are allowed to flush;
 * tests seed `state.cart` afterwards via {@link seedCart}.
 *
 * @return A promise resolving to the freshly bound cart plane's actions.
 */
async function loadCartStore(): Promise< RawCartActions > {
	jest.isolateModules( () => {
		const catalogModule =
			require( '../catalog' ) as typeof import('../catalog');
		const scopeModule = require( '../scope' ) as typeof import('../scope');
		const cartActionsModule =
			require( '../cart-actions' ) as typeof import('../cart-actions');

		mockState = {
			...catalogModule.catalogState,
			...cartActionsModule.cartActionsState,
			...baseState(),
		} as MockState;
		Object.defineProperties(
			mockState,
			Object.getOwnPropertyDescriptors( scopeModule.scopeState )
		);
		scopeModule.bindState( mockState );

		cartActions = cartActionsModule.cartActions;
		// The registered-actions parameter only matters for the retry
		// timeout and the page-sync listener (both untested here, and
		// no-ops against these raw, undriven generator functions); the
		// test drives `refreshCart` itself, below. `bindCartState`'s own
		// state type additionally reads `restUrl` / `nonce` /
		// `errorMessages`, no longer part of the published `WooCommerceStore`
		// state type `MockState` merges — hence the cast, as `index.ts`
		// itself does for the same reason.
		cartActionsModule.bindCartState(
			mockState as unknown as Parameters<
				typeof cartActionsModule.bindCartState
			>[ 0 ],
			{ refreshCart: cartActions.refreshCart }
		);
	} );

	// Drive the refresh so the module-level nonce-ready promise resolves.
	await runAction( cartActions.refreshCart() );
	return cartActions;
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
	} as unknown as MockState[ 'cart' ];
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
 * Installs a `global.fetch` mock whose batch responses return a caller-supplied
 * server cart instead of echoing the post-optimistic cart.
 *
 * @param serverCart The cart the batch endpoint should report as server state.
 */
function mockBatchFetchReturning( serverCart: Cart ): void {
	global.fetch = jest.fn(
		async ( _url: RequestInfo | URL, init?: RequestInit ) => {
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
 * Installs a `global.fetch` mock whose batch responses reject one targeted
 * mutation with an HTTP error status, reproducing a genuine server cap.
 *
 * @param options             Failure configuration.
 * @param options.failForPath The Store API path whose mutation should fail.
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
 */
function mockBatchFetchWholeBatchFailure(): void {
	global.fetch = jest.fn(
		async ( _url: RequestInfo | URL, init?: RequestInit ) => {
			if ( ! init?.body ) {
				return new Response(
					JSON.stringify( { items: [], totals: {}, errors: [] } ),
					{ headers: { Nonce: 'test-nonce-123' } }
				);
			}
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

/** The flat list of notices every `updateNotices` mock call received so far. */
function updateNoticesCalls(): Notice[] {
	return ( updateNotices as jest.Mock ).mock.calls.flatMap(
		( [ notices ] ) => ( notices ?? [] ) as Notice[]
	);
}

/** The errors every `showNoticeError` mock call received so far. */
function showNoticeErrorCalls(): Error[] {
	return ( showNoticeError as jest.Mock ).mock.calls.map(
		( [ error ] ) => error as Error
	);
}

describe( 'WooCommerce cart plane (cart-actions.ts)', () => {
	afterEach( () => {
		jest.clearAllMocks();
		// `getConfig` may have been given a per-test `mockReturnValue` (e.g. to
		// configure `addedToCartText`); `clearAllMocks` only clears call
		// history, so reset it explicitly to avoid leaking config into later
		// tests.
		( getConfig as jest.Mock ).mockReset();
		mockContext = null;
		delete ( mockState as Partial< MockState > ).cart;
	} );

	it( 'refreshCart passes cache: no-store to fetch to prevent browser caching', () => {
		const mockFetch = jest
			.fn()
			.mockResolvedValue(
				new Response(
					JSON.stringify( { items: [], totals: {}, errors: [] } )
				)
			);
		global.fetch = mockFetch;

		jest.isolateModules( () => {
			const scopeModule =
				require( '../scope' ) as typeof import('../scope');
			mockState = { ...baseState() } as MockState;
			Object.defineProperties(
				mockState,
				Object.getOwnPropertyDescriptors( scopeModule.scopeState )
			);
			scopeModule.bindState( mockState );
			const cartActionsModule =
				require( '../cart-actions' ) as typeof import('../cart-actions');
			cartActions = cartActionsModule.cartActions;
			cartActionsModule.bindCartState(
				mockState as unknown as Parameters<
					typeof cartActionsModule.bindCartState
				>[ 0 ],
				{ refreshCart: cartActions.refreshCart }
			);
		} );

		// Async actions are typed as void for consumers, but are actually
		// generators internally.
		( cartActions.refreshCart() as unknown as Iterator< void > ).next();

		expect( mockFetch ).toHaveBeenCalledWith(
			'https://example.com/wp-json/wc/store/v1/cart',
			expect.objectContaining( {
				method: 'GET',
				cache: 'no-store',
			} )
		);
	} );

	describe( 'addCartItem( payload ) — posts as given', () => {
		it( 'posts to add-item with the payload unchanged', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [] );

			await runAction( actions.addCartItem( { id: 42, quantity: 2 } ) );

			expect( captured ).toHaveLength( 1 );
			expect( captured[ 0 ].path ).toBe( '/wc/store/v1/cart/add-item' );
			expect( captured[ 0 ].body ).toEqual( { id: 42, quantity: 2 } );
		} );

		it( 'carries no type or quantityToAdd in the posted body', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [] );

			await runAction( actions.addCartItem( { id: 42, quantity: 2 } ) );

			expect( captured[ 0 ].body ).not.toHaveProperty( 'type' );
			expect( captured[ 0 ].body ).not.toHaveProperty( 'quantityToAdd' );
		} );

		it( 'forwards extension props to the Store API as given', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [] );

			await runAction(
				actions.addCartItem( {
					id: 42,
					quantity: 1,
					giftMessage: 'Happy birthday!',
				} )
			);

			expect( captured[ 0 ].body.giftMessage ).toBe( 'Happy birthday!' );
		} );

		it( 'bumps a matching existing line by the posted delta, optimistically', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await runAction( actions.addCartItem( { id: 42, quantity: 1 } ) );

			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ].quantity ).toBe( 4 );
		} );

		it( 'does not bump a sold-individually line ahead of the server', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( {
					id: 42,
					quantity: 1,
					sold_individually: true,
				} ),
			] );

			await runAction( actions.addCartItem( { id: 42, quantity: 1 } ) );

			expect( mockState.cart.items[ 0 ].quantity ).toBe( 1 );
		} );

		it( 'pushes a new optimistic line when no line matches', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await runAction( actions.addCartItem( { id: 99, quantity: 2 } ) );

			expect( mockState.cart.items ).toHaveLength( 2 );
			const added = mockState.cart.items.find(
				( item ) => item.id === 99
			);
			expect( added?.quantity ).toBe( 2 );
		} );

		it( 'defaults the posted quantity to 1 when omitted, for the optimistic bump', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await runAction( actions.addCartItem( { id: 42 } ) );

			expect( mockState.cart.items[ 0 ].quantity ).toBe( 4 );
		} );

		it( 'removes no scope record when called with a payload', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [] );
			mockContext = { productId: 42, scopeName: 'my-scope' };
			// Writing the draft creates the record.
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			mockState.productScope.draftCartItem!.quantity = 5;
			expect( mockState.productScopes[ 'my-scope' ] ).toBeDefined();

			await runAction( actions.addCartItem( { id: 99, quantity: 1 } ) );

			expect( mockState.productScopes[ 'my-scope' ] ).toBeDefined();
		} );
	} );

	describe( 'addCartItem() — the no-payload draft form', () => {
		it( 'posts the reading element scope’s draft identity and quantity', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [] );
			mockContext = {
				productId: 42,
				variation: [ { attribute: 'Color', value: 'Blue' } ],
				scopeName: 'my-scope',
			};
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			mockState.productScope.draftCartItem!.quantity = 3;

			await runAction( actions.addCartItem() );

			expect( captured[ 0 ].body ).toEqual( {
				id: 42,
				variation: [ { attribute: 'Color', value: 'Blue' } ],
				quantity: 3,
			} );
		} );

		it( 'forwards extension props written on the draft', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [] );
			mockContext = { productId: 42, scopeName: 'my-scope' };
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			mockState.productScope.draftCartItem!.giftMessage = 'Hi!';

			await runAction( actions.addCartItem() );

			expect( captured[ 0 ].body.giftMessage ).toBe( 'Hi!' );
		} );

		it( 'defaults to quantity 1 when the shopper never set one', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [] );
			mockContext = { productId: 7 };

			await runAction( actions.addCartItem() );

			expect( captured[ 0 ].body ).toEqual( { id: 7, quantity: 1 } );
		} );

		it( 'behaves the same when called with a DOM Event as its first argument (directive-bound call)', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [] );
			mockContext = { productId: 7 };

			await runAction( actions.addCartItem( new Event( 'click' ) ) );

			expect( captured[ 0 ].body ).toEqual( { id: 7, quantity: 1 } );
		} );

		it( 'removes the scope’s record when the server accepts the add', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [] );
			mockContext = { productId: 42, scopeName: 'my-scope' };
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			mockState.productScope.draftCartItem!.quantity = 2;
			expect( mockState.productScopes[ 'my-scope' ] ).toBeDefined();

			await runAction( actions.addCartItem() );

			expect( mockState.productScopes[ 'my-scope' ] ).toBeUndefined();
		} );

		it( 'removes no record when the server rejects the add', async () => {
			mockBatchFetchFailing( {
				failForPath: '/wc/store/v1/cart/add-item',
			} );
			const actions = await loadCartStore();
			seedCart( [] );
			mockContext = { productId: 42, scopeName: 'my-scope' };
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			mockState.productScope.draftCartItem!.quantity = 2;

			await runAction( actions.addCartItem() );

			expect( mockState.productScopes[ 'my-scope' ] ).toBeDefined();
		} );

		it( 'falls back to the unnamed _default scope when the context declares no scopeName', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [] );
			mockContext = { productId: 42 };
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			mockState.productScope.draftCartItem!.quantity = 1;

			await runAction( actions.addCartItem() );

			expect( mockState.productScopes._default ).toBeUndefined();
		} );
	} );

	describe( 'addCartItem resolved outcome', () => {
		it( 'resolves { success: true } when the Store API accepts the request', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [] );

			const outcome = await runAction(
				actions.addCartItem( { id: 42, quantity: 1 } )
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

			const outcome = await runAction(
				actions.addCartItem( { id: 42, quantity: 1 } )
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

			const outcome = ( await runAction(
				actions.addCartItem( { id: 42, quantity: 1 } )
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

			const [ acceptedOutcome, rejectedOutcome ] = await Promise.all( [
				runAction( actions.addCartItem( { id: 42, quantity: 1 } ) ),
				runAction( actions.addCartItem( { id: 99, quantity: 1 } ) ),
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
			// Simulate a client bug in post-success processing (e.g. a
			// notices import rejection): the captured success outcome must
			// survive this throw.
			( updateNotices as jest.Mock ).mockImplementationOnce( () => {
				throw new Error( 'post-success client bug' );
			} );

			const outcome = await runAction(
				actions.addCartItem( { id: 42, quantity: 1 } )
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
				actions.addCartItem( { id: 42, quantity: 1 } )
			);

			expect( outcome ).toEqual( { success: true } );
		} );
	} );

	describe( 'addCartItem notices', () => {
		const QUANTITY_CHANGED = 'was changed to';

		it( 'with { showCartUpdatesNotices: false } shows no auto-update notice', async () => {
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

			await runAction(
				actions.addCartItem(
					{ id: 42, quantity: 1 },
					{ showCartUpdatesNotices: false }
				)
			);

			expect(
				updateNoticesCalls().some( ( n ) =>
					n.notice.includes( QUANTITY_CHANGED )
				)
			).toBe( false );
		} );

		it( 'with the option omitted shows the auto-update notice for a genuine server change', async () => {
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

			await runAction( actions.addCartItem( { id: 42, quantity: 1 } ) );

			expect(
				updateNoticesCalls().some(
					( n ) =>
						n.notice.includes( QUANTITY_CHANGED ) &&
						n.notice.includes( '7' )
				)
			).toBe( true );
		} );

		it( 'shows the auto-removal notice for a line the server dropped', async () => {
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

			await runAction( actions.addCartItem( { id: 42, quantity: 1 } ) );

			expect(
				updateNoticesCalls().some( ( n ) =>
					n.notice.includes( 'Vanished Product' )
				)
			).toBe( true );
		} );

		it( 'shows a dismissible error notice with the server’s message for a failed mutation, not an auto-update notice', async () => {
			mockBatchFetchFailing( {
				failForPath: '/wc/store/v1/cart/add-item',
				status: 400,
				code: 'woocommerce_rest_cart_product_no_stock',
				message: 'You cannot add that amount to the cart.',
			} );
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await runAction( actions.addCartItem( { id: 42, quantity: 1 } ) );

			const errors = showNoticeErrorCalls();
			expect( errors ).toHaveLength( 1 );
			expect( errors[ 0 ].message ).toBe(
				'You cannot add that amount to the cart.'
			);
			expect( ( errors[ 0 ] as Error & { code?: string } ).code ).toBe(
				'woocommerce_rest_cart_product_no_stock'
			);
			expect(
				updateNoticesCalls().some( ( n ) =>
					n.notice.includes( QUANTITY_CHANGED )
				)
			).toBe( false );
		} );

		it( 'rolls the optimistic bump back when the add-item request is capped (HTTP 400)', async () => {
			mockBatchFetchFailing( {
				failForPath: '/wc/store/v1/cart/add-item',
				status: 400,
			} );
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine( { id: 42, quantity: 3 } ) ] );

			await runAction( actions.addCartItem( { id: 42, quantity: 1 } ) );

			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ].quantity ).toBe( 3 );
		} );

		it( 'shows no quantity-changed notice for an exact add the server caps (server total equals pre-add total plus posted delta)', async () => {
			// Pre-add: matched line at qty 3. Delta: +1. Expected total: 4.
			// Server returns the line at exactly 4 → the add was exact, so no
			// "quantity changed" notice fires even though the server chose a
			// different line internally than the one bumped optimistically.
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

			await runAction( actions.addCartItem( { id: 42, quantity: 1 } ) );

			expect(
				updateNoticesCalls().some( ( n ) =>
					n.notice.includes( QUANTITY_CHANGED )
				)
			).toBe( false );
		} );

		it( 'matches a variation line by id and variation for the exactness check', async () => {
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

			await runAction(
				actions.addCartItem( {
					id: 42,
					quantity: 1,
					variation: colorRedVariation,
				} )
			);

			expect(
				updateNoticesCalls().some( ( n ) =>
					n.notice.includes( QUANTITY_CHANGED )
				)
			).toBe( false );
		} );
	} );

	describe( 'updateCartItem', () => {
		it( 'sets the cart line’s absolute quantity via update-item', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( { key: 'server-key-abc', id: 42, quantity: 3 } ),
			] );

			await runAction(
				actions.updateCartItem( { key: 'server-key-abc', quantity: 5 } )
			);

			expect( captured[ 0 ].path ).toBe(
				'/wc/store/v1/cart/update-item'
			);
			expect( captured[ 0 ].body.quantity ).toBe( 5 );
			expect( mockState.cart.items[ 0 ].quantity ).toBe( 5 );
		} );

		it( 'shows the quantity-changed notice unconditionally (no exactness suppression) for a keyed change', async () => {
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

			await runAction(
				actions.updateCartItem( { key: 'server-key-abc', quantity: 5 } )
			);

			expect(
				updateNoticesCalls().some( ( n ) =>
					n.notice.includes( 'was changed to' )
				)
			).toBe( true );
		} );
	} );

	describe( 'removeCartItem', () => {
		it( 'removes the line immediately (optimistically)', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( { key: 'server-key-abc', id: 42, quantity: 3 } ),
			] );

			await runAction( actions.removeCartItem( 'server-key-abc' ) );

			expect( mockState.cart.items ).toHaveLength( 0 );
		} );

		it( 'still emits the auto-removal notice for another server-removed line (unaffected by the keyless suppression rule)', async () => {
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

			await runAction( actions.removeCartItem( 'server-key-keep' ) );

			expect(
				updateNoticesCalls().some( ( n ) =>
					n.notice.includes( 'Vanished Product' )
				)
			).toBe( true );
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

			await runAction( actions.addCartItem( { id: 1, quantity: 1 } ) );
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

		it( 'A5 — dispatches one batch request and one set of page-sync effects for a same-tick burst of addCartItem calls', async () => {
			( getConfig as jest.Mock ).mockReturnValue( {
				messages: { addedToCartText: 'Added to your cart.' },
			} );
			const captured = mockBatchFetch();
			const fetchMock = global.fetch as jest.Mock;
			const actions = await loadCartStore();
			seedCart( [] );
			const { events, cleanup } = captureSyncEvents();

			await Promise.all( [
				runAction( actions.addCartItem( { id: 1, quantity: 1 } ) ),
				runAction( actions.addCartItem( { id: 2, quantity: 1 } ) ),
				runAction( actions.addCartItem( { id: 3, quantity: 1 } ) ),
			] );
			await flushMicrotasks();

			// One POST wc/store/v1/batch request carrying the three mutations
			// (the GET /cart refresh from loadCartStore is the only other
			// fetch call).
			const batchCalls = fetchMock.mock.calls.filter( ( [ url ] ) =>
				String( url ).includes( 'wc/store/v1/batch' )
			);
			expect( batchCalls ).toHaveLength( 1 );
			expect( captured ).toHaveLength( 3 );

			// One set of page-sync effects: one sync event, one legacy event,
			// one announcement.
			expect( events ).toHaveLength( 1 );
			expect( triggerAddedToCartEvent ).toHaveBeenCalledTimes( 1 );
			expect( speak ).toHaveBeenCalledTimes( 1 );

			cleanup();
		} );

		it( 'A7 — N same-tick adds of a product at quantity 1 each post N deltas of 1, and the cart line lands at quantity N once the server’s merged response commits', async () => {
			// A real Store API batch runs each `add-item` sub-request
			// sequentially against one `WC_Cart` session, so N same-tick
			// deltas of 1 land the server on quantity N. Reproduce that
			// merged response directly, since the optimistic layer alone
			// cannot know the other same-tick calls will land on the same
			// line before the server confirms it (D8's "N rapid clicks add
			// N" claim is about the posted deltas and the settled result,
			// not the transient optimistic render).
			const N = 3;
			const captured: CapturedRequest[] = [];
			global.fetch = jest.fn(
				async ( _url: RequestInfo | URL, init?: RequestInit ) => {
					if ( ! init?.body ) {
						return new Response(
							JSON.stringify( {
								items: [],
								totals: {},
								errors: [],
							} ),
							{ headers: { Nonce: 'test-nonce-123' } }
						);
					}
					const parsed = JSON.parse( init.body as string ) as {
						requests: CapturedRequest[];
					};
					parsed.requests.forEach( ( request ) =>
						captured.push( request )
					);
					const serverCart = makeServerCart( [
						makeKeyedLine( { id: 1, quantity: N } ),
					] );
					const responses = parsed.requests.map( () => ( {
						status: 200,
						body: serverCart,
					} ) );
					return new Response( JSON.stringify( { responses } ), {
						headers: { Nonce: 'test-nonce-123' },
					} );
				}
			) as unknown as typeof fetch;
			const actions = await loadCartStore();
			seedCart( [] );

			await Promise.all(
				Array.from( { length: N }, () =>
					runAction( actions.addCartItem( { id: 1, quantity: 1 } ) )
				)
			);

			expect( captured ).toHaveLength( N );
			expect( captured.every( ( r ) => r.body.quantity === 1 ) ).toBe(
				true
			);
			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ].quantity ).toBe( N );
		} );

		it( 'still dispatches exactly one sync event for a cycle mixing a successful and a failed mutation, and surfaces the failure as its own error notice', async () => {
			( getConfig as jest.Mock ).mockReturnValue( {} );
			mockBatchFetchFailingProduct( { failForId: 99 } );
			const actions = await loadCartStore();
			seedCart( [] );
			const { events, cleanup } = captureSyncEvents();

			const [ acceptedOutcome, rejectedOutcome ] = await Promise.all( [
				runAction( actions.addCartItem( { id: 42, quantity: 1 } ) ),
				runAction( actions.addCartItem( { id: 99, quantity: 1 } ) ),
			] );
			await flushMicrotasks();

			expect( acceptedOutcome ).toEqual( { success: true } );
			expect( ( rejectedOutcome as AddCartItemOutcome ).success ).toBe(
				false
			);
			expect( events ).toHaveLength( 1 );
			expect( showNoticeErrorCalls() ).toHaveLength( 1 );

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

			await runAction( actions.addCartItem( { id: 42, quantity: 1 } ) );
			await flushMicrotasks();

			expect( events ).toHaveLength( 0 );
			expect( triggerAddedToCartEvent ).not.toHaveBeenCalled();
			expect( speak ).not.toHaveBeenCalled();
			expect( showNoticeErrorCalls() ).toHaveLength( 1 );

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
				runAction( actions.addCartItem( { id: 99, quantity: 1 } ) ),
				runAction(
					actions.updateCartItem( {
						key: 'server-key-abc',
						quantity: 5,
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
				runAction( actions.addCartItem( { id: 42, quantity: 1 } ) ),
				runAction( actions.addCartItem( { id: 42, quantity: 1 } ) ),
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

		it( 'announces once via the already-resolved a11y binding on a later call in the same store instance', async () => {
			( getConfig as jest.Mock ).mockReturnValue( {
				messages: { addedToCartText: 'Added to your cart.' },
			} );
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [] );

			await runAction( actions.addCartItem( { id: 1, quantity: 1 } ) );
			await flushMicrotasks();

			await runAction( actions.addCartItem( { id: 2, quantity: 1 } ) );
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
			await runAction( actions.addCartItem( { id: 1, quantity: 1 } ) );
			await flushMicrotasks();

			( speak as jest.Mock ).mockImplementation( () => {
				throw new Error( 'a11y unavailable' );
			} );

			const { events, cleanup } = captureSyncEvents();
			const outcome = await runAction(
				actions.addCartItem( { id: 2, quantity: 1 } )
			);
			await flushMicrotasks();

			expect( outcome ).toEqual( { success: true } );
			expect( events ).toHaveLength( 1 );
			expect( triggerAddedToCartEvent ).toHaveBeenCalledTimes( 2 );

			cleanup();
		} );
	} );

	describe( 'compatibility members (T18 deletes these)', () => {
		it( 'findItemInCart matches by key, then by id and variation', async () => {
			mockBatchFetch();
			await loadCartStore();
			seedCart( [
				makeKeyedLine( { key: 'server-key-abc', id: 42, quantity: 3 } ),
			] );

			expect(
				mockState.findItemInCart( { id: 42, key: 'server-key-abc' } )
					?.key
			).toBe( 'server-key-abc' );
			expect( mockState.findItemInCart( { id: 42 } )?.key ).toBe(
				'server-key-abc'
			);
			expect( mockState.findItemInCart( { id: 999 } ) ).toBeUndefined();
		} );

		it( 'addCartItem( { key, quantity, ... } ) — the keyed compatibility form — delegates to update-item, ignoring the caller’s id/variation/type', async () => {
			const captured = mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [
				makeKeyedLine( { key: 'server-key-abc', id: 42, quantity: 3 } ),
			] );

			await runAction(
				actions.addCartItem( {
					id: 999,
					key: 'server-key-abc',
					quantity: 5,
					variation: [ { attribute: 'Color', value: 'Red' } ],
					type: 'simple',
				} )
			);

			expect( captured[ 0 ].path ).toBe(
				'/wc/store/v1/cart/update-item'
			);
			expect( captured[ 0 ].body.quantity ).toBe( 5 );
			expect( captured[ 0 ].body.id ).toBe( 42 );
			expect( mockState.cart.items[ 0 ].quantity ).toBe( 5 );
		} );

		it( 'refreshCartItems is an alias for refreshCart', async () => {
			const actions = await loadCartStore();
			seedCart( [ makeKeyedLine() ] );
			mockBatchFetchReturning( makeServerCart( [] ) );

			await runAction( actions.refreshCartItems() );

			expect( mockState.cart.items ).toHaveLength( 0 );
		} );

		it( 'waitForIdle resolves once a pending mutation settles', async () => {
			mockBatchFetch();
			const actions = await loadCartStore();
			seedCart( [] );

			const addPromise = runAction(
				actions.addCartItem( { id: 1, quantity: 1 } )
			);
			// Let `sendCartRequest`'s own microtask boundary (its `await
			// isNonceReady`) run, so the mutation queue exists before
			// `waitForIdle` reads it.
			await flushMicrotasks();
			await runAction( actions.waitForIdle() );
			await addPromise;

			expect( mockState.cart.items[ 0 ]?.quantity ).toBe( 1 );
		} );

		it( 'waitForIdle resolves immediately when the queue is idle', async () => {
			const actions = await loadCartStore();

			await expect(
				runAction( actions.waitForIdle() )
			).resolves.toBeUndefined();
		} );

		describe( 'batchAddCartItems', () => {
			it( 'issues add-item for a keyless item and update-item for a keyed item in the same call', async () => {
				const captured = mockBatchFetch();
				const actions = await loadCartStore();
				seedCart( [
					makeKeyedLine( {
						key: 'server-key-abc',
						id: 42,
						quantity: 3,
					} ),
				] );

				await runAction(
					actions.batchAddCartItems( [
						{ id: 99, quantityToAdd: 2, type: 'simple' },
						{
							id: 42,
							key: 'server-key-abc',
							quantity: 5,
							type: 'simple',
						},
					] )
				);

				expect( captured ).toHaveLength( 2 );
				expect( captured.find( ( r ) => r.body.id === 99 )?.path ).toBe(
					'/wc/store/v1/cart/add-item'
				);
				expect(
					captured.find( ( r ) => r.body.key === 'server-key-abc' )
						?.path
				).toBe( '/wc/store/v1/cart/update-item' );
			} );

			it( 'bumps a matched keyless line optimistically by the delta and sets a keyed line to its absolute quantity', async () => {
				mockBatchFetch();
				const actions = await loadCartStore();
				seedCart( [
					makeKeyedLine( {
						key: 'server-key-abc',
						id: 42,
						quantity: 3,
					} ),
				] );

				await runAction(
					actions.batchAddCartItems( [
						{ id: 42, quantityToAdd: 1, type: 'simple' },
					] )
				);

				expect( mockState.cart.items ).toHaveLength( 1 );
				expect( mockState.cart.items[ 0 ].quantity ).toBe( 4 );
			} );

			it( 'produces one combined set of notices and suppresses an exact keyless add across the whole call', async () => {
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
					makeKeyedLine( {
						key: 'server-key-abc',
						id: 42,
						quantity: 3,
					} ),
				] );

				await runAction(
					actions.batchAddCartItems( [
						{ id: 42, quantityToAdd: 1, type: 'simple' },
						{ id: 42, quantityToAdd: 1, type: 'simple' },
					] )
				);

				// Server total (5) === expected total (3+1+1=5) → suppress.
				expect(
					updateNoticesCalls().some( ( n ) =>
						n.notice.includes( 'was changed to' )
					)
				).toBe( false );
			} );

			it( 'shows the error notice for a failed item without surfacing it as an unhandled rejection', async () => {
				mockBatchFetchFailingProduct( { failForId: 99 } );
				const actions = await loadCartStore();
				seedCart( [] );

				await runAction(
					actions.batchAddCartItems( [
						{ id: 42, quantityToAdd: 1, type: 'simple' },
						{ id: 99, quantityToAdd: 1, type: 'simple' },
					] )
				);

				expect(
					updateNoticesCalls().some( ( n ) => n.type === 'error' )
				).toBe( true );
			} );

			it( 'dispatches exactly one sync/legacy/announcement set for a call where one item fails and another succeeds', async () => {
				( getConfig as jest.Mock ).mockReturnValue( {
					messages: { addedToCartText: 'Added to your cart.' },
				} );
				mockBatchFetchFailingProduct( { failForId: 99 } );
				const actions = await loadCartStore();
				seedCart( [] );
				const { events, cleanup } = captureSyncEvents();

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

				cleanup();
			} );
		} );
	} );
} );
