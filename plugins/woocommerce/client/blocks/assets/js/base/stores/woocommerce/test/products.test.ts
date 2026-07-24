/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { ProductsStore } from '../products';
import type { DraftItem, DraftKey } from '../cart';
import { GLOBAL_DRAFT_KEY } from '../draft-internals';

let mockRegisteredStore: {
	state: ProductsStore[ 'state' ];
} | null = null;

let mockStoreState: ProductsStore[ 'state' ];

let mockContext: { productId?: number; variationId?: number | null } | null =
	null;

/**
 * The mocked `woocommerce/cart` namespace state `draft-internals.ts` reads
 * raw `state.draftItems` from. Kept as a single stable object per test (never
 * replaced wholesale) so a write recorded through the products-side setter
 * stays visible to a subsequent getter read within the same test — matching
 * the real Interactivity runtime returning the same persistent store object
 * across repeated `store()` calls.
 *
 * A products-only page where `cart.ts` never registered its state at all
 * (an empty stub with no `draftItems` key) is simulated by casting a bare
 * `{}` onto this variable directly, rather than by widening this type —
 * proving the getter's/setter's reads degrade rather than throw.
 */
let mockCartState: { draftItems: Record< DraftKey, DraftItem[] > };

/**
 * The mocked `getServerState( 'woocommerce/cart' )` payload's `draftSeeds`,
 * controlled per test. `undefined` simulates a page carrying no server-filed
 * seeds at all.
 */
let mockDraftSeeds: Record< DraftKey, Record< number, DraftItem > > | undefined;

/**
 * The `woocommerce/products` namespace's own state, held stable across the
 * repeated `store()` calls a single test makes: once for `products.ts`'s own
 * registration (carrying the real `state` definition — getters included),
 * and again for every lazy per-call read `draft-internals.ts` makes internally
 * (via `getProductsState()`, passing an effectively empty definition). Both
 * must resolve the identical object — never a freshly reconstructed one —
 * or a family-resolution helper's read would silently miss data the test
 * hydrated onto the first-returned reference.
 */
let mockProductsStateBase: {
	products: Record< number, ProductResponseItem >;
	productVariations: Record< number, ProductResponseItem >;
	productId: number;
	variationId: number | null;
};

const getMockStoreState = (): ProductsStore[ 'state' ] => {
	if ( mockRegisteredStore === null ) {
		throw new Error(
			'Expected woocommerce/products store to be registered.'
		);
	}
	return mockRegisteredStore.state;
};

const mockProduct = {
	id: 42,
	name: 'Test Product',
} as ProductResponseItem;

const mockVariation = {
	id: 99,
	name: 'Test Variation',
} as ProductResponseItem;

/**
 * A variable product family backing the family-draft-derivation and setter
 * tests: variation 20 fixes `Color` but leaves `Logo` as "any" (`value:
 * null`); variation 21 fixes both attributes concretely.
 * `productVariations[20]`/`[21]` deliberately carry no `attributes` field of
 * their own — the real Store API serializer always leaves it empty, so
 * neither the derivation nor the setter may depend on it; the only usable
 * per-variation attribute map is `familyVariableProduct.variations[]` (slug-valued,
 * per the real shape).
 */
const familyVariableProduct = {
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
	name: 'Red, Yes Logo',
} as ProductResponseItem;

/** A variation belonging to a different, unrelated product family. */
const foreignVariation = {
	id: 500,
	parent: 999,
	name: 'Foreign',
} as ProductResponseItem;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: jest.fn( ( namespace, definition ) => {
			if ( namespace === 'woocommerce/products' ) {
				// Simulate server-hydrated state merged with client
				// definition. Getters from definition.state are preserved,
				// and productId / variationId are added as plain values
				// (simulating wp_interactivity_state hydration). A repeated
				// call passing no `state` (draft-internals.ts's lazy reads)
				// merges nothing new and simply re-wraps the same base —
				// exactly how the real Interactivity runtime resolves an
				// already-registered namespace.
				if ( definition?.state ) {
					Object.defineProperties(
						mockProductsStateBase,
						Object.getOwnPropertyDescriptors( definition.state )
					);
				}
				mockRegisteredStore = {
					state: mockProductsStateBase as ProductsStore[ 'state' ],
				};
				return mockRegisteredStore;
			}
			if ( namespace === 'woocommerce/cart' ) {
				// A stable reference: repeated calls (draft-internals.ts
				// never caches state at module scope) must observe the same
				// object, so a write made through one call is visible to a
				// later read through another.
				return { state: mockCartState };
			}
			return {};
		} ),
		getContext: jest.fn( () => mockContext ),
		getServerState: jest.fn( () => ( { draftSeeds: mockDraftSeeds } ) ),
	} ),
	{ virtual: true }
);

