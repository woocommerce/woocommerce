/**
 * Internal dependencies
 */
import type { Store } from '../cart';
import type { DraftItem } from '../cart-item-matching';

type MockStore = { state: Store[ 'state' ]; actions: Store[ 'actions' ] };

let mockRegisteredStore: MockStore | null = null;
const mockState = {} as Store[ 'state' ];

// The cart store's OWN context (`woocommerce/cart`), returned by
// getContext( 'woocommerce/cart' ). Carries the line key, the each-item
// `cartItem`, and an optional `draftKey` override — NOT the product id (that is
// derived state) and NO filter (custom matching is an explicit
// `findItem({ filter })` predicate, not a context reference).
let mockCartContext: {
	cartItemKey?: string;
	cartItem?: { key?: string };
	draftKey?: string;
} | null = null;

// The context product id the products store's `mainProductInContext` resolves to
// (derived state — the cart store reads it instead of a foreign context).
let mockContextProductId: number | undefined;

// The min purchase quantity the products store's `productInContext` exposes
// (`add_to_cart.minimum`) — the addItem fallback quantity for a bare surface.
let mockContextMinPurchaseQuantity: number | undefined;

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
		// (`woocommerce/cart`) for the line key. There is no product-context read,
		// so any other namespace resolves to null.
		getContext: jest.fn( ( namespace?: string ) =>
			namespace === 'woocommerce/cart' ? mockCartContext : null
		),
		store: jest.fn( ( name: string, definition?: { state?: object } ) => {
			// Stub the products store so the cart's lazy cross-store reads
			// resolve: purchasable ids via the injectable mockFindProduct, and the
			// context product id via `mainProductInContext` (derived state — the
			// cart store's ONLY cross-domain product read).
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
						get productInContext() {
							if ( mockContextProductId === undefined ) {
								return null;
							}
							return {
								id: mockContextProductId,
								...( mockContextMinPurchaseQuantity !==
									undefined && {
									add_to_cart: {
										minimum: mockContextMinPurchaseQuantity,
									},
								} ),
							};
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
		mockRegisteredStore?.actions.refresh() as unknown as Iterator< unknown >
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
		mockContextMinPurchaseQuantity = undefined;
		mockFindProduct = ( { id } ) => ( { id } );
		// Reset shared mock state so drafts don't leak between tests.
		(
			mockState as { draftItems?: Record< string, DraftItem > }
		 ).draftItems = {};
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
			cart.state.draftItems = { '100': { id: 100, quantity: 1 } };

			const env = cart.state.itemInContext;
			expect( env.cart?.key ).toBe( 'abc' );
			expect( env.draft ).toEqual( { id: 100, quantity: 1 } );
		} );

		it( 'canonical documented shape: bare-namespace draft prop pairs the line whose extensions[ns] deep-matches', async () => {
			// B1 positive pairing, end-to-end with the exact documented shape:
			// `upsertDraftItem({ 'wc-gift-note-demo': { 'gift-note': 'A' } })`
			// pairs against `line.extensions['wc-gift-note-demo'] = { 'gift-note':
			// 'A' }`. Two note-lines are in the cart; the draft holds note A; the
			// envelope must resolve the note-A line (generic pairing, no filter).
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{
					key: 'noteA',
					id: 100,
					quantity: 1,
					name: 'A',
					type: 'simple',
					extensions: {
						'wc-gift-note-demo': { 'gift-note': 'A' },
					},
					item_data: [ { key: 'Gift note', value: 'A' } ],
				},
				{
					key: 'noteB',
					id: 100,
					quantity: 1,
					name: 'A',
					type: 'simple',
					extensions: {
						'wc-gift-note-demo': { 'gift-note': 'B' },
					},
					item_data: [ { key: 'Gift note', value: 'B' } ],
				},
			] );
			mockContextProductId = 100;
			cart.actions.upsertDraftItem( {
				id: 100,
				quantity: 1,
				'wc-gift-note-demo': { 'gift-note': 'A' },
			} );

			const env = cart.state.itemInContext;
			expect( env.cart?.key ).toBe( 'noteA' );
			expect( env.draft?.[ 'wc-gift-note-demo' ] ).toEqual( {
				'gift-note': 'A',
			} );
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
			cart.state.draftItems = {
				'100': {
					id: 100,
					quantity: 1,
					variation: [
						{ attribute: 'attribute_pa_color', value: 'green' },
					],
				},
			};

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
			cart.state.draftItems = {
				'100': { id: 100, quantity: 1, 'my-plugin': { note: 'B' } },
			};

			const env = cart.state.itemInContext;
			expect( env.cart?.key ).toBe( 'noteB' );
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
			cart.state.draftItems = { '100': { id: 100, quantity: 1 } };

			const env = cart.state.itemInContext;
			// Both note-lines are excluded by the presence heuristic → zero
			// survivors → THIS (plain) configuration is NOT paired: no cart line.
			expect( env.cart ).toBeUndefined();
		} );

		it( 'decorated-line-only presence + plain draft → cart undefined', async () => {
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
			cart.state.draftItems = { '5': { id: 5, quantity: 1 } };

			const env = cart.state.itemInContext;
			expect( env.cart ).toBeUndefined();
		} );

		it( 'invisible bare twins → cart undefined (both survive, ambiguous)', async () => {
			const cart = await loadCartAndReady();
			// Two lines with the same id and NO visible meta at all — BOTH
			// survive narrowing (the draft accounts for them trivially), so the
			// presence is genuine but ambiguous: cart undefined.
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
			cart.state.draftItems = { '100': { id: 100, quantity: 1 } };

			const env = cart.state.itemInContext;
			expect( env.cart ).toBeUndefined();
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
			cart.state.draftItems = {
				'100': { id: 100, quantity: 1, 'my-plugin': 'A' },
			};

			const env = cart.state.itemInContext;
			// Two survivors: no exact pairing (never first-match).
			expect( env.cart ).toBeUndefined();
		} );

		it( 'no id-match → cart undefined', async () => {
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
			cart.state.draftItems = { '100': { id: 100, quantity: 1 } };

			const env = cart.state.itemInContext;
			expect( env.cart ).toBeUndefined();
		} );
	} );

	describe( 'cross-product envelope guard (step 1 key path)', () => {
		it( 'drops the context draft when the keyed line is a different product', async () => {
			// A mini-cart row for product B (line key "rowB") rendered while the
			// page context product is A (draft id 100). Step 1 resolves the row's
			// exact line by key, but must NOT carry A's draft against B's line.
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{
					key: 'rowB',
					id: 200,
					quantity: 1,
					name: 'B',
					type: 'simple',
					extensions: {},
					item_data: [],
				},
			] );
			// Context product is A (100); its draft exists.
			mockContextProductId = 100;
			cart.state.draftItems = { '100': { id: 100, quantity: 1 } };
			mockCartContext = { cartItemKey: 'rowB' };

			const env = cart.state.itemInContext;
			expect( env.cart?.key ).toBe( 'rowB' );
			// A's draft must not be paired with B's line.
			expect( env.draft ).toBeUndefined();
		} );

		it( 'keeps the draft when the keyed line matches the context product', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{
					key: 'rowA',
					id: 100,
					quantity: 1,
					name: 'A',
					type: 'simple',
					extensions: {},
					item_data: [],
				},
			] );
			mockContextProductId = 100;
			cart.state.draftItems = { '100': { id: 100, quantity: 1 } };
			mockCartContext = { cartItemKey: 'rowA' };

			const env = cart.state.itemInContext;
			expect( env.cart?.key ).toBe( 'rowA' );
			expect( env.draft?.id ).toBe( 100 );
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
			cart.state.draftItems = { '100': { id: 100, quantity: 1 } };
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
		it( 'upsertDraftItem creates a draft when missing, keyed by String(productId)', async () => {
			const cart = await loadCartAndReady();
			mockContextProductId = 100;
			const draft = cart.actions.upsertDraftItem( { quantity: 3 } );
			expect( draft ).toEqual( { id: 100, quantity: 3 } );
			expect( Object.keys( cart.state.draftItems ) ).toEqual( [ '100' ] );
			expect( cart.state.draftItems[ '100' ] ).toEqual( {
				id: 100,
				quantity: 3,
			} );
		} );

		it( 'upsertDraftItem merges into the existing draft for the context product', async () => {
			const cart = await loadCartAndReady();
			mockContextProductId = 100;
			cart.actions.upsertDraftItem( { quantity: 1 } );
			cart.actions.upsertDraftItem( {
				'my-plugin': { 'gift-note': 'A' },
			} );
			expect( Object.keys( cart.state.draftItems ) ).toHaveLength( 1 );
			expect( cart.state.draftItems[ '100' ] ).toEqual( {
				id: 100,
				quantity: 1,
				'my-plugin': { 'gift-note': 'A' },
			} );
		} );

		it( 'upsertDraftItem is the write path for updating a field (write policy)', async () => {
			const cart = await loadCartAndReady();
			mockContextProductId = 100;
			cart.actions.upsertDraftItem( { quantity: 1 } );
			cart.actions.upsertDraftItem( { quantity: 5 } );
			expect( cart.state.draftItems[ '100' ].quantity ).toBe( 5 );
		} );

		it( 'default keying: surfaces of the same product share one draft', async () => {
			const cart = await loadCartAndReady();
			mockContextProductId = 100;
			// Two surfaces of the same product, neither declaring a draftKey.
			cart.actions.upsertDraftItem( { quantity: 2 } );
			cart.actions.upsertDraftItem( { quantity: 7 } );
			expect( Object.keys( cart.state.draftItems ) ).toEqual( [ '100' ] );
			expect( cart.state.draftItems[ '100' ].quantity ).toBe( 7 );
		} );

		it( 'draftKey isolation: two contexts on the same product hold independent drafts', async () => {
			const cart = await loadCartAndReady();
			mockContextProductId = 100;

			// Surface A opts into isolation via a context draftKey.
			mockCartContext = { draftKey: 'surface-A' };
			cart.actions.upsertDraftItem( { quantity: 2 } );

			// Surface B, same product, a different draftKey.
			mockCartContext = { draftKey: 'surface-B' };
			cart.actions.upsertDraftItem( { quantity: 9 } );

			expect( Object.keys( cart.state.draftItems ).sort() ).toEqual( [
				'surface-A',
				'surface-B',
			] );
			expect( cart.state.draftItems[ 'surface-A' ] ).toEqual( {
				id: 100,
				quantity: 2,
			} );
			expect( cart.state.draftItems[ 'surface-B' ] ).toEqual( {
				id: 100,
				quantity: 9,
			} );
		} );

		it( 'upsertDraftItem accepts an explicit draftKey for imperative callers', async () => {
			const cart = await loadCartAndReady();
			mockContextProductId = 100;
			cart.actions.upsertDraftItem( { draftKey: 'row-7', quantity: 4 } );
			expect( cart.state.draftItems[ 'row-7' ] ).toEqual( {
				id: 100,
				quantity: 4,
			} );
		} );

		it( 'removeDraftItem defaults to the context draft key', async () => {
			const cart = await loadCartAndReady();
			cart.state.draftItems = {
				'100': { id: 100, quantity: 1 },
				'200': { id: 200, quantity: 1 },
			};
			mockContextProductId = 100;
			cart.actions.removeDraftItem();
			expect( Object.keys( cart.state.draftItems ) ).toEqual( [ '200' ] );
		} );

		it( 'removeDraftItem targets an explicit draftKey', async () => {
			const cart = await loadCartAndReady();
			cart.state.draftItems = {
				'100': { id: 100, quantity: 1 },
				'200': { id: 200, quantity: 1 },
			};
			mockContextProductId = 100;
			cart.actions.removeDraftItem( { draftKey: '200' } );
			expect( Object.keys( cart.state.draftItems ) ).toEqual( [ '100' ] );
		} );

		it( 'removeDraftItem is a NO-OP when neither draftKey nor context resolves (never clears all)', async () => {
			const cart = await loadCartAndReady();
			cart.state.draftItems = {
				'100': { id: 100, quantity: 1 },
				'200': { id: 200, quantity: 1 },
			};
			// No context product (mainProductInContext resolves nothing) and no
			// explicit draftKey.
			mockContextProductId = undefined;
			cart.actions.removeDraftItem();
			// Must NOT clear all drafts.
			expect( Object.keys( cart.state.draftItems ) ).toHaveLength( 2 );
		} );
	} );

	describe( 'addItem', () => {
		it( 'with no draft uses the min-purchase-quantity fallback from context', async () => {
			const cart = await loadCartAndReady();
			mockContextProductId = 42;
			// A min-purchase product: `add_to_cart.minimum` is 3. The bare add
			// must fall back to that min, not to 1.
			mockContextMinPurchaseQuantity = 3;
			const { batchCalls } = installFetchMock( {
				onBatch: ( requests ) =>
					requests.map( () => ( {
						status: 200,
						body: {
							items: [
								{
									key: 'newkey',
									id: 42,
									quantity: 3,
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
				quantity: 3,
			} );
		} );

		it( 'with no draft falls back to quantity 1 when no min is exposed', async () => {
			const cart = await loadCartAndReady();
			mockContextProductId = 42;
			const { batchCalls } = installFetchMock( {
				onBatch: ( requests ) =>
					requests.map( () => ( {
						status: 200,
						body: { items: [], totals: {}, errors: [] },
					} ) ),
			} );

			await runAction(
				cart.actions.addItem() as unknown as Iterator< unknown >
			);

			expect( batchCalls[ 0 ][ 0 ].body ).toMatchObject( {
				id: 42,
				quantity: 1,
			} );
		} );

		it( 'throws when there is no payload, no draft, and no product in context', async () => {
			const cart = await loadCartAndReady();
			mockContextProductId = undefined;
			installFetchMock( {
				onBatch: ( requests ) =>
					requests.map( () => ( {
						status: 200,
						body: { items: [], totals: {}, errors: [] },
					} ) ),
			} );

			await expect(
				runAction(
					cart.actions.addItem() as unknown as Iterator< unknown >
				)
			).rejects.toThrow( 'addItem: no payload' );
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

		it( 'swaps in the purchasable id at send time, keeping the variation array', async () => {
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
					'wc-gift-note-demo': { 'gift-note': 'Happy birthday' },
				} ) as unknown as Iterator< unknown >
			);

			expect( batchCalls[ 0 ][ 0 ].body ).toMatchObject( {
				'wc-gift-note-demo': { 'gift-note': 'Happy birthday' },
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
			cart.state.draftItems = { '100': { id: 100, quantity: 1 } };
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

			expect( Object.keys( cart.state.draftItems ) ).toHaveLength( 1 );
		} );
	} );

	describe( 'addItem — merge-onto-confirmed optimism', () => {
		it( 'merges onto the single matching confirmed line instead of pushing a keyless twin (count ticks mid-flight)', async () => {
			const cart = await loadCartAndReady( {
				items: [
					{
						key: 'line-100',
						id: 100,
						quantity: 3,
						name: 'A',
						type: 'simple',
						extensions: {},
						item_data: [],
					},
				],
				totals: {},
				errors: [],
			} );
			// `onBatch` runs during the fetch, AFTER `applyOptimistic` mutated the
			// reactive state, so it observes exactly what the shopper sees while
			// the add is in flight.
			const midFlight: { items?: unknown[] } = {};
			installFetchMock( {
				onBatch: ( requests ) => {
					midFlight.items = JSON.parse(
						JSON.stringify( cart.state.cart.items )
					);
					return requests.map( () => ( {
						status: 200,
						body: { items: [], totals: {}, errors: [] },
					} ) );
				},
			} );

			await runAction(
				cart.actions.addItem( {
					id: 100,
					quantity: 1,
				} ) as unknown as Iterator< unknown >
			);

			// Mid-flight: the confirmed line was bumped 3 → 4 in place (key
			// preserved), NOT a keyless synthetic line pushed beside it.
			expect( midFlight.items ).toHaveLength( 1 );
			expect( midFlight.items?.[ 0 ] ).toMatchObject( {
				key: 'line-100',
				quantity: 4,
			} );
		} );

		it( 'envelope inCartQuantity sees the merged quantity mid-flight', async () => {
			const cart = await loadCartAndReady( {
				items: [
					{
						key: 'line-100',
						id: 100,
						quantity: 3,
						name: 'A',
						type: 'simple',
						extensions: {},
						item_data: [],
					},
				],
				totals: {},
				errors: [],
			} );
			mockContextProductId = 100;
			// Read the in-cart total DURING flight (inside onBatch, after
			// applyOptimistic). A keyless twin would leave two candidates and
			// blank the envelope; the merge keeps exactly one at quantity 4.
			let midFlightInCart: number | undefined;
			installFetchMock( {
				onBatch: ( requests ) => {
					midFlightInCart = cart.state.inCartQuantity( 100 );
					return requests.map( () => ( {
						status: 200,
						body: { items: [], totals: {}, errors: [] },
					} ) );
				},
			} );

			await runAction(
				cart.actions.addItem( {
					id: 100,
					quantity: 1,
				} ) as unknown as Iterator< unknown >
			);

			expect( midFlightInCart ).toBe( 4 );
		} );

		it( 'never merges onto a sold_individually line — pushes a keyless synthetic instead', async () => {
			const cart = await loadCartAndReady( {
				items: [
					{
						key: 'line-100',
						id: 100,
						quantity: 1,
						name: 'A',
						type: 'simple',
						sold_individually: true,
						extensions: {},
						item_data: [],
					},
				],
				totals: {},
				errors: [],
			} );
			const midFlight: { items?: unknown[] } = {};
			installFetchMock( {
				onBatch: ( requests ) => {
					midFlight.items = JSON.parse(
						JSON.stringify( cart.state.cart.items )
					);
					return requests.map( () => ( {
						status: 200,
						body: { items: [], totals: {}, errors: [] },
					} ) );
				},
			} );

			await runAction(
				cart.actions.addItem( {
					id: 100,
					quantity: 1,
				} ) as unknown as Iterator< unknown >
			);

			// The confirmed sold-individually line is untouched (still 1) and a
			// keyless synthetic add was pushed beside it — the doomed add surfaces
			// as the server op's error at settle, not a projected 2.
			expect( midFlight.items ).toHaveLength( 2 );
			const items = midFlight.items as Array< {
				key?: string;
				quantity: number;
			} >;
			expect(
				items.find( ( i ) => i.key === 'line-100' )?.quantity
			).toBe( 1 );
			expect( items.find( ( i ) => i.key === undefined )?.quantity ).toBe(
				1
			);
		} );

		it( 'ambiguous (two matching confirmed lines) → keyless synthetic push, no merge', async () => {
			const cart = await loadCartAndReady( {
				items: [
					{
						key: 'line-a',
						id: 100,
						quantity: 1,
						name: 'A',
						type: 'simple',
						extensions: {},
						item_data: [],
					},
					{
						key: 'line-b',
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
			const midFlight: { items?: unknown[] } = {};
			installFetchMock( {
				onBatch: ( requests ) => {
					midFlight.items = JSON.parse(
						JSON.stringify( cart.state.cart.items )
					);
					return requests.map( () => ( {
						status: 200,
						body: { items: [], totals: {}, errors: [] },
					} ) );
				},
			} );

			await runAction(
				cart.actions.addItem( {
					id: 100,
					quantity: 1,
				} ) as unknown as Iterator< unknown >
			);

			// Two matches → not exactly one → no merge. Both confirmed lines stay
			// at 1 and a keyless synthetic is pushed (3 items total).
			expect( midFlight.items ).toHaveLength( 3 );
			const bumped = (
				midFlight.items as Array< { quantity: number } >
			 ).filter( ( i ) => i.quantity !== 1 );
			expect( bumped ).toHaveLength( 0 );
		} );

		it( 'total failure rolls the merged quantity back to the confirmed value', async () => {
			const cart = await loadCartAndReady( {
				items: [
					{
						key: 'line-100',
						id: 100,
						quantity: 3,
						name: 'A',
						type: 'simple',
						extensions: {},
						item_data: [],
					},
				],
				totals: {},
				errors: [],
			} );
			// Batch fails for every request → total failure → rollback to the
			// pre-optimistic snapshot (quantity 3).
			installFetchMock( {
				onBatch: ( requests ) =>
					requests.map( () => ( {
						status: 500,
						body: {
							message: 'Simulated server error',
							code: 'internal_error',
						},
					} ) ),
			} );

			await runAction(
				cart.actions.addItem( {
					id: 100,
					quantity: 1,
				} ) as unknown as Iterator< unknown >
			);

			expect( cart.state.cart.items ).toHaveLength( 1 );
			expect(
				( cart.state.cart.items[ 0 ] as { quantity: number } ).quantity
			).toBe( 3 );
		} );
	} );

	describe( 'findItem({ filter }) — the explicit predicate escape hatch', () => {
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
			cart.state.draftItems = { '100': { id: 100, quantity: 1 } };
			mockContextProductId = 100;

			// The predicate selects the decorated line by its key — the escape
			// hatch a bundle editor uses to pair with a line the defaults reject.
			const env = cart.state.findItem( {
				id: 100,
				filter: ( item ) => item.key === 'decorated',
			} );
			expect( env.cart?.key ).toBe( 'decorated' );
		} );

		it( 'filter receives ( cartItem, { draft } )', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [ plainLine ] );
			cart.state.draftItems = {
				'100': { id: 100, quantity: 1, 'my-plugin': { token: 'seed' } },
			};
			mockContextProductId = 100;

			const seen: Array< {
				key: string | undefined;
				draftToken: unknown;
				draftId: number | undefined;
			} > = [];

			cart.state.findItem( {
				id: 100,
				filter: ( item, extra ) => {
					const draft = extra.draft as
						| Record< string, unknown >
						| undefined;
					const ns = draft?.[ 'my-plugin' ] as
						| { token?: unknown }
						| undefined;
					seen.push( {
						key: item.key,
						draftToken: ns?.token,
						draftId: draft?.id as number | undefined,
					} );
					return true;
				},
			} );

			expect( seen ).toEqual( [
				{
					key: 'plain',
					draftToken: 'seed',
					draftId: 100,
				},
			] );
		} );

		it( 'filter narrowing to exactly one among meta-twins → that line', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{ ...plainLine, key: 't1', extensions: { p: { n: 'A' } } },
				{ ...plainLine, key: 't2', extensions: { p: { n: 'B' } } },
			] );
			cart.state.draftItems = { '100': { id: 100, quantity: 1 } };
			mockContextProductId = 100;

			const env = cart.state.findItem( {
				id: 100,
				filter: ( item ) =>
					( item.extensions as { p?: { n?: string } } )?.p?.n === 'B',
			} );
			expect( env.cart?.key ).toBe( 't2' );
		} );

		it( 'filter selecting zero → cart undefined', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [ plainLine ] );
			cart.state.draftItems = { '100': { id: 100, quantity: 1 } };
			mockContextProductId = 100;

			const env = cart.state.findItem( {
				id: 100,
				filter: () => false,
			} );
			expect( env.cart ).toBeUndefined();
		} );

		it( 'filter leaving several → cart undefined (exactly-one still applies)', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{ ...plainLine, key: 'm1' },
				{ ...plainLine, key: 'm2' },
			] );
			cart.state.draftItems = { '100': { id: 100, quantity: 1 } };
			mockContextProductId = 100;

			const env = cart.state.findItem( {
				id: 100,
				filter: () => true,
			} );
			expect( env.cart ).toBeUndefined();
		} );

		it( 'key bypasses the filter param entirely', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{ ...plainLine, key: 'A' },
				{ ...plainLine, key: 'B' },
			] );
			const env = cart.state.findItem( {
				key: 'B',
				filter: () => false,
			} );
			expect( env.cart?.key ).toBe( 'B' );
		} );

		it( 'absent filter runs generic narrowing (no context filter inheritance)', async () => {
			const cart = await loadCartAndReady();
			// A single plain line generic rules WOULD pair. There is no context
			// filter to inherit anymore — absent filter always means generic.
			setCartItems( cart, [ plainLine ] );
			cart.state.draftItems = { '100': { id: 100, quantity: 1 } };
			mockContextProductId = 100;

			const env = cart.state.findItem( { id: 100 } );
			expect( env.cart?.key ).toBe( 'plain' );
		} );
	} );

	// The type-invariant in-cart aggregate. One read resolves the in-cart total
	// for ANY purchasable form — simple line, resolved variation line, or the
	// sum over a grouped parent's children — with no product-type branch.
	describe( 'inCartQuantity (type-invariant aggregate)', () => {
		it( 'simple: returns the product line quantity', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{
					key: 'abc',
					id: 100,
					quantity: 4,
					name: 'A',
					type: 'simple',
					extensions: {},
					item_data: [],
				},
			] );
			// findProduct( 100 ) → a simple product (no grouped_products), so the
			// aggregate falls through to the line quantity.
			mockFindProduct = ( { id } ) => ( { id } );
			expect( cart.state.inCartQuantity( 100 ) ).toBe( 4 );
		} );

		it( 'variable: returns the RESOLVED-variation line quantity', async () => {
			const cart = await loadCartAndReady();
			// The cart line carries the variation id (456), not the parent (100).
			setCartItems( cart, [
				{
					key: 'v',
					id: 456,
					quantity: 3,
					name: 'Hoodie - Green',
					type: 'variation',
					extensions: {},
					item_data: [],
				},
			] );
			// A draft carrying the variation selection is what the context surface
			// holds; findProduct resolves 100 → 456 (the purchasable id), which is
			// the line the aggregate must read.
			cart.state.draftItems = {
				'100': {
					id: 100,
					quantity: 1,
					variation: [ { attribute: 'color', value: 'green' } ],
				},
			};
			mockFindProduct = ( { id } ) =>
				id === 100 ? { id: 456 } : { id };
			expect( cart.state.inCartQuantity( 100 ) ).toBe( 3 );
		} );

		it( 'grouped: sums each child line quantity (parent has no line)', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{
					key: 'c1',
					id: 11,
					quantity: 2,
					name: 'Child 1',
					type: 'simple',
					extensions: {},
					item_data: [],
				},
				{
					key: 'c2',
					id: 12,
					quantity: 5,
					name: 'Child 2',
					type: 'simple',
					extensions: {},
					item_data: [],
				},
				// A third child that is NOT in the cart contributes 0.
			] );
			// findProduct( 9 ) → a grouped parent declaring its children;
			// findProduct( child ) → a plain product (no grouped_products) so each
			// child resolves to its own line quantity.
			mockFindProduct = ( { id } ) =>
				id === 9 ? { id: 9, grouped_products: [ 11, 12, 13 ] } : { id };
			// 2 (child 11) + 5 (child 12) + 0 (child 13 absent) = 7.
			expect( cart.state.inCartQuantity( 9 ) ).toBe( 7 );
		} );

		it( 'returns 0 when the product is not in the cart', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [] );
			mockFindProduct = ( { id } ) => ( { id } );
			expect( cart.state.inCartQuantity( 100 ) ).toBe( 0 );
		} );

		it( 'defaults to the context product id when called with no argument', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [
				{
					key: 'abc',
					id: 100,
					quantity: 6,
					name: 'A',
					type: 'simple',
					extensions: {},
					item_data: [],
				},
			] );
			mockContextProductId = 100;
			mockFindProduct = ( { id } ) => ( { id } );
			expect( cart.state.inCartQuantity() ).toBe( 6 );
		} );

		it( 'returns 0 when no id and no context product resolves', async () => {
			const cart = await loadCartAndReady();
			setCartItems( cart, [] );
			mockContextProductId = undefined;
			expect( cart.state.inCartQuantity() ).toBe( 0 );
		} );
	} );
} );
