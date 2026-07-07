/**
 * Internal dependencies
 */
import type { Store } from '../cart';

type MockStore = { state: Store[ 'state' ]; actions: Store[ 'actions' ] };

let mockRegisteredStore: MockStore | null = null;
const mockState = {} as Store[ 'state' ];
const mockAddNotice = jest.fn( () => 'notice-id' );

// `restUrl` (and the other infra values) now live in
// `wp_interactivity_config( 'woocommerce' )`, so the cart store reads them via
// `getConfig` instead of from reactive state.
const mockConfig: {
	restUrl: string;
	nonce: string;
	messages?: { addedToCartText?: string };
} = {
	restUrl: 'https://example.com/wp-json/',
	nonce: 'test-nonce-123',
};

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: jest.fn( () => mockConfig ),
		getContext: jest.fn( () => null ),
		// The cart store registers under `woocommerce/cart`. Merge each
		// registration's `state` into the single shared `mockState` and record
		// the actions. We skip the `cart` and `draftItems` keys: the module keeps
		// both as plain writable slots (`cart` set by refresh / the batcher
		// commit; `draftItems` mutated by the draft actions).
		store: jest.fn( ( name, definition ) => {
			// The notices store is a separate registration; return a stub with
			// the notice bookkeeping the cart actions call into.
			if ( name === 'woocommerce/store-notices' ) {
				return {
					state: { notices: [] },
					actions: {
						addNotice: mockAddNotice,
						removeNotice: jest.fn(),
					},
				};
			}
			// The cart store reads the products store for purchasable-id
			// resolution and the context product id. Stub it: `findProduct`
			// echoes the id (no variation swap), and there is no context product.
			if ( name === 'woocommerce/products' ) {
				return {
					state: {
						findProduct: ( { id }: { id: number } ) => ( { id } ),
						mainProductInContext: undefined,
						productInContext: undefined,
					},
				};
			}
			if ( definition?.state ) {
				for ( const key of Object.keys( definition.state ) ) {
					if ( key === 'cart' || key === 'draftItems' ) {
						continue;
					}
					const descriptor = Object.getOwnPropertyDescriptor(
						definition.state,
						key
					);
					if ( descriptor ) {
						Object.defineProperty( mockState, key, descriptor );
					}
				}
			}
			mockRegisteredStore = {
				state: mockState,
				actions: definition?.actions ?? mockRegisteredStore?.actions,
			};
			return mockRegisteredStore;
		} ),
	} ),
	{ virtual: true }
);

const mockTriggerAddedToCartEvent = jest.fn();
jest.mock( '../legacy-events', () => ( {
	triggerAddedToCartEvent: ( ...args: unknown[] ) =>
		mockTriggerAddedToCartEvent( ...args ),
} ) );

const mockSpeak = jest.fn();
jest.mock(
	'@wordpress/a11y',
	() => ( {
		speak: ( ...args: unknown[] ) => mockSpeak( ...args ),
	} ),
	{ virtual: true }
);

// The cart store adds/removes notices via the store-notices store, resolved
// through `store( 'woocommerce/store-notices' )` (mocked above) plus a dynamic
// `import( '@woocommerce/stores/store-notices' )`. Mock that module so the
// import resolves; the actual notice bookkeeping goes through the mocked
// `store()`, which returns the cart actions object — good enough to assert the
// cart calls `updateNotices`/`showNoticeError` the right number of times, which
// we do by spying on the cart's own action functions.
jest.mock( '../store-notices', () => ( {} ), { virtual: true } );

/**
 * Drive an iAPI async-action generator to completion.
 *
 * The mocked `store()` records the raw generator functions (not the iAPI
 * runtime-wrapped versions), so calling an action returns a generator we must
 * pump ourselves: forward yielded promises' resolved values back in, and stop
 * at `done`.
 */