describe( 'woocommerce/products store – product context derived state', () => {
	beforeEach( () => {
		mockRegisteredStore = null;
		mockContext = null;
		mockCartState = { draftItems: {} };
		mockDraftSeeds = undefined;
		mockProductsStateBase = {
			products: {},
			productVariations: {},
			productId: 0,
			variationId: null,
		};

		jest.isolateModules( () => require( '../products' ) );
		mockStoreState = getMockStoreState();

		// Hydrate products and variations after store is created.
		mockStoreState.products = { 42: mockProduct };
		mockStoreState.productVariations = { 99: mockVariation };
	} );

	it( 'has writable productId and variationId state', () => {
		mockStoreState.productId = 42;
		mockStoreState.variationId = 99;

		expect( mockStoreState.productId ).toBe( 42 );
		expect( mockStoreState.variationId ).toBe( 99 );
	} );

	describe( 'baseProductInContext', () => {
		it( 'returns the product when variationId is null', () => {
			mockStoreState.productId = 42;
			mockStoreState.variationId = null;

			expect( mockStoreState.baseProductInContext ).toBe( mockProduct );
		} );

		it( 'returns the product even when variationId is set', () => {
			mockStoreState.productId = 42;
			mockStoreState.variationId = 99;

			// product always returns the base product, never the variation.
			expect( mockStoreState.baseProductInContext ).toBe( mockProduct );
		} );

		it( 'returns null when product is not in the store', () => {
			mockStoreState.productId = 999;
			mockStoreState.variationId = null;

			expect( mockStoreState.baseProductInContext ).toBeNull();
		} );

		it( 'returns null when productId is 0', () => {
			expect( mockStoreState.baseProductInContext ).toBeNull();
		} );

		it( 'reads from block context when available', () => {
			mockStoreState.productId = 1;
			mockContext = { productId: 42 };

			expect( mockStoreState.baseProductInContext ).toBe( mockProduct );
		} );
	} );

	describe( 'productVariationInContext', () => {
		it( 'returns null when variationId is null (simple product)', () => {
			mockStoreState.productId = 42;
			mockStoreState.variationId = null;

			expect( mockStoreState.productVariationInContext ).toBeNull();
		} );

		it( 'returns null when variationId is null (variable product, no selection)', () => {
			mockStoreState.products[ 10 ] = {
				id: 10,
				type: 'variable',
			} as ProductResponseItem;
			mockStoreState.productId = 10;
			mockStoreState.variationId = null;

			expect( mockStoreState.productVariationInContext ).toBeNull();
		} );

		it( 'returns the variation when variationId is set', () => {
			mockStoreState.productId = 42;
			mockStoreState.variationId = 99;

			expect( mockStoreState.productVariationInContext ).toBe(
				mockVariation
			);
		} );

		it( 'returns null when variation is not in the store', () => {
			mockStoreState.productId = 42;
			mockStoreState.variationId = 999;

			expect( mockStoreState.productVariationInContext ).toBeNull();
		} );

		describe( 'family draft derivation (direction A)', () => {
			beforeEach( () => {
				mockStoreState.products[ 10 ] = familyVariableProduct;
				mockStoreState.productVariations[ 20 ] = variation20;
				mockStoreState.productVariations[ 21 ] = variation21;
				mockStoreState.productId = 10;
				mockStoreState.variationId = null;
			} );

			it( 'resolves the matching variation for a family draft carrying a resolvable attribute set, with no further call', () => {
				mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] = [
					{
						id: 10,
						quantity: 1,
						variation: [
							{ attribute: 'Color', value: 'red' },
							{ attribute: 'Logo', value: 'yes' },
						],
					} as DraftItem,
				];

				expect( mockStoreState.productVariationInContext ).toBe(
					variation21
				);
				expect( mockStoreState.productInContext ).toBe( variation21 );
			} );

			it( 'resolves null for a family draft carrying an unresolvable attribute set, exactly as an equivalent variationId write resolves today', () => {
				mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] = [
					{
						id: 10,
						quantity: 1,
						variation: [ { attribute: 'Color', value: 'green' } ],
					} as DraftItem,
				];

				expect( mockStoreState.productVariationInContext ).toBeNull();
				expect( mockStoreState.productInContext ).toBe(
					familyVariableProduct
				);
			} );

			it( 'resolves the variation via the id-direct rung for a family draft carrying a variation id but no attributes', () => {
				mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] = [
					{ id: 20, quantity: 1 } as DraftItem,
				];

				expect( mockStoreState.productVariationInContext ).toBe(
					variation20
				);
			} );

			it( 'falls back to variationId context/state resolution when no family draft exists in the collection (SSR/first-paint parity)', () => {
				expect(
					mockCartState.draftItems[ GLOBAL_DRAFT_KEY ]
				).toBeUndefined();

				mockStoreState.variationId = 20;

				expect( mockStoreState.productVariationInContext ).toBe(
					variation20
				);
			} );

			it( 'degrades to the variationId fallback instead of throwing when the woocommerce/cart namespace never registered state (products-only page)', () => {
				mockCartState = {} as typeof mockCartState;
				mockStoreState.variationId = 21;

				expect(
					() => mockStoreState.productVariationInContext
				).not.toThrow();
				expect( mockStoreState.productVariationInContext ).toBe(
					variation21
				);
			} );
		} );

		describe( 'setter (direction B)', () => {
			let warnSpy: jest.SpyInstance;
			let originalNodeEnv: string | undefined;

			beforeEach( () => {
				mockStoreState.products[ 10 ] = familyVariableProduct;
				mockStoreState.productVariations[ 20 ] = variation20;
				mockStoreState.productVariations[ 21 ] = variation21;
				mockStoreState.productId = 10;
				mockStoreState.variationId = null;

				originalNodeEnv = process.env.NODE_ENV;
				process.env.NODE_ENV = 'development';
				warnSpy = jest
					.spyOn( console, 'warn' )
					.mockImplementation( () => {} );
			} );

			afterEach( () => {
				warnSpy.mockRestore();
				process.env.NODE_ENV = originalNodeEnv;
			} );

			it( 'writes variation derived from base.variations[] and the migrated id, materializing on an untouched surface', () => {
				mockDraftSeeds = {
					[ GLOBAL_DRAFT_KEY ]: {
						10: { id: 10, quantity: 2 } as DraftItem,
					},
				};

				mockStoreState.productVariationInContext = variation21;

				expect( mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] ).toEqual(
					[
						{
							id: 21,
							quantity: 2,
							variation: [
								{ attribute: 'Color', value: 'red' },
								{ attribute: 'Logo', value: 'yes' },
							],
						},
					]
				);
				// The assignment "sticks": reading the accessor again, against
				// the same real serializer-shaped data (productVariations[21]
				// carries no attributes field of its own), reflects it.
				expect( mockStoreState.productVariationInContext ).toBe(
					variation21
				);
			} );

			it( 'files a partial selection at the parent id and warns naming the missing attribute(s) when an "any" attribute has no derivable value', () => {
				mockDraftSeeds = {
					[ GLOBAL_DRAFT_KEY ]: {
						10: { id: 10, quantity: 3 } as DraftItem,
					},
				};

				mockStoreState.productVariationInContext = variation20;

				expect( warnSpy ).toHaveBeenCalledWith(
					expect.stringContaining( 'Logo' )
				);
				expect( mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] ).toEqual(
					[
						{
							id: 10,
							quantity: 3,
							variation: [
								{ attribute: 'Color', value: 'blue' },
							],
						},
					]
				);
				// Files at the parent, so it renders unresolved rather than
				// as a falsely-resolved variation.
				expect( mockStoreState.productVariationInContext ).toBeNull();
			} );

			it( 'preserves a previously recorded "any" value from the existing family draft instead of warning', () => {
				mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] = [
					{
						id: 20,
						quantity: 4,
						variation: [
							{ attribute: 'Color', value: 'blue' },
							{ attribute: 'Logo', value: 'existing' },
						],
					} as DraftItem,
				];

				mockStoreState.productVariationInContext = variation20;

				expect( warnSpy ).not.toHaveBeenCalled();
				expect( mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] ).toEqual(
					[
						{
							id: 20,
							quantity: 4,
							variation: [
								{ attribute: 'Color', value: 'blue' },
								{ attribute: 'Logo', value: 'existing' },
							],
						},
					]
				);
			} );

			it( 'clears the selection when assigned null: variation becomes [], id migrates to the parent', () => {
				mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] = [
					{
						id: 21,
						quantity: 5,
						variation: [
							{ attribute: 'Color', value: 'red' },
							{ attribute: 'Logo', value: 'yes' },
						],
					} as DraftItem,
				];

				mockStoreState.productVariationInContext = null;

				expect( mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] ).toEqual(
					[ { id: 10, quantity: 5, variation: [] } ]
				);
			} );

			it( 'leaves state unchanged and warns when assigning a foreign variation', () => {
				mockStoreState.productVariationInContext = foreignVariation;

				expect( warnSpy ).toHaveBeenCalled();
				expect(
					mockCartState.draftItems[ GLOBAL_DRAFT_KEY ]
				).toBeUndefined();
			} );

			it( 'leaves state unchanged and warns when assigning on a simple/grouped in-context product', () => {
				mockStoreState.productId = 42; // mockProduct carries no `type` (not variable).
				mockStoreState.variationId = null;

				mockStoreState.productVariationInContext = variation21;

				expect( warnSpy ).toHaveBeenCalled();
				expect(
					mockCartState.draftItems[ GLOBAL_DRAFT_KEY ]
				).toBeUndefined();
			} );
		} );
	} );

	describe( 'findProduct', () => {
		it( 'returns null when product is not in the store', () => {
			const result = mockStoreState.findProduct( {
				id: 999,
			} );

			expect( result ).toBeNull();
		} );

		it( 'returns the product itself for a simple product', () => {
			const simpleProduct = {
				id: 1,
				type: 'simple',
			} as ProductResponseItem;
			mockStoreState.products[ 1 ] = simpleProduct;

			const result = mockStoreState.findProduct( {
				id: 1,
			} );

			expect( result ).toBe( simpleProduct );
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
			mockStoreState.products[ 1 ] = variableProduct;
			mockStoreState.productVariations[ 10 ] = populatedVariation;

			const result = mockStoreState.findProduct( {
				id: 1,
				selectedAttributes: [ { attribute: 'Color', value: 'red' } ],
			} );

			expect( result ).toBe( populatedVariation );
		} );

		it( 'returns null when attributes match but the variation is not populated', () => {
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
			mockStoreState.products[ 1 ] = variableProduct;
			// productVariations intentionally empty.

			const result = mockStoreState.findProduct( {
				id: 1,
				selectedAttributes: [ { attribute: 'Color', value: 'red' } ],
			} );

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
			mockStoreState.products[ 1 ] = variableProduct;

			expect( mockStoreState.findProduct( { id: 1 } ) ).toBe(
				variableProduct
			);
			expect(
				mockStoreState.findProduct( {
					id: 1,
					selectedAttributes: [],
				} )
			).toBe( variableProduct );
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
			mockStoreState.products[ 1 ] = variableProduct;
			mockStoreState.productVariations[ 10 ] = {
				id: 10,
			} as ProductResponseItem;

			const result = mockStoreState.findProduct( {
				id: 1,
				selectedAttributes: [ { attribute: 'Color', value: 'blue' } ],
			} );

			expect( result ).toBeNull();
		} );

		it( 'returns the variation directly when given a variation ID', () => {
			const variation = {
				id: 50,
				name: 'Direct Variation',
			} as ProductResponseItem;
			mockStoreState.productVariations[ 50 ] = variation;

			const result = mockStoreState.findProduct( {
				id: 50,
			} );

			expect( result ).toBe( variation );
		} );

		it( 'returns the variation directly and ignores selectedAttributes when given a variation ID', () => {
			const variation = {
				id: 50,
				name: 'Direct Variation',
			} as ProductResponseItem;
			mockStoreState.productVariations[ 50 ] = variation;

			const result = mockStoreState.findProduct( {
				id: 50,
				selectedAttributes: [ { attribute: 'Color', value: 'blue' } ],
			} );

			expect( result ).toBe( variation );
		} );

		it( 'prefers variation lookup over product lookup when ID exists in both', () => {
			const product = {
				id: 50,
				type: 'simple',
				name: 'Product 50',
			} as ProductResponseItem;
			const variation = {
				id: 50,
				name: 'Variation 50',
			} as ProductResponseItem;
			mockStoreState.products[ 50 ] = product;
			mockStoreState.productVariations[ 50 ] = variation;

			const result = mockStoreState.findProduct( {
				id: 50,
			} );

			expect( result ).toBe( variation );
		} );

		describe( 'attribute matching (variable products)', () => {
			it( 'matches with attribute prefix in selected attributes', () => {
				const variableProduct = {
					id: 3,
					type: 'variable',
					variations: [
						{
							id: 301,
							attributes: [
								{ name: 'Color', value: 'Blue' },
								{ name: 'Size', value: 'Small' },
							],
						},
						{
							id: 302,
							attributes: [
								{ name: 'Color', value: 'Blue' },
								{ name: 'Size', value: 'Large' },
							],
						},
					],
				} as unknown as ProductResponseItem;
				const populatedVariation301 = {
					id: 301,
					name: 'Blue Small',
				} as ProductResponseItem;
				const populatedVariation302 = {
					id: 302,
					name: 'Blue Large',
				} as ProductResponseItem;
				mockStoreState.products[ 3 ] = variableProduct;
				mockStoreState.productVariations[ 301 ] = populatedVariation301;
				mockStoreState.productVariations[ 302 ] = populatedVariation302;

				const result = mockStoreState.findProduct( {
					id: 3,
					selectedAttributes: [
						{ attribute: 'attribute_pa_color', value: 'Blue' },
						{ attribute: 'attribute_pa_size', value: 'Small' },
					],
				} );

				expect( result ).toBe( populatedVariation301 );
			} );

			describe( 'multi-word attribute names', () => {
				it( 'matches when selected attributes use hyphenated slugs', () => {
					const variableProduct = {
						id: 3,
						type: 'variable',
						variations: [
							{
								id: 301,
								attributes: [
									{ name: 'Color', value: 'Blue' },
									{ name: 'numeric size', value: '42' },
								],
							},
							{
								id: 302,
								attributes: [
									{ name: 'Color', value: 'Red' },
									{ name: 'numeric size', value: '44' },
								],
							},
						],
					} as unknown as ProductResponseItem;
					const populatedVariation = {
						id: 301,
						name: 'Blue 42',
					} as ProductResponseItem;
					mockStoreState.products[ 3 ] = variableProduct;
					mockStoreState.productVariations[ 301 ] =
						populatedVariation;

					const result = mockStoreState.findProduct( {
						id: 3,
						selectedAttributes: [
							{
								attribute: 'attribute_pa_color',
								value: 'Blue',
							},
							{
								attribute: 'attribute_pa_numeric-size',
								value: '42',
							},
						],
					} );

					expect( result ).toBe( populatedVariation );
				} );
			} );

			describe( 'Any attribute handling', () => {
				it( 'matches variation with "Any" attribute when value is selected', () => {
					const variableProduct = {
						id: 2,
						type: 'variable',
						variations: [
							{
								id: 201,
								attributes: [
									{ name: 'Color', value: null },
									{ name: 'Size', value: 'Small' },
								],
							},
							{
								id: 202,
								attributes: [
									{ name: 'Color', value: 'Blue' },
									{ name: 'Size', value: null },
								],
							},
						],
					} as unknown as ProductResponseItem;
					const populatedVariation = {
						id: 201,
						name: 'Any Color Small',
					} as ProductResponseItem;
					mockStoreState.products[ 2 ] = variableProduct;
					mockStoreState.productVariations[ 201 ] =
						populatedVariation;

					const result = mockStoreState.findProduct( {
						id: 2,
						selectedAttributes: [
							{ attribute: 'Color', value: 'Red' },
							{ attribute: 'Size', value: 'Small' },
						],
					} );

					expect( result ).toBe( populatedVariation );
				} );

				it( 'does not match "Any" attribute when selected value is null', () => {
					const variableProduct = {
						id: 2,
						type: 'variable',
						variations: [
							{
								id: 201,
								attributes: [
									{ name: 'Color', value: null },
									{ name: 'Size', value: 'Small' },
								],
							},
						],
					} as unknown as ProductResponseItem;
					mockStoreState.products[ 2 ] = variableProduct;

					const result = mockStoreState.findProduct( {
						id: 2,
						selectedAttributes: [
							{
								attribute: 'Color',
								value: null as unknown as string,
							},
							{ attribute: 'Size', value: 'Small' },
						],
					} );

					expect( result ).toBeNull();
				} );

				it( 'does not match "Any" attribute when attribute is not selected', () => {
					const variableProduct = {
						id: 2,
						type: 'variable',
						variations: [
							{
								id: 201,
								attributes: [
									{ name: 'Color', value: null },
									{ name: 'Size', value: 'Small' },
								],
							},
						],
					} as unknown as ProductResponseItem;
					mockStoreState.products[ 2 ] = variableProduct;

					const result = mockStoreState.findProduct( {
						id: 2,
						selectedAttributes: [
							{ attribute: 'Size', value: 'Small' },
						],
					} );

					expect( result ).toBeNull();
				} );
			} );
		} );
	} );

	describe( 'productInContext', () => {
		it( 'returns product when variationId is null (simple product path)', () => {
			mockStoreState.productId = 42;
			mockStoreState.variationId = null;

			expect( mockStoreState.productInContext ).toBe( mockProduct );
		} );

		it( 'returns productVariationInContext when variationId is set and populated', () => {
			mockStoreState.productId = 42;
			mockStoreState.variationId = 99;

			expect( mockStoreState.productInContext ).toBe( mockVariation );
		} );

		it( 'falls back to product when variation is missing from productVariations', () => {
			mockStoreState.productId = 42;
			mockStoreState.variationId = 123;

			expect( mockStoreState.productInContext ).toBe( mockProduct );
		} );

		it( 'returns null when neither product nor variation resolves', () => {
			mockStoreState.productId = 0;
			mockStoreState.variationId = null;

			expect( mockStoreState.productInContext ).toBeNull();
		} );

		it( 'honors local context over state IDs', () => {
			mockStoreState.productId = 1;
			mockStoreState.variationId = null;
			mockContext = { productId: 42, variationId: 99 };

			expect( mockStoreState.productInContext ).toBe( mockVariation );
		} );
	} );

	describe( 'Product block path (context without variationId)', () => {
		it( 'baseProductInContext reads productId from context', () => {
			mockContext = { productId: 42 };
			mockStoreState.variationId = null;

			expect( mockStoreState.baseProductInContext ).toBe( mockProduct );
		} );

		it( 'productVariationInContext reads variationId from context when available', () => {
			mockContext = { productId: 42, variationId: 99 };
			mockStoreState.variationId = null;

			expect( mockStoreState.productVariationInContext ).toBe(
				mockVariation
			);
		} );

		it( 'productVariationInContext falls back to state when context exists but does not define variationId', () => {
			mockContext = { productId: 42 };
			mockStoreState.variationId = 99;

			expect( mockStoreState.productVariationInContext ).toBe(
				mockVariation
			);
		} );

		it( 'productVariationInContext does not fall back to state when context explicitly sets variationId to null', () => {
			mockContext = { productId: 42, variationId: null };
			mockStoreState.variationId = 99;

			expect( mockStoreState.productVariationInContext ).toBe( null );
		} );

		it( 'productVariationInContext falls back to state when context does not exist', () => {
			mockStoreState.variationId = 99;

			expect( mockStoreState.productVariationInContext ).toBe(
				mockVariation
			);
		} );

		it( 'productVariationInContext returns null when both context and state variationId are null', () => {
			mockStoreState.variationId = null;

			expect( mockStoreState.productVariationInContext ).toBeNull();
		} );
	} );
} );
