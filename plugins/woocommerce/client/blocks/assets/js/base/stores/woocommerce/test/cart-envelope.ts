/**
 * Internal dependencies
 */
import type { Store } from '../cart';
import type { DraftItem } from '../cart-item-matching';

type MockStore = { state: Store[ 'state' ]; actions: Store[ 'actions' ] };

let mockRegisteredStore: MockStore | null = null;
const mockState = {} as Store[ 'state' ];

// Extension-owned iAPI stores, keyed by namespace, that the `cartItemFilter`
// resolver walks via the public `store( namespace )` accessor. Tests register a
// fake extension store here (with an `actions` bag holding the predicate) and
// clear it in beforeEach. A namespace absent from this map (and not one of the
// core namespaces below) throws from the mocked `store()` — mirroring iAPI's
// "unknown namespace on a locked read" so the resolver's catch path is exercised.
const mockExtensionStores: Record< string, Record< string, unknown > > = {};

// The cart store's OWN context (`woocommerce/cart`), returned by
// getContext( 'woocommerce/cart' ). Carries the line key, the each-item
// `cartItem`, and the `cartItemFilter` reference — NOT the product id (that is
// derived state, T12).
let mockCartContext: {
	cartItemKey?: string;
	cartItem?: { key?: string };
	cartItemFilter?: { namespace: string; action: string };
} | null = null;