async function runAction(
	iterator:
		| ( Iterator< unknown > & {
				throw?: ( e: unknown ) => IteratorResult< unknown >;
		  } )
		| undefined
): Promise< void > {
	if ( ! iterator ) {
		return;
	}
	let next = iterator.next();
	while ( ! next.done ) {
		try {
			// eslint-disable-next-line no-await-in-loop
			const resolved = await Promise.resolve( next.value );
			next = iterator.next( resolved );
		} catch ( error ) {
			// Feed rejections back into the generator so the action's own
			// try/catch runs (mirrors how the iAPI runtime drives async
			// actions on a rejected yield).
			if ( ! iterator.throw ) {
				throw error;
			}
			next = iterator.throw( error );
		}
	}
}

/**
 * Build a mocked global.fetch that:
 * - answers the initial GET /cart (the cart refresh) with an empty cart, and
 * - answers POST /batch with one response item per request.
 *
 * `onBatch` receives the parsed request bodies for assertions/customization and
 * returns the per-item response objects.
 */
function installFetchMock( {
	initialCart = { items: [], totals: {}, errors: [] },
	onBatch,
}: {
	initialCart?: unknown;
	onBatch: ( requests: Array< { path: string; body: unknown } > ) => Array< {
		status: number;
		body: unknown;
	} >;
} ) {
	const batchCalls: Array< Array< { path: string; body: unknown } > > = [];

	const fetchMock = jest.fn(
		( url: string, options?: RequestInit ): Promise< Response > => {
			if ( url.endsWith( '/wc/store/v1/cart' ) ) {
				return Promise.resolve( {
					ok: true,
					headers: { get: () => 'refreshed-nonce' },
					json: () => Promise.resolve( initialCart ),
				} as unknown as Response );
			}

			// Batch endpoint.
			const parsed = JSON.parse( options?.body as string );
			const requests = parsed.requests as Array< {
				path: string;
				body: unknown;
			} >;
			batchCalls.push( requests );
			const responses = onBatch( requests );
			return Promise.resolve( {
				ok: true,
				headers: { get: () => 'refreshed-nonce' },
				json: () => Promise.resolve( { responses } ),
			} as unknown as Response );
		}
	);

	global.fetch = fetchMock as unknown as typeof fetch;
	return { fetchMock, batchCalls };
}

/**
 * Load the cart module fresh, run the initial refresh so the nonce becomes
 * ready (unblocking the batcher), and return the recorded actions.
 */
async function loadCartAndReady() {
	let mod: MockStore | null = null;
	jest.isolateModules( () => {
		require( '../cart' );
		mod = mockRegisteredStore;
	} );

	// The module kicks off refresh() on load, which is what resolves
	// `isNonceReady`. Drive it to completion so subsequent add/remove requests
	// can flow through the batcher.
	await runAction(
		mockRegisteredStore?.actions.refresh() as unknown as Iterator< unknown >
	);
	// Let the refresh's promise chain settle.
	await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

	return mod as unknown as MockStore;
}

