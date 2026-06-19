/**
 * External dependencies
 */
import type { CartItem } from '@woocommerce/types';

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
 * @param action The async action return value cast to a generator.
 * @return A promise that resolves once the generator has finished.
 */
async function runAction( action: unknown ): Promise< void > {
	const iterator = action as Iterator< unknown, unknown, unknown >;
	let next = iterator.next();
	while ( ! next.done ) {
		// eslint-disable-next-line no-await-in-loop
		const resolved = await next.value;
		next = iterator.next( resolved );
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
} );
