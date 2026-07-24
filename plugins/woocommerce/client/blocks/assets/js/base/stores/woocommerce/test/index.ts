/**
 * External dependencies
 */
import type { CartItem, ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { DraftItem, DraftKey, SelectedAttributes } from '../cart';
import { GLOBAL_DRAFT_KEY } from '../draft-internals';

/**
 * The shared `woocommerce` namespace state this file's `store()` mock
 * resolves for every namespace **except** `woocommerce/products` — the
 * root module's own registration (`products.items`/`.variations`,
 * `draftItems`, and the real `itemInContext`/`findItem` accessors, merged
 * on at import time below) and `draft-internals.ts`'s reads all go through
 * this one object, matching the real Interactivity runtime resolving one
 * shared object per namespace across every `store()` call.
 */
type MockWooCommerceState = {
	products: {
		items: Record< number, ProductResponseItem >;
		variations: Record< number, ProductResponseItem >;
		productId?: number;
		variationId?: number | null;
	};
	draftItems: Record< DraftKey, DraftItem[] >;
	cart?: { items: ( CartItem | Record< string, unknown > )[] };
	itemInContext: {
		productId: number | null;
		variationId: number | null;
		draftKey: DraftKey;
		product: ProductResponseItem | null;
		variation: ProductResponseItem | null;
		baseProduct: ProductResponseItem | null;
		draftItem?: DraftItem | undefined;
		cartItem?: CartItem | undefined;
	};
	findItem: ( ref?: {
		id?: number;
		selectedAttributes?: SelectedAttributes[] | null;
		key?: string;
		filter?: ( item: CartItem ) => boolean;
	} ) => MockWooCommerceState[ 'itemInContext' ];
};

const mockState: MockWooCommerceState = {
	products: { items: {}, variations: {} },
	draftItems: {},
} as MockWooCommerceState;

/**
 * `cart-pairing.ts` is not migrated by this task — it still opens
 * `woocommerce/products` directly for its own attribute-term-slug
 * reconciliation (`matchesSelectedAttributes`). Every product/variation
 * this file seeds is mirrored here too (see {@link seedProduct}/
 * {@link seedVariation}) so that read degrades to the same data rather than
 * an empty/absent map.
 */
const mockProductsFlatState: {
	products: Record< number, ProductResponseItem >;
	productVariations: Record< number, ProductResponseItem >;
} = { products: {}, productVariations: {} };

/**
 * The value `getContext( 'woocommerce' )` should return, controlled per
 * test. `undefined` simulates a surface with no container of its own.
 */
let mockContext:
	| { productId?: number; variationId?: number | null; draftKey?: DraftKey }
	| undefined;

/**
 * When `true`, the mocked `getContext` throws regardless of namespace,
 * reproducing the real Interactivity runtime's behavior when called with no
 * directive currently executing on the call stack.
 */
let mockContextThrows = false;

/**
 * The value `getServerState( 'woocommerce' )` should return, controlled per
 * test.
 */
let mockDraftSeeds: Record< DraftKey, Record< number, DraftItem > > | undefined;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getContext: jest.fn( () => {
			if ( mockContextThrows ) {
				throw new Error(
					'Cannot call `getContext()` when there is no scope.'
				);
			}
			return mockContext;
		} ),
		getServerState: jest.fn( () => ( { draftSeeds: mockDraftSeeds } ) ),
		store: jest.fn(
			( namespace: string, definition?: { state?: object } ) => {
				if ( namespace === 'woocommerce/products' ) {
					return { state: mockProductsFlatState };
				}
				if ( definition?.state ) {
					Object.defineProperties(
						mockState,
						Object.getOwnPropertyDescriptors( definition.state )
					);
				}
				return { state: mockState };
			}
		),
	} ),
	{ virtual: true }
);

// A plain top-level `import` would be hoisted above `mockState`'s own
// declaration by the module transform (exactly like every other `import`
// in the file), running the root module's eager `store()` registration
// call before `mockState` exists. Requiring it here, after `mockState` is
// declared, is what proves the `@woocommerce/stores/woocommerce` alias
// resolves in Jest while keeping the registration order correct.
// eslint-disable-next-line @typescript-eslint/no-var-requires, import/order
require( '@woocommerce/stores/woocommerce' );