describe( 'WooCommerce Cart Interactivity API Store', () => {
	let syncEvents: CustomEvent[] = [];
	let syncListener: ( ( e: Event ) => void ) | null = null;
	let updateNoticesSpy: jest.SpyInstance | null = null;

	beforeEach( () => {
		jest.clearAllMocks();
		// showNoticeError logs to console.error for troubleshooting; silence it.
		jest.spyOn( console, 'error' ).mockImplementation( () => undefined );
		mockConfig.messages = { addedToCartText: 'Product added to cart.' };
		syncEvents = [];
		syncListener = ( e: Event ) => {
			syncEvents.push( e as CustomEvent );
		};
		window.addEventListener(
			'wc-blocks_store_sync_required',
			syncListener
		);
	} );

	afterEach( () => {
		if ( syncListener ) {
			window.removeEventListener(
				'wc-blocks_store_sync_required',
				syncListener
			);
		}
		updateNoticesSpy?.mockRestore();
		updateNoticesSpy = null;
	} );

	// Count only iAPI-emitted sync events (the ones we care about carry
	// type 'from_iAPI'); ignore any others.
	const iapiSyncEvents = () =>
		syncEvents.filter( ( e ) => e.detail?.type === 'from_iAPI' );

	it( 'refresh passes cache: no-store to fetch to prevent browser caching', () => {
		const mockFetch = jest
			.fn()
			.mockResolvedValue(
				new Response(
					JSON.stringify( { items: [], totals: {}, errors: [] } )
				)
			);
		global.fetch = mockFetch;

		jest.isolateModules( () => require( '../cart' ) );

		const iterator = mockRegisteredStore?.actions.refresh();

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

	it( 'N same-frame addItem calls → one batch request, one sync event, one legacy event, one a11y announcement, one notice pass', async () => {
		const serverCart = {
			items: [
				{ key: 'a', id: 1, quantity: 1, name: 'A', type: 'simple' },
				{ key: 'b', id: 2, quantity: 1, name: 'B', type: 'simple' },
				{ key: 'c', id: 3, quantity: 1, name: 'C', type: 'simple' },
			],
			totals: {},
			errors: [],
		};
		const { batchCalls } = installFetchMock( {
			onBatch: ( requests ) =>
				requests.map( () => ( { status: 200, body: serverCart } ) ),
		} );

		const cart = await loadCartAndReady();
		updateNoticesSpy = jest.spyOn( cart.actions, 'updateNotices' );

		// Three adds dispatched in the same frame (no await between them).
		await Promise.all( [
			runAction(
				cart.actions.addItem( {
					id: 1,
					quantity: 1,
				} ) as unknown as Iterator< unknown >
			),
			runAction(
				cart.actions.addItem( {
					id: 2,
					quantity: 1,
				} ) as unknown as Iterator< unknown >
			),
			runAction(
				cart.actions.addItem( {
					id: 3,
					quantity: 1,
				} ) as unknown as Iterator< unknown >
			),
		] );

		// Exactly ONE batch request carrying all three items.
		expect( batchCalls ).toHaveLength( 1 );
		expect( batchCalls[ 0 ] ).toHaveLength( 3 );

		// Exactly ONE sync event for the whole cycle, aggregating all adds.
		expect( iapiSyncEvents() ).toHaveLength( 1 );
		expect(
			iapiSyncEvents()[ 0 ].detail.quantityChanges.productsPendingAdd
		).toEqual( [ 1, 2, 3 ] );

		// One legacy event and one a11y announcement.
		expect( mockTriggerAddedToCartEvent ).toHaveBeenCalledTimes( 1 );
		expect( mockSpeak ).toHaveBeenCalledTimes( 1 );

		// One consolidated notice pass.
		expect( updateNoticesSpy ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'mixed success/failure cycle still fires exactly one sync/legacy/a11y set', async () => {
		const serverCart = {
			items: [
				{ key: 'a', id: 1, quantity: 1, name: 'A', type: 'simple' },
			],
			totals: {},
			errors: [],
		};
		const { batchCalls } = installFetchMock( {
			onBatch: ( requests ) =>
				requests.map( ( _req, index ) =>
					index === 0
						? {
								status: 400,
								body: {
									message: 'Out of stock',
									code: 'out_of_stock',
								},
						  }
						: { status: 200, body: serverCart }
				),
		} );

		const cart = await loadCartAndReady();

		await Promise.all( [
			runAction(
				cart.actions.addItem( {
					id: 99,
					quantity: 1,
				} ) as unknown as Iterator< unknown >
			),
			runAction(
				cart.actions.addItem( {
					id: 1,
					quantity: 1,
				} ) as unknown as Iterator< unknown >
			),
		] );

		// One batch with both items.
		expect( batchCalls ).toHaveLength( 1 );
		expect( batchCalls[ 0 ] ).toHaveLength( 2 );

		// At least one success → events fire exactly once.
		expect( iapiSyncEvents() ).toHaveLength( 1 );
		expect( mockTriggerAddedToCartEvent ).toHaveBeenCalledTimes( 1 );
		expect( mockSpeak ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not fire sync/legacy/a11y events when all requests fail', async () => {
		installFetchMock( {
			onBatch: ( requests ) =>
				requests.map( () => ( {
					status: 500,
					body: { message: 'Server error', code: 'server_error' },
				} ) ),
		} );

		const cart = await loadCartAndReady();

		await Promise.all( [
			runAction(
				cart.actions.addItem( {
					id: 1,
					quantity: 1,
				} ) as unknown as Iterator< unknown >
			),
			runAction(
				cart.actions.addItem( {
					id: 2,
					quantity: 1,
				} ) as unknown as Iterator< unknown >
			),
		] );

		// No success in the cycle → no sync event, no legacy event, no a11y.
		expect( iapiSyncEvents() ).toHaveLength( 0 );
		expect( mockTriggerAddedToCartEvent ).not.toHaveBeenCalled();
		expect( mockSpeak ).not.toHaveBeenCalled();
	} );

	it( 'a pure removal cycle fires the sync event but NOT the legacy add event or a11y', async () => {
		const serverCart = { items: [], totals: {}, errors: [] };
		installFetchMock( {
			initialCart: {
				items: [
					{
						key: 'x',
						id: 5,
						quantity: 1,
						name: 'X',
						type: 'simple',
					},
				],
				totals: {},
				errors: [],
			},
			onBatch: ( requests ) =>
				requests.map( () => ( { status: 200, body: serverCart } ) ),
		} );

		const cart = await loadCartAndReady();

		await runAction(
			cart.actions.removeItem( 'x' ) as unknown as Iterator< unknown >
		);

		// Sync event fires (removal changed the cart)…
		expect( iapiSyncEvents() ).toHaveLength( 1 );
		expect(
			iapiSyncEvents()[ 0 ].detail.quantityChanges.cartItemsPendingDelete
		).toEqual( [ 'x' ] );
		// …but this is not an add, so no legacy add event / no announcement.
		expect( mockTriggerAddedToCartEvent ).not.toHaveBeenCalled();
		expect( mockSpeak ).not.toHaveBeenCalled();
	} );

	it( 'removeItem posts a remove-item request by key and fires the sync event', async () => {
		const serverCart = { items: [], totals: {}, errors: [] };
		const { batchCalls } = installFetchMock( {
			initialCart: {
				items: [
					{ key: 'y', id: 5, quantity: 1, name: 'Y', type: 'simple' },
				],
				totals: {},
				errors: [],
			},
			onBatch: ( requests ) =>
				requests.map( () => ( { status: 200, body: serverCart } ) ),
		} );

		const cart = await loadCartAndReady();

		await runAction(
			cart.actions.removeItem( 'y' ) as unknown as Iterator< unknown >
		);

		expect( batchCalls ).toHaveLength( 1 );
		expect( batchCalls[ 0 ][ 0 ].path ).toBe(
			'/wc/store/v1/cart/remove-item'
		);
		expect( batchCalls[ 0 ][ 0 ].body ).toEqual( { key: 'y' } );
		expect(
			iapiSyncEvents()[ 0 ].detail.quantityChanges.cartItemsPendingDelete
		).toEqual( [ 'y' ] );
	} );

	it( 'addItem posts an add-item request carrying the payload id and quantity', async () => {
		const serverCart = {
			items: [
				{ key: 'a', id: 7, quantity: 2, name: 'A', type: 'simple' },
			],
			totals: {},
			errors: [],
		};
		const { batchCalls } = installFetchMock( {
			onBatch: ( requests ) =>
				requests.map( () => ( { status: 200, body: serverCart } ) ),
		} );

		const cart = await loadCartAndReady();

		await runAction(
			cart.actions.addItem( {
				id: 7,
				quantity: 2,
			} ) as unknown as Iterator< unknown >
		);

		expect( batchCalls ).toHaveLength( 1 );
		expect( batchCalls[ 0 ][ 0 ].path ).toBe(
			'/wc/store/v1/cart/add-item'
		);
		expect( batchCalls[ 0 ][ 0 ].body ).toEqual( { id: 7, quantity: 2 } );
	} );
} );