// The context product id the products store's `mainProductInContext` resolves to
// (derived state — the cart store reads it instead of a foreign context, T12).
// Tests set this in place of the old `mockContext.productId`.
let mockContextProductId: number | undefined;

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
		// Namespace-aware: the cart store reads its OWN context
		// (`woocommerce/cart`) for the line key/filter. There is no product-context
		// read anymore (T12), so any other namespace resolves to null.
		getContext: jest.fn( ( namespace?: string ) =>
			namespace === 'woocommerce/cart' ? mockCartContext : null
		),
		store: jest.fn( ( name: string, definition?: { state?: object } ) => {
			// Stub the products store so the cart's lazy cross-store reads
			// resolve: purchasable ids via the injectable mockFindProduct, and the
			// context product id via `mainProductInContext` (derived state — the
			// cart store's ONLY cross-domain product read, T12).
			if ( name === 'woocommerce/products' ) {
				return {
					state: {
						findProduct: (
							...args: Parameters< typeof mockFindProduct >
						) => mockFindProduct( ...args ),
						get mainProductInContext() {
							return mockContextProductId === undefined
								? null
								: { id: mockContextProductId };
						},
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
			// Extension-store read (cartItemFilter reference resolution). Any
			// namespace that is not one of the core store namespaces is treated
			// as an extension read: return its registered fake store, or throw
			// when it is not registered (iAPI throws for a locked/unknown read;
			// the resolver's try/catch degrades to generic rules on that throw).
			if (
				name !== 'woocommerce/cart' &&
				name !== 'woocommerce' &&
				! definition
			) {
				if ( mockExtensionStores[ name ] ) {
					return mockExtensionStores[ name ];
				}
				throw new Error(
					`Mock: no extension store registered for "${ name }".`
				);
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
		mockCartContext = null;
		mockContextProductId = undefined;
		mockFindProduct = ( { id } ) => ( { id } );
		// Reset shared mock state so drafts don't leak between tests.
		( mockState as { draftItems?: DraftItem[] } ).draftItems = [];
		// Reset the extension-store registry between tests.
		for ( const key of Object.keys( mockExtensionStores ) ) {
			delete mockExtensionStores[ key ];
		}
	} );

	describe( 'ladder — cart resolution', () => {
		it( 'step 1: cartItemKey in context returns that exact line', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{ key: 'abc', id: 1, quantity: 1, name: 'A', type: 'simple' },
				{ key: 'def', id: 2, quantity: 1, name: 'B', type: 'simple' },
			] );
			mockCartContext = { cartItemKey: 'def' };

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
			mockContextProductId = 100;
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
			mockContextProductId = 100;
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
			mockContextProductId = 100;
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
			mockContextProductId = 100;
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
			mockContextProductId = 5;
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
			mockContextProductId = 100;
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
			mockContextProductId = 100;
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
			mockContextProductId = 100;
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
			mockContextProductId = 100;
			const draft = cart.actions.upsertDraftItem( { quantity: 3 } );
			expect( draft ).toEqual( { id: 100, quantity: 3 } );
			expect( cart.state.draftItems ).toHaveLength( 1 );
		} );

		it( 'upsertDraftItem merges into an existing draft by product id', async () => {
			const cart = await loadCartAndReady();
			mockContextProductId = 100;
			cart.actions.upsertDraftItem( { quantity: 1 } );
			cart.actions.upsertDraftItem( { 'my-plugin/gift-note': 'A' } );
			expect( cart.state.draftItems ).toHaveLength( 1 );
			expect( cart.state.draftItems[ 0 ] ).toEqual( {
				id: 100,
				quantity: 1,
				'my-plugin/gift-note': 'A',
			} );
		} );

		it( 'upsertDraftItem is the write path for updating a field (write policy)', async () => {
			const cart = await loadCartAndReady();
			mockContextProductId = 100;
			cart.actions.upsertDraftItem( { quantity: 1 } );
			cart.actions.upsertDraftItem( { quantity: 5 } );
			expect( cart.state.draftItems[ 0 ].quantity ).toBe( 5 );
		} );

		it( 'removeDraftItem defaults to the context product id', async () => {
			const cart = await loadCartAndReady();
			cart.state.draftItems = [
				{ id: 100, quantity: 1 },
				{ id: 200, quantity: 1 },
			];
			mockContextProductId = 100;
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
			// No context product (mainProductInContext resolves nothing).
			mockContextProductId = undefined;
			cart.actions.clearDraftItems();
			expect( cart.state.draftItems ).toHaveLength( 0 );
		} );
	} );

	describe( 'addItem', () => {
		it( 'with no draft uses the { id, quantity: 1 } fallback from context', async () => {
			const cart = await loadCartAndReady();
			mockContextProductId = 42;
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

		it( 'swaps in the purchasable id at send time (identity rule 6), keeping the variation array', async () => {
			const cart = await loadCartAndReady();
			// findProduct resolves the draft's parent id + selection to the
			// variation (100 → 456). addItem must POST the RESOLVED purchasable
			// id: posting the parent id and relying on server-side resolution is
			// not universally safe — the draft's `variation` carries the
			// shopper-facing attribute LABELS, and for attributes with custom
			// slugs (label ≠ slug) the server fails with "No matching variation
			// found" (T6 e2e finding). The variation array is still sent so the
			// server can validate the attributes against the concrete variation
			// (including "any" slots).
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
				id: 456,
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
			mockContextProductId = 100;
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

	describe( 'cartItemFilter — the context escape hatch (T5)', () => {
		// A decorated line the presence heuristic would exclude, plus a plain
		// line. Shared by several tests.
		const decoratedLine = {
			key: 'decorated',
			id: 100,
			quantity: 1,
			name: 'A',
			type: 'simple',
			extensions: { 'other-plugin': { bundle: 'X' } },
			item_data: [ { key: 'Part of', value: 'Mega Bundle' } ],
		};
		const plainLine = {
			key: 'plain',
			id: 100,
			quantity: 1,
			name: 'A',
			type: 'simple',
			extensions: {},
			item_data: [],
		};

		it( 'filter REPLACES narrowing: pairs a line the presence heuristic excludes', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [ decoratedLine ] );
			// A plain draft: generic narrowing (presence heuristic) would
			// EXCLUDE the decorated line → zero survivors → cart undefined.
			cart.state.draftItems = [ { id: 100, quantity: 1 } ];

			// The filter selects the decorated line by its key — the escape
			// hatch a bundle editor uses to pair with a line the defaults reject.
			mockExtensionStores[ 'bundle/editor' ] = {
				actions: {
					matchLine: ( item: { key?: string } ) =>
						item.key === 'decorated',
				},
			};
			mockContextProductId = 100;
			mockCartContext = {
				cartItemFilter: {
					namespace: 'bundle/editor',
					action: 'matchLine',
				},
			};

			const env = cart.state.itemInContext;
			expect( env.cart?.key ).toBe( 'decorated' );
			expect( env.isInCart ).toBe( true );
		} );

		it( 'filter receives ( cartItem, { draft, context } )', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [ plainLine ] );
			cart.state.draftItems = [
				{ id: 100, quantity: 1, 'my-plugin/token': 'seed' },
			];

			const seen: Array< {
				key: string | undefined;
				draftToken: unknown;
				draftId: number | undefined;
				ctxFilterNamespace: string | undefined;
			} > = [];
			mockExtensionStores[ 'my-plugin/x' ] = {
				actions: {
					matchLine: (
						item: { key?: string },
						extra: {
							draft?: Record< string, unknown >;
							// The predicate receives the `woocommerce/cart`
							// context (T12) — it carries the filter reference, not
							// a product id (product identity is derived state).
							context?: {
								cartItemFilter?: { namespace?: string };
							} | null;
						}
					) => {
						seen.push( {
							key: item.key,
							draftToken: extra.draft?.[ 'my-plugin/token' ],
							draftId: extra.draft?.id as number | undefined,
							ctxFilterNamespace:
								extra.context?.cartItemFilter?.namespace,
						} );
						return true;
					},
				},
			};
			mockContextProductId = 100;
			mockCartContext = {
				cartItemFilter: {
					namespace: 'my-plugin/x',
					action: 'matchLine',
				},
			};

			cart.state.itemInContext;
			expect( seen ).toEqual( [
				{
					key: 'plain',
					draftToken: 'seed',
					draftId: 100,
					ctxFilterNamespace: 'my-plugin/x',
				},
			] );
		} );

		it( 'filter narrowing to exactly one among meta-twins → that line', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{ ...plainLine, key: 't1', extensions: { p: { n: 'A' } } },
				{ ...plainLine, key: 't2', extensions: { p: { n: 'B' } } },
			] );
			cart.state.draftItems = [ { id: 100, quantity: 1 } ];
			mockExtensionStores[ 'my-plugin/x' ] = {
				actions: {
					matchLine: ( item: {
						extensions?: { p?: { n?: string } };
					} ) => item.extensions?.p?.n === 'B',
				},
			};
			mockContextProductId = 100;
			mockCartContext = {
				cartItemFilter: {
					namespace: 'my-plugin/x',
					action: 'matchLine',
				},
			};

			const env = cart.state.itemInContext;
			expect( env.cart?.key ).toBe( 't2' );
			expect( env.isInCart ).toBe( true );
		} );

		it( 'filter selecting zero → { cart: undefined, isInCart: false }', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [ plainLine ] );
			cart.state.draftItems = [ { id: 100, quantity: 1 } ];
			mockExtensionStores[ 'my-plugin/x' ] = {
				actions: { matchLine: () => false },
			};
			mockContextProductId = 100;
			mockCartContext = {
				cartItemFilter: {
					namespace: 'my-plugin/x',
					action: 'matchLine',
				},
			};

			const env = cart.state.itemInContext;
			expect( env.cart ).toBeUndefined();
			expect( env.isInCart ).toBe( false );
		} );

		it( 'filter leaving several → { cart: undefined, isInCart: true }', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{ ...plainLine, key: 'm1' },
				{ ...plainLine, key: 'm2' },
			] );
			cart.state.draftItems = [ { id: 100, quantity: 1 } ];
			mockExtensionStores[ 'my-plugin/x' ] = {
				actions: { matchLine: () => true },
			};
			mockContextProductId = 100;
			mockCartContext = {
				cartItemFilter: {
					namespace: 'my-plugin/x',
					action: 'matchLine',
				},
			};

			const env = cart.state.itemInContext;
			expect( env.cart ).toBeUndefined();
			expect( env.isInCart ).toBe( true );
		} );

		it( 'key-in-context IGNORES the filter (step 1 unchanged)', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{ key: 'exact', id: 1, quantity: 1, name: 'A', type: 'simple' },
			] );
			// A filter that would reject everything — must never run for a key.
			mockExtensionStores[ 'my-plugin/x' ] = {
				actions: { matchLine: () => false },
			};
			mockCartContext = {
				cartItemKey: 'exact',
				cartItemFilter: {
					namespace: 'my-plugin/x',
					action: 'matchLine',
				},
			};

			const env = cart.state.itemInContext;
			expect( env.cart?.key ).toBe( 'exact' );
			expect( env.isInCart ).toBe( true );
		} );

		it( 'resolves a dotted action path from the store root', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [ { ...plainLine, key: 'dp' } ] );
			cart.state.draftItems = [ { id: 100, quantity: 1 } ];
			mockExtensionStores[ 'my-plugin/x' ] = {
				actions: {
					filters: {
						byNote: ( item: { key?: string } ) => item.key === 'dp',
					},
				},
			};
			mockContextProductId = 100;
			mockCartContext = {
				cartItemFilter: {
					namespace: 'my-plugin/x',
					action: 'actions.filters.byNote',
				},
			};

			const env = cart.state.itemInContext;
			expect( env.cart?.key ).toBe( 'dp' );
		} );

		describe( 'broken reference → generic rules + dev warning', () => {
			it( 'unknown namespace → generic narrowing runs, warns in dev', async () => {
				const cart = await loadCartAndReady();
				const warn = jest
					.spyOn( console, 'warn' )
					.mockImplementation( () => undefined );
				( globalThis as { SCRIPT_DEBUG?: boolean } ).SCRIPT_DEBUG =
					true;

				// One plain line that generic narrowing WOULD pair.
				setCartItems( cart, [ plainLine ] );
				cart.state.draftItems = [ { id: 100, quantity: 1 } ];
				mockContextProductId = 100;
				mockCartContext = {
					cartItemFilter: {
						namespace: 'does-not/exist',
						action: 'matchLine',
					},
				};

				const env = cart.state.itemInContext;
				// Fell back to generic rules: the plain draft pairs the plain line.
				expect( env.cart?.key ).toBe( 'plain' );
				expect( warn ).toHaveBeenCalled();

				delete ( globalThis as { SCRIPT_DEBUG?: boolean } )
					.SCRIPT_DEBUG;
				warn.mockRestore();
			} );

			it( 'unknown action on a known store → generic narrowing runs, warns', async () => {
				const cart = await loadCartAndReady();
				const warn = jest
					.spyOn( console, 'warn' )
					.mockImplementation( () => undefined );
				( globalThis as { SCRIPT_DEBUG?: boolean } ).SCRIPT_DEBUG =
					true;

				setCartItems( cart, [ plainLine ] );
				cart.state.draftItems = [ { id: 100, quantity: 1 } ];
				mockExtensionStores[ 'my-plugin/x' ] = {
					actions: { somethingElse: () => true },
				};
				mockContextProductId = 100;
				mockCartContext = {
					cartItemFilter: {
						namespace: 'my-plugin/x',
						action: 'noSuchAction',
					},
				};

				const env = cart.state.itemInContext;
				expect( env.cart?.key ).toBe( 'plain' );
				expect( warn ).toHaveBeenCalled();

				delete ( globalThis as { SCRIPT_DEBUG?: boolean } )
					.SCRIPT_DEBUG;
				warn.mockRestore();
			} );

			it( 'non-function target → generic narrowing runs, warns', async () => {
				const cart = await loadCartAndReady();
				const warn = jest
					.spyOn( console, 'warn' )
					.mockImplementation( () => undefined );
				( globalThis as { SCRIPT_DEBUG?: boolean } ).SCRIPT_DEBUG =
					true;

				setCartItems( cart, [ plainLine ] );
				cart.state.draftItems = [ { id: 100, quantity: 1 } ];
				mockExtensionStores[ 'my-plugin/x' ] = {
					actions: { matchLine: 'not a function' },
				};
				mockContextProductId = 100;
				mockCartContext = {
					cartItemFilter: {
						namespace: 'my-plugin/x',
						action: 'matchLine',
					},
				};

				const env = cart.state.itemInContext;
				expect( env.cart?.key ).toBe( 'plain' );
				expect( warn ).toHaveBeenCalled();

				delete ( globalThis as { SCRIPT_DEBUG?: boolean } )
					.SCRIPT_DEBUG;
				warn.mockRestore();
			} );

			it( 'does NOT warn in production (SCRIPT_DEBUG off)', async () => {
				const cart = await loadCartAndReady();
				const warn = jest
					.spyOn( console, 'warn' )
					.mockImplementation( () => undefined );
				// SCRIPT_DEBUG left unset (production).

				setCartItems( cart, [ plainLine ] );
				cart.state.draftItems = [ { id: 100, quantity: 1 } ];
				mockContextProductId = 100;
				mockCartContext = {
					cartItemFilter: {
						namespace: 'does-not/exist',
						action: 'matchLine',
					},
				};

				const env = cart.state.itemInContext;
				expect( env.cart?.key ).toBe( 'plain' );
				expect( warn ).not.toHaveBeenCalled();

				warn.mockRestore();
			} );
		} );

		it( 'innermost-context shadowing: the effective context filter wins', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{ ...plainLine, key: 'outerPick' },
				{ ...plainLine, key: 'innerPick' },
			] );
			cart.state.draftItems = [ { id: 100, quantity: 1 } ];

			// Two competing filters. iAPI context inheritance means the getter
			// reads the NEAREST (innermost) `cartItemFilter`; we simulate that by
			// setting the effective context to the inner reference and proving
			// the inner predicate selects — the outer one is never consulted.
			mockExtensionStores[ 'outer/region' ] = {
				actions: {
					pick: ( item: { key?: string } ) =>
						item.key === 'outerPick',
				},
			};
			mockExtensionStores[ 'inner/region' ] = {
				actions: {
					pick: ( item: { key?: string } ) =>
						item.key === 'innerPick',
				},
			};

			// Outer context would pick 'outerPick'…
			mockContextProductId = 100;
			mockCartContext = {
				cartItemFilter: { namespace: 'outer/region', action: 'pick' },
			};
			expect( cart.state.itemInContext.cart?.key ).toBe( 'outerPick' );

			// …but an inner region shadowing it picks 'innerPick' (innermost wins).
			mockContextProductId = 100;
			mockCartContext = {
				cartItemFilter: { namespace: 'inner/region', action: 'pick' },
			};
			expect( cart.state.itemInContext.cart?.key ).toBe( 'innerPick' );
		} );

		describe( 'findItem filter param', () => {
			const twinA = {
				key: 'A',
				id: 100,
				quantity: 1,
				name: 'A',
				type: 'simple',
				extensions: {},
				item_data: [],
			};
			const twinB = { ...twinA, key: 'B' };

			it( 'explicit predicate overrides the context filter', async () => {
				const cart = await loadCartAndReady();
				setCartItems( cart, [ twinA, twinB ] );
				cart.state.draftItems = [ { id: 100, quantity: 1 } ];
				// Context filter would pick A…
				mockExtensionStores[ 'ctx/x' ] = {
					actions: {
						pick: ( item: { key?: string } ) => item.key === 'A',
					},
				};
				mockContextProductId = 100;
				mockCartContext = {
					cartItemFilter: { namespace: 'ctx/x', action: 'pick' },
				};

				// …but an explicit predicate (real function) picks B and wins.
				const env = cart.state.findItem( {
					id: 100,
					filter: ( item ) => item.key === 'B',
				} );
				expect( env.cart?.key ).toBe( 'B' );
			} );

			it( 'filter: false opts out — generic rules run, context filter ignored', async () => {
				const cart = await loadCartAndReady();
				// A single plain line generic rules WOULD pair, plus a context
				// filter that would reject everything. filter:false must ignore
				// the context filter and let generic narrowing pair the line.
				setCartItems( cart, [ twinA ] );
				cart.state.draftItems = [ { id: 100, quantity: 1 } ];
				mockExtensionStores[ 'ctx/x' ] = {
					actions: { pick: () => false },
				};
				mockContextProductId = 100;
				mockCartContext = {
					cartItemFilter: { namespace: 'ctx/x', action: 'pick' },
				};

				const env = cart.state.findItem( { id: 100, filter: false } );
				expect( env.cart?.key ).toBe( 'A' );
				expect( env.isInCart ).toBe( true );
			} );

			it( 'absent filter inherits the context filter when in scope', async () => {
				const cart = await loadCartAndReady();
				setCartItems( cart, [ twinA, twinB ] );
				cart.state.draftItems = [ { id: 100, quantity: 1 } ];
				mockExtensionStores[ 'ctx/x' ] = {
					actions: {
						pick: ( item: { key?: string } ) => item.key === 'B',
					},
				};
				mockContextProductId = 100;
				mockCartContext = {
					cartItemFilter: { namespace: 'ctx/x', action: 'pick' },
				};

				const env = cart.state.findItem( { id: 100 } );
				expect( env.cart?.key ).toBe( 'B' );
			} );

			it( 'out of scope: degrades silently to generic rules (no context filter)', async () => {
				const cart = await loadCartAndReady();
				setCartItems( cart, [ twinA ] );
				cart.state.draftItems = [ { id: 100, quantity: 1 } ];
				// getContext throws out of scope → getSharedContext returns null.
				const { getContext } = require( '@wordpress/interactivity' );
				( getContext as jest.Mock ).mockImplementationOnce( () => {
					throw new Error( 'out of scope' );
				} );

				// No context filter is inherited; generic narrowing pairs the
				// single plain line.
				const env = cart.state.findItem( { id: 100 } );
				expect( env.cart?.key ).toBe( 'A' );
				expect( env.isInCart ).toBe( true );
			} );

			it( 'key bypasses the filter param entirely', async () => {
				const cart = await loadCartAndReady();
				setCartItems( cart, [ twinA, twinB ] );
				const env = cart.state.findItem( {
					key: 'B',
					filter: () => false,
				} );
				expect( env.cart?.key ).toBe( 'B' );
			} );
		} );
	} );
} );