/**
 * Seeds a product into both the unified nested shape (`state.products.
 * items`, read by `draft-internals.ts`/the root module) and the flat
 * `woocommerce/products` mock (read by `cart-pairing.ts`'s own,
 * not-yet-migrated `matchesSelectedAttributes`).
 *
 * @param product The product to seed.
 */
function seedProduct( product: ProductResponseItem ): void {
	mockState.products.items[ product.id ] = product;
	mockProductsFlatState.products[ product.id ] = product;
}

/**
 * Seeds a variation into both the unified nested shape and the flat
 * `woocommerce/products` mock. See {@link seedProduct}.
 *
 * @param variation The variation to seed.
 */
function seedVariation( variation: ProductResponseItem ): void {
	mockState.products.variations[ variation.id ] = variation;
	mockProductsFlatState.productVariations[ variation.id ] = variation;
}

/**
 * Seeds `state.cart.items` with the given lines.
 *
 * @param items The cart lines to expose via `state.cart.items`.
 */
function seedCart( items: CartItem[] ): void {
	mockState.cart = { items };
}

/**
 * Narrows an envelope's `draftItem` to a defined `DraftItem` — present
 * whenever an id resolves, per the envelope's own contract.
 *
 * @param draftItem The envelope's `draftItem`, expected to be defined.
 * @return The defined draft view.
 */
function assertDraftItem( draftItem: DraftItem | undefined ): DraftItem {
	if ( ! draftItem ) {
		throw new Error( 'Expected the draft view to be defined.' );
	}
	return draftItem;
}

/**
 * Builds a minimal server-confirmed cart line, optionally carrying
 * namespaced extension data under `extensions[namespace]`.
 *
 * @param overrides Partial cart-line fields to override the defaults.
 * @return A cart line suitable for seeding `state.cart.items`.
 */
function makeLine( overrides: Partial< CartItem > = {} ): CartItem {
	return {
		key: 'server-key-abc',
		id: 42,
		type: 'simple',
		quantity: 3,
		name: 'Test Product',
		sold_individually: false,
		variation: [],
		item_data: [],
		extensions: {},
		...overrides,
	} as CartItem;
}

/**
 * A variable base product whose family backs the variation-resolution
 * tests: variation 20 fixes `Color` but leaves `Logo` as "any" (`value:
 * null`); variation 21 fixes both attributes concretely.
 */
const familyBase = {
	id: 10,
	type: 'variable',
	variations: [
		{
			id: 20,
			attributes: [
				{ name: 'Color', value: 'blue' },
				{ name: 'Logo', value: null },
			],
		},
		{
			id: 21,
			attributes: [
				{ name: 'Color', value: 'red' },
				{ name: 'Logo', value: 'yes' },
			],
		},
	],
} as unknown as ProductResponseItem;

const variation20 = { id: 20, parent: 10, name: 'Blue' } as ProductResponseItem;
const variation21 = {
	id: 21,
	parent: 10,
	name: 'Red/Yes',
} as ProductResponseItem;

beforeEach( () => {
	mockState.products = { items: {}, variations: {} };
	mockState.draftItems = {};
	delete mockState.cart;
	mockProductsFlatState.products = {};
	mockProductsFlatState.productVariations = {};
	mockContext = undefined;
	mockContextThrows = false;
	mockDraftSeeds = undefined;
} );

