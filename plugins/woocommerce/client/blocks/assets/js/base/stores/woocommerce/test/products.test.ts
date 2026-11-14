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
			return {};
		} ),
		getContext: jest.fn( () => mockContext ),
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
