/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { ProductsStore } from '../products';

let mockRegisteredStore: {
	state: ProductsStore[ 'state' ];
} | null = null;

let mockStoreState: ProductsStore[ 'state' ];

let mockContext: { productId?: number; variationId?: number | null } | null =
	null;

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

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: jest.fn( ( namespace, definition ) => {
			if ( namespace === 'woocommerce/products' ) {
				// Simulate server-hydrated state merged with client definition.
				// Getters from definition.state are preserved, and productId /
				// variationId are added as plain values (simulating
				// wp_interactivity_state hydration).
				const stateBase = {
					products: {} as Record< number, ProductResponseItem >,
					productVariations: {} as Record<
						number,
						ProductResponseItem
					>,
					productId: 0,
					variationId: null as number | null,
				};
				const descriptors = Object.getOwnPropertyDescriptors(
					definition.state
				);
				Object.defineProperties( stateBase, descriptors );

				mockRegisteredStore = {
					state: stateBase as ProductsStore[ 'state' ],
				};
				return mockRegisteredStore;
			}
			// The products store reads NOTHING from the cart store (T12:
			// one-directional coupling, cart → products only). Any other
			// namespace read returns an empty store.
			return {};
		} ),
		// The products store reads ONLY its own `woocommerce/products` context
		// (T12). It never reads the bare `woocommerce` context or the cart store.
		getContext: jest.fn( ( namespace?: string ) =>
			namespace === 'woocommerce/products' ? mockContext : null
		),
	} ),
	{ virtual: true }
);

