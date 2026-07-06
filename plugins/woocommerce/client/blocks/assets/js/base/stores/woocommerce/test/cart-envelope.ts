/**
 * Internal dependencies
 */
import type { Store } from '../cart';
import type { DraftItem } from '../cart-item-matching';

type MockStore = { state: Store[ 'state' ]; actions: Store[ 'actions' ] };

let mockRegisteredStore: MockStore | null = null;
const mockState = {} as Store[ 'state' ];

// Shared `woocommerce` context returned by getContext( 'woocommerce' ).
let mockContext: {
	productId?: number;
	cartItemKey?: string;
} | null = null;

// products store `findProduct` — deterministic variation resolution stand-in.
// Tests set this to control purchasable-id resolution.
let mockFindProduct: ( args: {
	id: number;
	selectedAttributes?: unknown;
} ) => { id: number } | null = ( { id } ) => ( { id } );

const mockConfig = {
	restUrl: 'https://example.com/wp-json/',
	nonce: 'test-nonce-123',
};

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: jest.fn( () => mockConfig ),
		getContext: jest.fn( () => mockContext ),
		store: jest.fn( ( name: string, definition?: { state?: object } ) => {
			// Stub the products store so the cart's lazy cross-store read
			// resolves purchasable ids via the injectable mockFindProduct.
			if ( name === 'woocommerce/products' ) {
				return {
					state: {
						findProduct: (
							...args: Parameters< typeof mockFindProduct >
						) => mockFindProduct( ...args ),
					},
				};
			}
			if ( name === 'woocommerce/store-notices' ) {
				return {
					state: { notices: [] },
					actions: {
						addNotice: jest.fn( () => 'notice-id' ),
						removeNotice: jest.fn(),
					},
				};
			}
			// The cart store registers under `woocommerce/cart` and re-registers
			// a delegating alias under `woocommerce`. In real iAPI the alias is a
			// SEPARATE proxy whose getters read the distinct `woocommerce/cart`
			// proxy. This mock collapses both onto a single shared object, so the
			// alias registration must NOT re-define any key already provided by
			// the real `woocommerce/cart` registration — otherwise the alias
			// getters (e.g. `get itemInContext() { return state.itemInContext }`)
			// would shadow the real getter and self-recurse. We therefore ignore
			// the alias registration's state entirely and keep the real one.
			if ( definition?.state && name !== 'woocommerce' ) {
				for ( const key of Object.keys( definition.state ) ) {
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
				actions: ( ( definition as { actions?: Store[ 'actions' ] } )
					?.actions ??
					mockRegisteredStore?.actions ) as Store[ 'actions' ],
			};
			return mockRegisteredStore;
		} ),
	} ),
	{ virtual: true }
);

jest.mock( '../legacy-events', () => ( {
	triggerAddedToCartEvent: jest.fn(),
} ) );
jest.mock( '@wordpress/a11y', () => ( { speak: jest.fn() } ), {
	virtual: true,
} );
jest.mock( '../store-notices', () => ( {} ), { virtual: true } );

/**
 * Drive an iAPI async-action generator to completion (same helper as cart.ts
 * test), returning the generator's final value.
 */
async function runAction< T >(
	iterator:
		| ( Iterator< unknown > & {
				throw?: ( e: unknown ) => IteratorResult< unknown >;
		  } )
		| undefined
): Promise< T | undefined > {
	if ( ! iterator ) {
		return undefined;
	}
	let next = iterator.next();
	while ( ! next.done ) {
		try {
			// eslint-disable-next-line no-await-in-loop
			const resolved = await Promise.resolve( next.value );
			next = iterator.next( resolved );
		} catch ( error ) {
			if ( ! iterator.throw ) {
				throw error;
			}
			next = iterator.throw( error );
		}
	}
	return next.value as T;
}

function installFetchMock( {
	initialCart = { items: [], totals: {}, errors: [] },
	onBatch = ( requests: Array< { path: string; body: unknown } > ) =>
		requests.map( () => ( { status: 200, body: initialCart } ) ),
}: {
	initialCart?: unknown;
	onBatch?: (
		requests: Array< { path: string; body: unknown } >
	) => Array< { status: number; body: unknown } >;
} = {} ) {
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
			const parsed = JSON.parse( options?.body as string );
			const requests = parsed.requests as Array< {
				path: string;
				body: unknown;
			} >;
			batchCalls.push( requests );
			return Promise.resolve( {
				ok: true,
				headers: { get: () => 'refreshed-nonce' },
				json: () =>
					Promise.resolve( { responses: onBatch( requests ) } ),
			} as unknown as Response );
		}
	);
	global.fetch = fetchMock as unknown as typeof fetch;
	return { fetchMock, batchCalls };
}