describe( 'the woocommerce root module', () => {
	it( 'registers state.itemInContext and state.findItem', () => {
		expect( mockState.itemInContext ).toBeDefined();
		expect( typeof mockState.findItem ).toBe( 'function' );
	} );

	it( 'registers state.products/draftItems, not state.cart', () => {
		expect( mockState.products ).toEqual( { items: {}, variations: {} } );
		expect( mockState.draftItems ).toEqual( {} );
		expect( mockState.cart ).toBeUndefined();
	} );

	describe( 'laziness', () => {
		it( 'reading product does not run the pairing ladder (cartItem stays lazy)', () => {
			seedProduct( { id: 42, type: 'simple' } as ProductResponseItem );
			mockContext = { productId: 42 };
			// A getter spy on `cartItem` would prove non-invocation directly,
			// but the envelope is a plain object literal recreated per read —
			// reading `.product` must not throw even with no cart items and
			// no draft seeded, and must not itself materialize a draft.
			expect( () => mockState.itemInContext.product ).not.toThrow();
			expect( mockState.draftItems[ GLOBAL_DRAFT_KEY ] ).toBeUndefined();
		} );

		it( 'reading cartItem does not resolve the product', () => {
			seedCart( [ makeLine( { id: 42 } ) ] );
			// No product data loaded at all for id 42 — resolving `product`
			// would necessarily be `null`, but `cartItem` still pairs by
			// identity without needing product data.
			expect( mockState.findItem( { id: 42 } ).cartItem ).toEqual(
				makeLine( { id: 42 } )
			);
		} );

		it( 'draftItem returns the cached view without materialising a collection', () => {
			expect( mockState.findItem( { id: 42 } ).draftItem ).toBeDefined();
			expect( mockState.draftItems[ GLOBAL_DRAFT_KEY ] ).toBeUndefined();
		} );

		it( 'rebuilds the envelope per read (values, not identity) while the draftItem view is stable across reads', () => {
			const first = mockState.findItem( { id: 42 } );
			const second = mockState.findItem( { id: 42 } );

			expect( first ).not.toBe( second );
			expect( first.draftItem ).toBe( second.draftItem );
		} );
	} );

	describe( 'itemInContext — addressing', () => {
		it( 'resolves productId/variationId/draftKey from state.products.* when no context is active', () => {
			mockState.products.productId = 42;
			mockState.products.variationId = 99;

			expect( mockState.itemInContext.productId ).toBe( 42 );
			expect( mockState.itemInContext.draftKey ).toBe( GLOBAL_DRAFT_KEY );
		} );

		it( 'resolves productId/draftKey from the woocommerce context bag when a container declares one', () => {
			mockState.products.productId = 1;
			mockContext = { productId: 42, draftKey: 'collection/q1/42' };

			expect( mockState.itemInContext.productId ).toBe( 42 );
			expect( mockState.itemInContext.draftKey ).toBe(
				'collection/q1/42'
			);
		} );

		it( 'addressing resolves even when no product data is loaded', () => {
			mockContext = { productId: 999 };

			expect( mockState.itemInContext.productId ).toBe( 999 );
			expect( mockState.itemInContext.product ).toBeNull();
			expect( mockState.itemInContext.draftItem ).toBeDefined();
		} );

		it( 'degrades to the global draft key without throwing when read outside a directive', () => {
			mockContextThrows = true;

			expect( () => mockState.itemInContext.draftKey ).not.toThrow();
			expect( mockState.itemInContext.draftKey ).toBe( GLOBAL_DRAFT_KEY );
		} );
	} );

	describe( 'itemInContext.baseProduct', () => {
		it( 'returns the product when variationId is null', () => {
			seedProduct( { id: 42, type: 'simple' } as ProductResponseItem );
			mockState.products.productId = 42;
			mockState.products.variationId = null;

			expect( mockState.itemInContext.baseProduct ).toEqual( {
				id: 42,
				type: 'simple',
			} );
		} );

		it( 'returns the base product even when variationId is set — never the variation', () => {
			seedProduct( familyBase );
			seedVariation( variation20 );
			mockState.products.productId = 10;
			mockState.products.variationId = 20;

			expect( mockState.itemInContext.baseProduct ).toBe( familyBase );
		} );

		it( 'returns null when the product is not in the store', () => {
			mockState.products.productId = 999;

			expect( mockState.itemInContext.baseProduct ).toBeNull();
		} );

		it( 'returns null when no productId resolves at all', () => {
			expect( mockState.itemInContext.baseProduct ).toBeNull();
		} );

		it( 'reads productId from the context bag over state', () => {
			seedProduct( { id: 42, type: 'simple' } as ProductResponseItem );
			mockState.products.productId = 1;
			mockContext = { productId: 42 };

			expect( mockState.itemInContext.baseProduct ).toEqual(
				expect.objectContaining( { id: 42 } )
			);
		} );
	} );

	describe( 'itemInContext.variation — family-draft derivation', () => {
		beforeEach( () => {
			seedProduct( familyBase );
			seedVariation( variation20 );
			seedVariation( variation21 );
			mockState.products.productId = 10;
			mockState.products.variationId = null;
		} );

		it( 'returns null when variationId is null and no family draft exists (simple/variable, no selection)', () => {
			expect( mockState.itemInContext.variation ).toBeNull();
		} );

		it( 'returns the variation when variationId is set directly (no family draft in the collection)', () => {
			mockState.products.variationId = 20;

			expect( mockState.itemInContext.variation ).toBe( variation20 );
		} );

		it( 'returns null when the addressed variationId names nothing in state.products.variations', () => {
			mockState.products.variationId = 999;

			expect( mockState.itemInContext.variation ).toBeNull();
		} );

		it( 'resolves the matching variation for a family draft carrying a resolvable attribute set, with no further call', () => {
			mockState.draftItems[ GLOBAL_DRAFT_KEY ] = [
				{
					id: 10,
					quantity: 1,
					variation: [
						{ attribute: 'Color', value: 'red' },
						{ attribute: 'Logo', value: 'yes' },
					],
				} as DraftItem,
			];

			expect( mockState.itemInContext.variation ).toBe( variation21 );
			expect( mockState.itemInContext.product ).toBe( variation21 );
		} );

		it( 'resolves null for a family draft carrying an unresolvable attribute set, exactly as an equivalent variationId write resolves today', () => {
			mockState.draftItems[ GLOBAL_DRAFT_KEY ] = [
				{
					id: 10,
					quantity: 1,
					variation: [ { attribute: 'Color', value: 'green' } ],
				} as DraftItem,
			];

			expect( mockState.itemInContext.variation ).toBeNull();
			// The entry-point-divergent no-match contract: itemInContext
			// falls back to the base product rather than blanking.
			expect( mockState.itemInContext.product ).toBe( familyBase );
		} );

		it( 'resolves the variation via the id-direct rung for a family draft carrying a variation id but no attributes', () => {
			mockState.draftItems[ GLOBAL_DRAFT_KEY ] = [
				{ id: 20, quantity: 1 } as DraftItem,
			];

			expect( mockState.itemInContext.variation ).toBe( variation20 );
		} );

		it( 'falls back to variationId context/state resolution when no family draft exists in the collection (SSR/first-paint parity)', () => {
			expect( mockState.draftItems[ GLOBAL_DRAFT_KEY ] ).toBeUndefined();

			mockState.products.variationId = 20;

			expect( mockState.itemInContext.variation ).toBe( variation20 );
		} );

		it( 'reads variationId from context when available, and honors an explicit context null over state', () => {
			mockState.products.variationId = 99;
			mockContext = { productId: 10, variationId: null };

			expect( mockState.itemInContext.variation ).toBeNull();
		} );
	} );

	describe( 'itemInContext.product — entry-point-divergent no-match contract', () => {
		it( 'falls back to the base product on a no-match variable selection', () => {
			seedProduct( familyBase );
			seedVariation( variation20 );
			mockState.products.productId = 10;
			mockState.draftItems[ GLOBAL_DRAFT_KEY ] = [
				{
					id: 10,
					quantity: 1,
					variation: [ { attribute: 'Color', value: 'unknown' } ],
				} as DraftItem,
			];

			expect( mockState.itemInContext.product ).toBe( familyBase );
		} );

		it( 'returns the variation when one resolves', () => {
			seedProduct( familyBase );
			seedVariation( variation20 );
			mockState.products.productId = 10;
			mockState.products.variationId = 20;

			expect( mockState.itemInContext.product ).toBe( variation20 );
		} );

		it( 'returns null when neither the base product nor a variation resolves', () => {
			mockState.products.productId = 999;

			expect( mockState.itemInContext.product ).toBeNull();
		} );
	} );

	describe( 'findItem — product resolution (the resolveProduct primitive)', () => {
		it( 'returns null when the id names no product or variation', () => {
			expect( mockState.findItem( { id: 999 } ).product ).toBeNull();
		} );

		it( 'returns the product itself for a simple product', () => {
			const simpleProduct = {
				id: 1,
				type: 'simple',
			} as ProductResponseItem;
			seedProduct( simpleProduct );

			expect( mockState.findItem( { id: 1 } ).product ).toEqual(
				simpleProduct
			);
		} );

		it( 'returns the matched variation when selectedAttributes match and the variation is populated', () => {
			const variableProduct = {
				id: 1,
				type: 'variable',
				variations: [
					{
						id: 10,
						attributes: [ { name: 'Color', value: 'red' } ],
					},
				],
			} as unknown as ProductResponseItem;
			const populatedVariation = {
				id: 10,
				name: 'Red Variation',
			} as ProductResponseItem;
			seedProduct( variableProduct );
			seedVariation( populatedVariation );

			const result = mockState.findItem( {
				id: 1,
				selectedAttributes: [ { attribute: 'Color', value: 'red' } ],
			} ).product;

			expect( result ).toEqual( populatedVariation );
		} );

		it( 'returns null when attributes match but the variation is not populated (the existence-probe contract)', () => {
			const variableProduct = {
				id: 1,
				type: 'variable',
				variations: [
					{
						id: 10,
						attributes: [ { name: 'Color', value: 'red' } ],
					},
				],
			} as unknown as ProductResponseItem;
			seedProduct( variableProduct );

			const result = mockState.findItem( {
				id: 1,
				selectedAttributes: [ { attribute: 'Color', value: 'red' } ],
			} ).product;

			expect( result ).toBeNull();
		} );

		it( 'returns the parent product when the product is variable and no attributes are selected', () => {
			const variableProduct = {
				id: 1,
				type: 'variable',
				variations: [
					{
						id: 10,
						attributes: [ { name: 'Color', value: 'red' } ],
					},
				],
			} as unknown as ProductResponseItem;
			seedProduct( variableProduct );

			expect( mockState.findItem( { id: 1 } ).product ).toEqual(
				variableProduct
			);
			expect(
				mockState.findItem( { id: 1, selectedAttributes: [] } ).product
			).toEqual( variableProduct );
		} );

		it( 'returns null when attributes do not match any variation', () => {
			const variableProduct = {
				id: 1,
				type: 'variable',
				variations: [
					{
						id: 10,
						attributes: [ { name: 'Color', value: 'red' } ],
					},
				],
			} as unknown as ProductResponseItem;
			seedProduct( variableProduct );
			seedVariation( { id: 10 } as ProductResponseItem );

			const result = mockState.findItem( {
				id: 1,
				selectedAttributes: [ { attribute: 'Color', value: 'blue' } ],
			} ).product;

			expect( result ).toBeNull();
		} );

		it( 'returns the variation directly when given a variation id — ignoring selectedAttributes', () => {
			const variation = {
				id: 50,
				name: 'Direct Variation',
			} as ProductResponseItem;
			seedVariation( variation );

			expect( mockState.findItem( { id: 50 } ).product ).toEqual(
				variation
			);
			expect(
				mockState.findItem( {
					id: 50,
					selectedAttributes: [
						{ attribute: 'Color', value: 'blue' },
					],
				} ).product
			).toEqual( variation );
		} );

		it( 'prefers variation lookup over product lookup when id exists in both', () => {
			seedProduct( {
				id: 50,
				type: 'simple',
				name: 'Product 50',
			} as ProductResponseItem );
			const variation = {
				id: 50,
				name: 'Variation 50',
			} as ProductResponseItem;
			seedVariation( variation );

			expect( mockState.findItem( { id: 50 } ).product ).toEqual(
				variation
			);
		} );

		it( 'resolves the family base as findItem(...).baseProduct for a variation id', () => {
			seedProduct( familyBase );
			seedVariation( variation20 );

			expect( mockState.findItem( { id: 20 } ).baseProduct ).toBe(
				familyBase
			);
			expect( mockState.findItem( { id: 20 } ).variation ).toBe(
				variation20
			);
		} );

		it( "findItem(...).variation is null for a resolved base/simple product — it never assigns the base as its own 'variation'", () => {
			seedProduct( { id: 1, type: 'simple' } as ProductResponseItem );

			expect( mockState.findItem( { id: 1 } ).variation ).toBeNull();
		} );

		it( 'addressing (productId) resolves to the given id even when no product record backs it', () => {
			expect( mockState.findItem( { id: 777 } ).productId ).toBe( 777 );
			expect( mockState.findItem( { id: 777 } ).product ).toBeNull();
		} );
	} );

	describe( 'draftItem — the draft view', () => {
		function makeDraft( overrides: Partial< DraftItem > = {} ): DraftItem {
			return { id: 42, quantity: 1, ...overrides } as DraftItem;
		}

		it( 'starts as an empty keyed map — nothing server-seeds it', () => {
			expect( mockState.draftItems ).toEqual( {} );
		} );

		it( 'creates the session-global collection lazily, on its first write through the draft view', () => {
			expect( mockState.draftItems[ GLOBAL_DRAFT_KEY ] ).toBeUndefined();

			assertDraftItem(
				mockState.findItem( { id: 42 } ).draftItem
			).quantity = 1;

			expect( mockState.draftItems[ GLOBAL_DRAFT_KEY ] ).toEqual( [
				makeDraft(),
			] );
			expect( mockState.findItem( { id: 42 } ).draftItem ).toEqual(
				makeDraft()
			);
		} );

		it( 'merges a second write to the same product id instead of duplicating it', () => {
			assertDraftItem(
				mockState.findItem( { id: 42 } ).draftItem
			).quantity = 2;
			assertDraftItem(
				mockState.findItem( { id: 42 } ).draftItem
			).quantity = 5;

			expect( mockState.draftItems[ GLOBAL_DRAFT_KEY ] ).toEqual( [
				makeDraft( { quantity: 5 } ),
			] );
		} );

		it( 'keeps drafts for the same product id independent across the session-global collection and a keyed container collection', () => {
			assertDraftItem(
				mockState.findItem( { id: 42 } ).draftItem
			).quantity = 1;

			mockContext = { draftKey: 'collection/q1/42' };
			assertDraftItem(
				mockState.findItem( { id: 42 } ).draftItem
			).quantity = 9;

			expect( mockState.draftItems[ GLOBAL_DRAFT_KEY ] ).toEqual( [
				makeDraft( { quantity: 1 } ),
			] );
			expect( mockState.draftItems[ 'collection/q1/42' ] ).toEqual( [
				makeDraft( { quantity: 9 } ),
			] );
		} );

		it( 'stores namespaced extension props at the draft payload root, enumerable through the view', () => {
			const draftItem = assertDraftItem(
				mockState.findItem( { id: 42 } ).draftItem
			);
			draftItem.quantity = 1;
			draftItem[ 'my-plugin/gift-note' ] = 'Happy birthday!';

			expect( mockState.draftItems[ GLOBAL_DRAFT_KEY ][ 0 ] ).toEqual(
				expect.objectContaining( {
					id: 42,
					quantity: 1,
					'my-plugin/gift-note': 'Happy birthday!',
				} )
			);
		} );

		it( 'rejects a direct write to draftItem.id, applying no state change', () => {
			assertDraftItem(
				mockState.findItem( { id: 42 } ).draftItem
			).quantity = 1;

			assertDraftItem(
				mockState.findItem( { id: 42 } ).draftItem
			).id = 99;

			expect( mockState.draftItems[ GLOBAL_DRAFT_KEY ] ).toEqual( [
				makeDraft(),
			] );
			expect( console ).toHaveWarned();
		} );

		it( "reads the surface's server-filed seed through the view on an untouched surface, without creating a collection", () => {
			mockDraftSeeds = {
				[ GLOBAL_DRAFT_KEY ]: {
					42: { id: 42, quantity: 3 } as DraftItem,
				},
			};

			const draftItem = assertDraftItem(
				mockState.findItem( { id: 42 } ).draftItem
			);

			expect( draftItem.quantity ).toBe( 3 );
			expect( mockState.draftItems[ GLOBAL_DRAFT_KEY ] ).toBeUndefined();
		} );

		it( 'itemInContext.draftItem resolves via the in-context id — the resolved item id, variation id when selected', () => {
			seedProduct( familyBase );
			seedVariation( variation20 );
			mockState.products.productId = 10;
			mockState.products.variationId = 20;

			assertDraftItem( mockState.itemInContext.draftItem ).quantity = 4;

			expect( mockState.draftItems[ GLOBAL_DRAFT_KEY ] ).toEqual( [
				{ id: 20, quantity: 4 },
			] );
		} );

		it( 'itemInContext.draftItem is undefined when nothing addresses an item at all', () => {
			expect( mockState.itemInContext.draftItem ).toBeUndefined();
		} );
	} );

	describe( 'cartItem — the pairing ladder', () => {
		it( 'itemInContext.cartItem is undefined when no product is in context', () => {
			seedCart( [] );

			expect( mockState.itemInContext.cartItem ).toBeUndefined();
		} );

		it( 'itemInContext.cartItem is undefined when the in-context product is not in the cart', () => {
			seedCart( [ makeLine( { id: 99 } ) ] );
			mockState.products.productId = 42;

			expect( mockState.itemInContext.cartItem ).toBeUndefined();
		} );

		it( 'itemInContext.cartItem pairs via product identity when exactly one line matches', () => {
			const line = makeLine( { id: 42 } );
			seedCart( [ line, makeLine( { id: 99 } ) ] );
			mockState.products.productId = 42;

			expect( mockState.itemInContext.cartItem ).toEqual( line );
			expect( mockState.itemInContext.draftItem ).toBeDefined();
		} );

		it( 'itemInContext.cartItem disambiguates same-id lines via a namespaced extension-prop match against the draft', () => {
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
			mockState.products.productId = 42;
			mockState.draftItems[ GLOBAL_DRAFT_KEY ] = [
				{
					id: 42,
					quantity: 1,
					'my-plugin/giftNote': 'B',
				} as DraftItem,
			];

			expect( mockState.itemInContext.cartItem ).toEqual( giftB );
		} );

		it( 'itemInContext.cartItem never guesses: ambiguous identity/extension matches leave cartItem undefined', () => {
			seedCart( [
				makeLine( { id: 42, key: 'line-a' } ),
				makeLine( { id: 42, key: 'line-b' } ),
			] );
			mockState.products.productId = 42;

			expect( mockState.itemInContext.cartItem ).toBeUndefined();
		} );

		it( 'findItem returns the same paired line for an explicit id, key, or filter', () => {
			const line = makeLine( { id: 42, key: 'the-key' } );
			seedCart( [ line ] );

			expect( mockState.findItem( { id: 42 } ).cartItem ).toEqual( line );
			expect( mockState.findItem( { key: 'the-key' } ).cartItem ).toEqual(
				line
			);
			expect(
				mockState.findItem( {
					filter: ( item ) => item.id === 42,
				} ).cartItem
			).toEqual( line );
		} );

		it( "findItem({ key }) resolves draftItem from the matched line's own id when no id is also given", () => {
			seedCart( [ makeLine( { id: 42, key: 'the-key' } ) ] );

			assertDraftItem(
				mockState.findItem( { key: 'the-key' } ).draftItem
			).quantity = 6;

			expect( mockState.draftItems[ GLOBAL_DRAFT_KEY ] ).toEqual( [
				{ id: 42, quantity: 6 },
			] );
		} );

		it( 'writing itemInContext.draftItem.variation re-files the draft under the resolved variation id', () => {
			seedProduct( familyBase );
			seedVariation( variation20 );
			mockState.products.productId = 10;

			const heldView = assertDraftItem(
				mockState.itemInContext.draftItem
			);
			heldView.quantity = 2;

			// `familyBase`'s variation 20 leaves `Logo` as "any" — a
			// concrete (non-null) value for it is required for the match,
			// alongside the fixed `Color: blue`.
			assertDraftItem( mockState.itemInContext.draftItem ).variation = [
				{ attribute: 'Color', value: 'blue' },
				{ attribute: 'Logo', value: 'none' },
			];

			expect( heldView.id ).toBe( 20 );
			expect( heldView.quantity ).toBe( 2 );
			expect( mockState.draftItems[ GLOBAL_DRAFT_KEY ] ).toEqual( [
				{
					id: 20,
					quantity: 2,
					variation: [
						{ attribute: 'Color', value: 'blue' },
						{ attribute: 'Logo', value: 'none' },
					],
				},
			] );
		} );

		describe( 'effective-attribute pairing (id-direct, untouched-seed, "any" disambiguation)', () => {
			function seedBlueVariationFamily() {
				seedProduct( {
					id: 10,
					type: 'variable',
					variations: [
						{
							id: 20,
							attributes: [ { name: 'Color', value: 'blue' } ],
						},
					],
				} as unknown as ProductResponseItem );
				seedVariation( {
					id: 20,
					parent: 10,
				} as ProductResponseItem );
			}

			it( 'pairs a materialized id-direct draft ({id: variationId, variation: []}) to its server cart line via effective attributes', () => {
				seedBlueVariationFamily();
				const line = makeLine( {
					id: 20,
					type: 'variation',
					quantity: 4,
					variation: [
						{
							attribute: 'Color',
							value: 'blue',
							raw_attribute: 'attribute_pa_color',
						},
					],
				} );
				seedCart( [ line ] );
				mockState.products.productId = 20;
				mockState.draftItems[ GLOBAL_DRAFT_KEY ] = [
					{ id: 20, quantity: 1, variation: [] } as DraftItem,
				];

				expect( mockState.findItem( { id: 20 } ).cartItem ).toEqual(
					line
				);
			} );

			it( 'pairs an untouched default-attribute surface (a parent-filed seed, variation: []) to a pre-existing cart line for the resolved variation, with no write', () => {
				seedBlueVariationFamily();
				const line = makeLine( {
					id: 20,
					type: 'variation',
					quantity: 2,
					variation: [
						{
							attribute: 'Color',
							value: 'blue',
							raw_attribute: 'attribute_pa_color',
						},
					],
				} );
				seedCart( [ line ] );
				mockDraftSeeds = {
					[ GLOBAL_DRAFT_KEY ]: { 10: { id: 10, quantity: 1 } },
				};

				expect( mockState.findItem( { id: 20 } ).cartItem ).toEqual(
					line
				);
				expect(
					mockState.draftItems[ GLOBAL_DRAFT_KEY ]
				).toBeUndefined();
			} );

			it( 'an unspecified "any" effective payload pairs to nothing — no invented match', () => {
				seedProduct( {
					id: 30,
					type: 'variable',
					variations: [
						{
							id: 40,
							attributes: [ { name: 'Color', value: null } ],
						},
					],
				} as unknown as ProductResponseItem );
				seedVariation( {
					id: 40,
					parent: 30,
				} as ProductResponseItem );
				const redLine = makeLine( {
					id: 40,
					type: 'variation',
					quantity: 2,
					variation: [
						{
							attribute: 'Color',
							value: 'red',
							raw_attribute: 'attribute_pa_color',
						},
					],
				} );
				seedCart( [ redLine ] );
				mockState.draftItems[ GLOBAL_DRAFT_KEY ] = [
					{ id: 40, quantity: 1, variation: [] } as DraftItem,
				];

				expect(
					mockState.findItem( { id: 40 } ).cartItem
				).toBeUndefined();
			} );

			it( 'a specified "any" value pairs to the matching line, disambiguating multiple same-id lines by value', () => {
				seedProduct( {
					id: 30,
					type: 'variable',
					variations: [
						{
							id: 40,
							attributes: [ { name: 'Color', value: null } ],
						},
					],
				} as unknown as ProductResponseItem );
				seedVariation( {
					id: 40,
					parent: 30,
				} as ProductResponseItem );
				const redLine = makeLine( {
					key: 'red-key',
					id: 40,
					type: 'variation',
					quantity: 2,
					variation: [
						{
							attribute: 'Color',
							value: 'red',
							raw_attribute: 'attribute_pa_color',
						},
					],
				} );
				const blueLine = makeLine( {
					key: 'blue-key',
					id: 40,
					type: 'variation',
					quantity: 5,
					variation: [
						{
							attribute: 'Color',
							value: 'blue',
							raw_attribute: 'attribute_pa_color',
						},
					],
				} );
				seedCart( [ redLine, blueLine ] );
				mockState.draftItems[ GLOBAL_DRAFT_KEY ] = [
					{
						id: 40,
						quantity: 1,
						variation: [ { attribute: 'Color', value: 'blue' } ],
					} as DraftItem,
				];

				expect( mockState.findItem( { id: 40 } ).cartItem ).toEqual(
					blueLine
				);
			} );
		} );
	} );

	describe( 'cartItem guard — state.cart absent', () => {
		it( 'itemInContext.cartItem is undefined, without throwing, when state.cart is absent', () => {
			mockState.products.productId = 42;
			expect( mockState.cart ).toBeUndefined();

			expect( () => mockState.itemInContext.cartItem ).not.toThrow();
			expect( mockState.itemInContext.cartItem ).toBeUndefined();
		} );

		it( 'findItem({ id }).cartItem is undefined, without throwing, when state.cart is absent', () => {
			expect(
				() => mockState.findItem( { id: 42 } ).cartItem
			).not.toThrow();
			expect( mockState.findItem( { id: 42 } ).cartItem ).toBeUndefined();
		} );

		it( 'draftItem still resolves when state.cart is absent', () => {
			mockState.products.productId = 42;

			expect( mockState.itemInContext.draftItem ).toBeDefined();
			expect( mockState.findItem( { id: 42 } ).draftItem ).toBeDefined();
		} );
	} );
} );