describe( 'woocommerce/products store – product context derived state', () => {
	beforeEach( () => {
		mockRegisteredStore = null;
		mockContext = null;

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

	describe( 'mainProductInContext', () => {
		it( 'returns the product when variationId is null', () => {
			mockStoreState.productId = 42;
			mockStoreState.variationId = null;

			expect( mockStoreState.mainProductInContext ).toBe( mockProduct );
		} );

		it( 'returns the product even when variationId is set', () => {
			mockStoreState.productId = 42;
			mockStoreState.variationId = 99;

			// product always returns the main product, never the variation.
			expect( mockStoreState.mainProductInContext ).toBe( mockProduct );
		} );

		it( 'returns null when product is not in the store', () => {
			mockStoreState.productId = 999;
			mockStoreState.variationId = null;

			expect( mockStoreState.mainProductInContext ).toBeNull();
		} );

		it( 'returns null when productId is 0', () => {
			expect( mockStoreState.mainProductInContext ).toBeNull();
		} );

		it( 'reads from block context when available', () => {
			mockStoreState.productId = 1;
			mockContext = { productId: 42 };

			expect( mockStoreState.mainProductInContext ).toBe( mockProduct );
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
	} );

	// T12: `productVariationInContext` derives PURELY from this store's own
	// `variationId` (context first, else global state), else null. The products
	// store reads NOTHING from the cart store — a full cart draft selection does
	// NOT drive the selected variation (coupling is one-directional: cart →
	// products only). A purchase surface writes the resolved `variationId` into
	// the products context (the "double write"); this getter only reads it.
	describe( 'productVariationInContext – own-namespace only (T12)', () => {
		const variableProduct = {
			id: 10,
			type: 'variable',
			variations: [
				{ id: 77, attributes: [ { name: 'Color', value: 'red' } ] },
			],
		} as unknown as ProductResponseItem;
		const redVariation = {
			id: 77,
			name: 'Red',
		} as ProductResponseItem;

		beforeEach( () => {
			mockStoreState.products[ 10 ] = variableProduct;
			mockStoreState.productVariations[ 77 ] = redVariation;
			mockStoreState.productId = 10;
			mockStoreState.variationId = null;
		} );

		it( 'returns the variation when variationId is set in the products context', () => {
			mockContext = { productId: 10, variationId: 77 };

			expect( mockStoreState.productVariationInContext ).toBe(
				redVariation
			);
		} );

		it( 'returns the variation when variationId is set in global state (no context)', () => {
			mockContext = null;
			mockStoreState.variationId = 77;

			expect( mockStoreState.productVariationInContext ).toBe(
				redVariation
			);
		} );

		it( 'returns null when no variationId is set anywhere, even for a variable product', () => {
			mockContext = { productId: 10, variationId: null };

			expect( mockStoreState.productVariationInContext ).toBeNull();
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

			it( 'value comparison stays EXACT on the products-store path (no case folding)', () => {
				// The structural matcher (`variationMatchesSelection`) compares
				// VALUES exactly: on this path both sides are slugs, mirroring
				// the server's `find_matching_product_variation`. Case-only
				// normalization exists ONLY at the shopper-lists boundary
				// (`findListItem`), where rows carry display names — it must
				// never leak into variation resolution, or client and server
				// would disagree on which variation a selection resolves to.
				const variableProduct = {
					id: 3,
					type: 'variable',
					variations: [
						{
							id: 301,
							attributes: [ { name: 'Color', value: 'blue' } ],
						},
					],
				} as unknown as ProductResponseItem;
				const populatedVariation301 = {
					id: 301,
					name: 'Blue',
				} as ProductResponseItem;
				mockStoreState.products[ 3 ] = variableProduct;
				mockStoreState.productVariations[ 301 ] = populatedVariation301;

				// Case-only difference ("Blue" vs stored "blue") → NO match.
				expect(
					mockStoreState.findProduct( {
						id: 3,
						selectedAttributes: [
							{ attribute: 'Color', value: 'Blue' },
						],
					} )
				).toBeNull();

				// Exact value → match.
				expect(
					mockStoreState.findProduct( {
						id: 3,
						selectedAttributes: [
							{ attribute: 'Color', value: 'blue' },
						],
					} )
				).toBe( populatedVariation301 );
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

				// Server parity: an "Any" variation attribute never causes a
				// mismatch, even when the corresponding selected value is
				// null. `find_matching_product_variation` short-circuits on
				// `'' === $attribute_value` before ever consulting the
				// selection, so the variation still matches on its non-"Any"
				// attributes alone.
				it( 'matches "Any" attribute even when the selected value is null', () => {
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
							{
								attribute: 'Color',
								value: null as unknown as string,
							},
							{ attribute: 'Size', value: 'Small' },
						],
					} );

					expect( result ).toBe( populatedVariation );
				} );

				// Server parity: partial selections resolve. When the shopper
				// omits an "Any" attribute entirely, the server still matches
				// the variation on its remaining (non-"Any") attributes.
				it( 'matches "Any" attribute even when it is not selected (partial selection)', () => {
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
							{ attribute: 'Size', value: 'Small' },
						],
					} );

					expect( result ).toBe( populatedVariation );
				} );
			} );

			// Adversarial parity table: each case asserts that `findProduct`
			// resolves the SAME variation the server's
			// `find_matching_product_variation` would (WC_Product_Data_Store_CPT).
			// Variations are always listed in `menu_order ASC, ID ASC` order,
			// exactly as the Store API delivers them via `get_visible_children()`,
			// so the first matching variation in array order is the server winner.
			describe( 'deterministic resolution mirrors the server', () => {
				type Attr = { name: string; value: string | null };
				type Case = {
					title: string;
					// Variations in server order (menu_order ASC, ID ASC).
					variations: Array< { id: number; attributes: Attr[] } >;
					selection: Array< { attribute: string; value: string } >;
					// Expected resolved variation ID, or null for no match.
					expected: number | null;
				};

				const cases: Case[] = [
					{
						title: 'overlapping "any": first variation in order wins the tie (A = size S / color any, B = size any / color red; select S + red → A)',
						variations: [
							{
								id: 201,
								attributes: [
									{ name: 'Size', value: 'S' },
									{ name: 'Color', value: null },
								],
							},
							{
								id: 202,
								attributes: [
									{ name: 'Size', value: null },
									{ name: 'Color', value: 'red' },
								],
							},
						],
						selection: [
							{ attribute: 'Size', value: 'S' },
							{ attribute: 'Color', value: 'red' },
						],
						expected: 201,
					},
					{
						title: 'order dependence: same two variations but B is listed first (menu_order) → B wins',
						variations: [
							{
								id: 202,
								attributes: [
									{ name: 'Size', value: null },
									{ name: 'Color', value: 'red' },
								],
							},
							{
								id: 201,
								attributes: [
									{ name: 'Size', value: 'S' },
									{ name: 'Color', value: null },
								],
							},
						],
						selection: [
							{ attribute: 'Size', value: 'S' },
							{ attribute: 'Color', value: 'red' },
						],
						expected: 202,
					},
					{
						title: 'catch-all "any/any" listed first shadows an exact match listed later',
						variations: [
							{
								id: 300,
								attributes: [
									{ name: 'Size', value: null },
									{ name: 'Color', value: null },
								],
							},
							{
								id: 301,
								attributes: [
									{ name: 'Size', value: 'S' },
									{ name: 'Color', value: 'red' },
								],
							},
						],
						selection: [
							{ attribute: 'Size', value: 'S' },
							{ attribute: 'Color', value: 'red' },
						],
						expected: 300,
					},
					{
						title: 'exact match: fully-specified variation among specific siblings',
						variations: [
							{
								id: 400,
								attributes: [
									{ name: 'Size', value: 'S' },
									{ name: 'Color', value: 'blue' },
								],
							},
							{
								id: 401,
								attributes: [
									{ name: 'Size', value: 'M' },
									{ name: 'Color', value: 'red' },
								],
							},
						],
						selection: [
							{ attribute: 'Size', value: 'M' },
							{ attribute: 'Color', value: 'red' },
						],
						expected: 401,
					},
					{
						title: 'partial selection: omitted attribute is "any" on the variation → matches',
						variations: [
							{
								id: 500,
								attributes: [
									{ name: 'Size', value: 'S' },
									{ name: 'Color', value: null },
								],
							},
						],
						selection: [ { attribute: 'Size', value: 'S' } ],
						expected: 500,
					},
					{
						title: 'partial selection: omitted attribute is concrete on every variation → no match',
						variations: [
							{
								id: 600,
								attributes: [
									{ name: 'Size', value: 'S' },
									{ name: 'Color', value: 'red' },
								],
							},
							{
								id: 601,
								attributes: [
									{ name: 'Size', value: 'S' },
									{ name: 'Color', value: 'blue' },
								],
							},
						],
						selection: [ { attribute: 'Size', value: 'S' } ],
						expected: null,
					},
					{
						title: 'no match: selected value does not exist on any variation',
						variations: [
							{
								id: 700,
								attributes: [
									{ name: 'Size', value: 'S' },
									{ name: 'Color', value: 'red' },
								],
							},
							{
								id: 701,
								attributes: [
									{ name: 'Size', value: 'M' },
									{ name: 'Color', value: 'red' },
								],
							},
						],
						selection: [
							{ attribute: 'Size', value: 'XL' },
							{ attribute: 'Color', value: 'red' },
						],
						expected: null,
					},
					{
						title: 'extra selected attribute the variation does not define is ignored (server loops variation attrs only)',
						variations: [
							{
								id: 800,
								attributes: [ { name: 'Size', value: 'S' } ],
							},
						],
						selection: [
							{ attribute: 'Size', value: 'S' },
							{ attribute: 'Material', value: 'cotton' },
						],
						expected: 800,
					},
					{
						title: 'all-"any" variation matches any concrete selection',
						variations: [
							{
								id: 900,
								attributes: [
									{ name: 'Size', value: null },
									{ name: 'Color', value: null },
								],
							},
						],
						selection: [
							{ attribute: 'Size', value: 'S' },
							{ attribute: 'Color', value: 'green' },
						],
						expected: 900,
					},
					{
						title: 'first exact match wins over a later, equally-exact duplicate',
						variations: [
							{
								id: 1000,
								attributes: [ { name: 'Size', value: 'S' } ],
							},
							{
								id: 1001,
								attributes: [ { name: 'Size', value: 'S' } ],
							},
						],
						selection: [ { attribute: 'Size', value: 'S' } ],
						expected: 1000,
					},
				];

				it.each( cases )(
					'$title',
					( { variations, selection, expected } ) => {
						const productId = 7;
						mockStoreState.products[ productId ] = {
							id: productId,
							type: 'variable',
							variations,
						} as unknown as ProductResponseItem;

						// Populate every referenced variation so a successful
						// match returns the variation record (not null due to a
						// missing record).
						variations.forEach( ( v ) => {
							mockStoreState.productVariations[ v.id ] = {
								id: v.id,
							} as ProductResponseItem;
						} );

						const result = mockStoreState.findProduct( {
							id: productId,
							selectedAttributes: selection,
						} );

						if ( expected === null ) {
							expect( result ).toBeNull();
						} else {
							expect( result ).not.toBeNull();
							expect( result?.id ).toBe( expected );
						}
					}
				);
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

	// `resolvedProductInContext` is `productInContext` restricted to a
	// selection RESOLVED to one concrete product — a selected variation, or a
	// main product that needs no options (`has_options === false`). It is the
	// type-invariant read the wishlist button binds to: no `type === 'variable'`
	// sniff, the polymorphism resolves once here from the server-computed
	// `has_options` field.
	describe( 'resolvedProductInContext', () => {
		it( 'returns the variation when one is selected', () => {
			mockStoreState.productId = 42;
			mockStoreState.variationId = 99;

			expect( mockStoreState.resolvedProductInContext ).toBe(
				mockVariation
			);
		} );

		it( 'returns the main product when it has no options (has_options false)', () => {
			// A no-options product (simple/grouped/external): resolved
			// immediately, no variation to pick.
			mockStoreState.products[ 50 ] = {
				id: 50,
				type: 'simple',
				has_options: false,
			} as ProductResponseItem;
			mockStoreState.productId = 50;
			mockStoreState.variationId = null;

			expect( mockStoreState.resolvedProductInContext?.id ).toBe( 50 );
		} );

		it( 'returns null for a product with options and no variation selected', () => {
			// A variable product before the shopper picks a variation: NOT yet
			// resolved → null. Detected from `has_options`, never `type`.
			mockStoreState.products[ 60 ] = {
				id: 60,
				type: 'variable',
				has_options: true,
			} as ProductResponseItem;
			mockStoreState.productId = 60;
			mockStoreState.variationId = null;

			expect( mockStoreState.resolvedProductInContext ).toBeNull();
		} );

		it( 'returns the variation for a product WITH options once one is selected', () => {
			// Same has_options=true product, but now a variation is picked — the
			// selected variation resolves it regardless of has_options.
			mockStoreState.products[ 60 ] = {
				id: 60,
				type: 'variable',
				has_options: true,
			} as ProductResponseItem;
			mockStoreState.productId = 60;
			mockStoreState.variationId = 99;

			expect( mockStoreState.resolvedProductInContext ).toBe(
				mockVariation
			);
		} );

		it( 'returns null when neither product nor variation resolves', () => {
			mockStoreState.productId = 0;
			mockStoreState.variationId = null;

			expect( mockStoreState.resolvedProductInContext ).toBeNull();
		} );
	} );

	describe( 'Product block path (context without variationId)', () => {
		it( 'mainProductInContext reads productId from context', () => {
			mockContext = { productId: 42 };
			mockStoreState.variationId = null;

			expect( mockStoreState.mainProductInContext ).toBe( mockProduct );
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