async function loadCartAndReady(
	initialCart: unknown = { items: [], totals: {}, errors: [] }
) {
	installFetchMock( { initialCart } );
	let mod: MockStore | null = null;
	jest.isolateModules( () => {
		require( '../cart' );
		mod = mockRegisteredStore;
	} );
	await runAction(
		mockRegisteredStore?.actions.refreshCartItems() as unknown as Iterator< unknown >
	);
	await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
	return mod as unknown as MockStore;
}

/** Seed cart items directly onto the reactive state slot. */
function setCartItems( cart: MockStore, items: unknown[] ) {
	cart.state.cart = { items, totals: {} } as Store[ 'state' ][ 'cart' ];
}

describe( 'woocommerce/cart — envelope resolution ladder + drafts', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		jest.spyOn( console, 'error' ).mockImplementation( () => undefined );
		mockContext = null;
		mockFindProduct = ( { id } ) => ( { id } );
		// Reset shared mock state so drafts don't leak between tests.
		( mockState as { draftItems?: DraftItem[] } ).draftItems = [];
	} );

	describe( 'ladder — cart resolution', () => {
		it( 'step 1: cartItemKey in context returns that exact line', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{ key: 'abc', id: 1, quantity: 1, name: 'A', type: 'simple' },
				{ key: 'def', id: 2, quantity: 1, name: 'B', type: 'simple' },
			] );
			mockContext = { cartItemKey: 'def' };

			const env = cart.state.itemInContext;
			expect( env.cart?.key ).toBe( 'def' );
			expect( env.isInCart ).toBe( true );
		} );

		it( 'single id-match pairs the draft with the one line', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{
					key: 'abc',
					id: 100,
					quantity: 1,
					name: 'A',
					type: 'simple',
					extensions: {},
					item_data: [],
				},
			] );
			mockContext = { productId: 100 };
			cart.state.draftItems = [ { id: 100, quantity: 1 } ];

			const env = cart.state.itemInContext;
			expect( env.cart?.key ).toBe( 'abc' );
			expect( env.isInCart ).toBe( true );
			expect( env.draft ).toEqual( { id: 100, quantity: 1 } );
		} );

		it( 'resolves the purchasable (variation) id via findProduct before matching', async () => {
			const cart = await loadCartAndReady();
			// Line carries the variation id (456), not the parent id (100).
			setCartItems( cart, [
				{
					key: 'v',
					id: 456,
					quantity: 1,
					name: 'Hoodie - Green',
					type: 'variation',
					extensions: {},
					item_data: [],
				},
			] );
			// findProduct resolves parent 100 + green → variation 456.
			mockFindProduct = ( { id } ) => ( id === 100 ? { id: 456 } : null );
			mockContext = { productId: 100 };
			cart.state.draftItems = [
				{
					id: 100,
					quantity: 1,
					variation: [
						{ attribute: 'attribute_pa_color', value: 'green' },
					],
				},
			];

			const env = cart.state.itemInContext;
			expect( env.cart?.key ).toBe( 'v' );
		} );

		it( 'two meta-differentiated lines + draft props → exact pair', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{
					key: 'noteA',
					id: 100,
					quantity: 1,
					name: 'A',
					type: 'simple',
					extensions: { 'my-plugin': { note: 'A' } },
					item_data: [ { key: 'Gift note', value: 'A' } ],
				},
				{
					key: 'noteB',
					id: 100,
					quantity: 1,
					name: 'A',
					type: 'simple',
					extensions: { 'my-plugin': { note: 'B' } },
					item_data: [ { key: 'Gift note', value: 'B' } ],
				},
			] );
			mockContext = { productId: 100 };
			cart.state.draftItems = [
				{ id: 100, quantity: 1, 'my-plugin': { note: 'B' } },
			];

			const env = cart.state.itemInContext;
			expect( env.cart?.key ).toBe( 'noteB' );
			expect( env.isInCart ).toBe( true );
		} );

		it( 'same two lines + draft with NO props → cart undefined (presence heuristic)', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{
					key: 'noteA',
					id: 100,
					quantity: 1,
					name: 'A',
					type: 'simple',
					extensions: { 'my-plugin': { note: 'A' } },
					item_data: [ { key: 'Gift note', value: 'A' } ],
				},
				{
					key: 'noteB',
					id: 100,
					quantity: 1,
					name: 'A',
					type: 'simple',
					extensions: { 'my-plugin': { note: 'B' } },
					item_data: [ { key: 'Gift note', value: 'B' } ],
				},
			] );
			mockContext = { productId: 100 };
			// Draft accounts for none of the note metadata.
			cart.state.draftItems = [ { id: 100, quantity: 1 } ];

			const env = cart.state.itemInContext;
			expect( env.cart ).toBeUndefined();
			// Both note-lines are excluded by the presence heuristic → zero
			// survivors → THIS (plain) configuration is NOT in the cart, only
			// decorated lines the draft cannot account for are.
			expect( env.isInCart ).toBe( false );
		} );

		it( 'decorated-line-only presence + plain draft → { cart: undefined, isInCart: false }', async () => {
			const cart = await loadCartAndReady();
			// Product 5 is in the cart ONLY as a visibly-decorated line (e.g. a
			// bundle child with "Part of" item_data). A plain draft cannot
			// account for that decoration, so it is not a survivor: the card
			// must NOT render in-cart UI (it has no safe mutation target) and
			// falls back to a plain add button.
			setCartItems( cart, [
				{
					key: 'bundle-child',
					id: 5,
					quantity: 1,
					name: 'Part',
					type: 'simple',
					extensions: {},
					item_data: [ { key: 'Part of', value: 'Mega Bundle' } ],
				},
			] );
			mockContext = { productId: 5 };
			cart.state.draftItems = [ { id: 5, quantity: 1 } ];

			const env = cart.state.itemInContext;
			expect( env.cart ).toBeUndefined();
			expect( env.isInCart ).toBe( false );
		} );

		it( 'invisible bare twins → { cart: undefined, isInCart: true } (both survive)', async () => {
			const cart = await loadCartAndReady();
			// Two lines with the same id and NO visible meta at all — BOTH
			// survive narrowing (the draft accounts for them trivially), so the
			// presence is genuine but ambiguous: isInCart true, cart undefined.
			setCartItems( cart, [
				{
					key: 'x1',
					id: 100,
					quantity: 1,
					name: 'A',
					type: 'simple',
					extensions: {},
					item_data: [],
				},
				{
					key: 'x2',
					id: 100,
					quantity: 1,
					name: 'A',
					type: 'simple',
					extensions: {},
					item_data: [],
				},
			] );
			mockContext = { productId: 100 };
			cart.state.draftItems = [ { id: 100, quantity: 1 } ];

			const env = cart.state.itemInContext;
			expect( env.cart ).toBeUndefined();
			expect( env.isInCart ).toBe( true );
		} );

		it( 'exactly-one rule: never first-match when several survive', async () => {
			const cart = await loadCartAndReady();
			// Two lines both matching the draft props exactly → ambiguous.
			setCartItems( cart, [
				{
					key: 'p1',
					id: 100,
					quantity: 1,
					name: 'A',
					type: 'simple',
					extensions: { 'my-plugin': 'A' },
					item_data: [],
				},
				{
					key: 'p2',
					id: 100,
					quantity: 1,
					name: 'A',
					type: 'simple',
					extensions: { 'my-plugin': 'A' },
					item_data: [],
				},
			] );
			mockContext = { productId: 100 };
			cart.state.draftItems = [
				{ id: 100, quantity: 1, 'my-plugin': 'A' },
			];

			const env = cart.state.itemInContext;
			expect( env.cart ).toBeUndefined();
			// Two survivors: presence is genuine (isInCart true) but there is
			// no exact pairing (never first-match).
			expect( env.isInCart ).toBe( true );
		} );

		it( 'no id-match → isInCart false, cart undefined', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{
					key: 'other',
					id: 999,
					quantity: 1,
					name: 'Z',
					type: 'simple',
					extensions: {},
					item_data: [],
				},
			] );
			mockContext = { productId: 100 };
			cart.state.draftItems = [ { id: 100, quantity: 1 } ];

			const env = cart.state.itemInContext;
			expect( env.cart ).toBeUndefined();
			expect( env.isInCart ).toBe( false );
		} );
	} );

	describe( 'findItem parity with itemInContext', () => {
		it( 'findItem({ key }) matches the key-context result', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{ key: 'abc', id: 1, quantity: 1, name: 'A', type: 'simple' },
			] );
			expect( cart.state.findItem( { key: 'abc' } ).cart?.key ).toBe(
				'abc'
			);
			expect(
				cart.state.findItem( { key: 'missing' } ).cart
			).toBeUndefined();
		} );

		it( 'findItem({ id }) runs the ladder against the context draft', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{
					key: 'abc',
					id: 100,
					quantity: 1,
					name: 'A',
					type: 'simple',
					extensions: {},
					item_data: [],
				},
			] );
			cart.state.draftItems = [ { id: 100, quantity: 1 } ];
			const env = cart.state.findItem( { id: 100 } );
			expect( env.cart?.key ).toBe( 'abc' );
			expect( env.draft?.id ).toBe( 100 );
		} );

		it( 'findItem({ id }) with no draft builds a bare draft from the id', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{
					key: 'abc',
					id: 100,
					quantity: 1,
					name: 'A',
					type: 'simple',
					extensions: {},
					item_data: [],
				},
			] );
			const env = cart.state.findItem( { id: 100 } );
			expect( env.cart?.key ).toBe( 'abc' );
		} );
	} );

	describe( 'drafts', () => {
		it( 'upsertDraftItem creates a draft when missing', async () => {
			const cart = await loadCartAndReady();
			mockContext = { productId: 100 };
			const draft = cart.actions.upsertDraftItem( { quantity: 3 } );
			expect( draft ).toEqual( { id: 100, quantity: 3 } );
			expect( cart.state.draftItems ).toHaveLength( 1 );
		} );

		it( 'upsertDraftItem merges into an existing draft by product id', async () => {
			const cart = await loadCartAndReady();
			mockContext = { productId: 100 };
			cart.actions.upsertDraftItem( { quantity: 1 } );
			cart.actions.upsertDraftItem( { 'my-plugin/gift-note': 'A' } );
			expect( cart.state.draftItems ).toHaveLength( 1 );
			expect( cart.state.draftItems[ 0 ] ).toEqual( {
				id: 100,
				quantity: 1,
				'my-plugin/gift-note': 'A',
			} );
		} );

		it( 'direct mutation of a draft object works', async () => {
			const cart = await loadCartAndReady();
			mockContext = { productId: 100 };
			const draft = cart.actions.upsertDraftItem( { quantity: 1 } );
			draft.quantity = 5;
			expect( cart.state.draftItems[ 0 ].quantity ).toBe( 5 );
		} );

		it( 'removeDraftItem defaults to the context product id', async () => {
			const cart = await loadCartAndReady();
			cart.state.draftItems = [
				{ id: 100, quantity: 1 },
				{ id: 200, quantity: 1 },
			];
			mockContext = { productId: 100 };
			cart.actions.removeDraftItem();
			expect( cart.state.draftItems.map( ( d ) => d.id ) ).toEqual( [
				200,
			] );
		} );

		it( 'clearDraftItems with no context clears all drafts', async () => {
			const cart = await loadCartAndReady();
			cart.state.draftItems = [
				{ id: 100, quantity: 1 },
				{ id: 200, quantity: 1 },
			];
			mockContext = null;
			cart.actions.clearDraftItems();
			expect( cart.state.draftItems ).toHaveLength( 0 );
		} );
	} );

	describe( 'addItem', () => {
		it( 'with no draft uses the { id, quantity: 1 } fallback from context', async () => {
			const cart = await loadCartAndReady();
			mockContext = { productId: 42 };
			const { batchCalls } = installFetchMock( {
				onBatch: ( requests ) =>
					requests.map( () => ( {
						status: 200,
						body: {
							items: [
								{
									key: 'newkey',
									id: 42,
									quantity: 1,
									name: 'X',
									type: 'simple',
									extensions: {},
									item_data: [],
								},
							],
							totals: {},
							errors: [],
						},
					} ) ),
			} );

			await runAction(
				cart.actions.addItem() as unknown as Iterator< unknown >
			);

			expect( batchCalls ).toHaveLength( 1 );
			expect( batchCalls[ 0 ][ 0 ].path ).toBe(
				'/wc/store/v1/cart/add-item'
			);
			expect( batchCalls[ 0 ][ 0 ].body ).toMatchObject( {
				id: 42,
				quantity: 1,
			} );
		} );

		it( 'never emits update-item even when an id-matching line already exists', async () => {
			const cart = await loadCartAndReady( {
				items: [
					{
						key: 'existing',
						id: 100,
						quantity: 1,
						name: 'A',
						type: 'simple',
						extensions: {},
						item_data: [],
					},
				],
				totals: {},
				errors: [],
			} );
			const { batchCalls } = installFetchMock( {
				onBatch: ( requests ) =>
					requests.map( () => ( {
						status: 200,
						body: {
							items: cart.state.cart.items,
							totals: {},
							errors: [],
						},
					} ) ),
			} );

			await runAction(
				cart.actions.addItem( {
					id: 100,
					quantity: 1,
				} ) as unknown as Iterator< unknown >
			);

			expect( batchCalls[ 0 ][ 0 ].path ).toBe(
				'/wc/store/v1/cart/add-item'
			);
			expect( batchCalls[ 0 ][ 0 ].path ).not.toContain( 'update-item' );
		} );

		it( 'POSTs the draft as-is (parent id + variation array), no client-side id swap', async () => {
			const cart = await loadCartAndReady();
			// Even though findProduct could resolve 100 → 456, addItem must POST
			// the parent id and let the server resolve the variation.
			mockFindProduct = ( { id } ) => ( id === 100 ? { id: 456 } : null );
			const { batchCalls } = installFetchMock( {
				onBatch: ( requests ) =>
					requests.map( () => ( {
						status: 200,
						body: { items: [], totals: {}, errors: [] },
					} ) ),
			} );

			await runAction(
				cart.actions.addItem( {
					id: 100,
					quantity: 2,
					variation: [
						{ attribute: 'attribute_pa_color', value: 'green' },
					],
				} ) as unknown as Iterator< unknown >
			);

			expect( batchCalls[ 0 ][ 0 ].body ).toMatchObject( {
				id: 100,
				quantity: 2,
				variation: [
					{ attribute: 'attribute_pa_color', value: 'green' },
				],
			} );
		} );

		it( 'forwards namespaced extension props to the server', async () => {
			const cart = await loadCartAndReady();
			const { batchCalls } = installFetchMock( {
				onBatch: ( requests ) =>
					requests.map( () => ( {
						status: 200,
						body: { items: [], totals: {}, errors: [] },
					} ) ),
			} );

			await runAction(
				cart.actions.addItem( {
					id: 100,
					quantity: 1,
					'my-plugin/gift-note': 'Happy birthday',
				} ) as unknown as Iterator< unknown >
			);

			expect( batchCalls[ 0 ][ 0 ].body ).toMatchObject( {
				'my-plugin/gift-note': 'Happy birthday',
			} );
		} );

		it( 'resolves with the affected cart line', async () => {
			const cart = await loadCartAndReady();
			const serverLine = {
				key: 'added',
				id: 100,
				quantity: 1,
				name: 'A',
				type: 'simple',
				extensions: {},
				item_data: [],
			};
			installFetchMock( {
				onBatch: ( requests ) =>
					requests.map( () => ( {
						status: 200,
						body: {
							items: [ serverLine ],
							totals: {},
							errors: [],
						},
					} ) ),
			} );

			const affected = await runAction<
				import('@woocommerce/types').CartItem
			>(
				cart.actions.addItem( {
					id: 100,
					quantity: 1,
				} ) as unknown as Iterator< unknown >
			);

			expect( affected?.key ).toBe( 'added' );
		} );

		it( 'does not clear drafts implicitly', async () => {
			const cart = await loadCartAndReady();
			mockContext = { productId: 100 };
			cart.state.draftItems = [ { id: 100, quantity: 1 } ];
			installFetchMock( {
				onBatch: ( requests ) =>
					requests.map( () => ( {
						status: 200,
						body: { items: [], totals: {}, errors: [] },
					} ) ),
			} );

			await runAction(
				cart.actions.addItem() as unknown as Iterator< unknown >
			);

			expect( cart.state.draftItems ).toHaveLength( 1 );
		} );
	} );
} );
