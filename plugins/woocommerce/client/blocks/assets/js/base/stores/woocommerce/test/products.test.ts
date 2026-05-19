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

let mockContext: { productId?: number; variationId?: number | null } | null =
	null;

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

		// Hydrate products and variations after store is created.
		mockRegisteredStore!.state.products = { 42: mockProduct };
		mockRegisteredStore!.state.productVariations = { 99: mockVariation };
	} );

	it( 'has writable productId and variationId state', () => {
		expect( mockRegisteredStore ).not.toBeNull();

		mockRegisteredStore!.state.productId = 42;
		mockRegisteredStore!.state.variationId = 99;

		expect( mockRegisteredStore!.state.productId ).toBe( 42 );
		expect( mockRegisteredStore!.state.variationId ).toBe( 99 );
	} );

	describe( 'mainProductInContext', () => {
		it( 'returns the product when variationId is null', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 42;
			mockRegisteredStore!.state.variationId = null;

			expect( mockRegisteredStore!.state.mainProductInContext ).toBe(
				mockProduct
			);
		} );

		it( 'returns the product even when variationId is set', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 42;
			mockRegisteredStore!.state.variationId = 99;

			// product always returns the main product, never the variation.
			expect( mockRegisteredStore!.state.mainProductInContext ).toBe(
				mockProduct
			);
		} );

		it( 'returns null when product is not in the store', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 999;
			mockRegisteredStore!.state.variationId = null;

			expect(
				mockRegisteredStore!.state.mainProductInContext
			).toBeNull();
		} );

		it( 'returns null when productId is 0', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			expect(
				mockRegisteredStore!.state.mainProductInContext
			).toBeNull();
		} );

		it( 'reads from block context when available', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 1;
			mockContext = { productId: 42 };

			expect( mockRegisteredStore!.state.mainProductInContext ).toBe(
				mockProduct
			);
		} );
	} );

	describe( 'productVariationInContext', () => {
		it( 'returns null when variationId is null (simple product)', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 42;
			mockRegisteredStore!.state.variationId = null;

			expect(
				mockRegisteredStore!.state.productVariationInContext
			).toBeNull();
		} );

		it( 'returns null when variationId is null (variable product, no selection)', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.products[ 10 ] = {
				id: 10,
				type: 'variable',
			} as ProductResponseItem;
			mockRegisteredStore!.state.productId = 10;
			mockRegisteredStore!.state.variationId = null;

			expect(
				mockRegisteredStore!.state.productVariationInContext
			).toBeNull();
		} );

		it( 'returns the variation when variationId is set', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 42;
			mockRegisteredStore!.state.variationId = 99;

			expect( mockRegisteredStore!.state.productVariationInContext ).toBe(
				mockVariation
			);
		} );

		it( 'returns null when variation is not in the store', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 42;
			mockRegisteredStore!.state.variationId = 999;

			expect(
				mockRegisteredStore!.state.productVariationInContext
			).toBeNull();
		} );
	} );

	describe( 'findProduct', () => {
		it( 'returns null when product is not in the store', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			const result = mockRegisteredStore!.state.findProduct( {
				id: 999,
			} );

			expect( result ).toBeNull();
		} );

		it( 'returns the product itself for a simple product', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			const simpleProduct = {
				id: 1,
				type: 'simple',
			} as ProductResponseItem;
			mockRegisteredStore!.state.products[ 1 ] = simpleProduct;

			const result = mockRegisteredStore!.state.findProduct( {
				id: 1,
			} );

			expect( result ).toBe( simpleProduct );
		} );

		it( 'returns the matched variation when selectedAttributes match and the variation is populated', () => {
			expect( mockRegisteredStore ).not.toBeNull();

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
			mockRegisteredStore!.state.products[ 1 ] = variableProduct;
			mockRegisteredStore!.state.productVariations[ 10 ] =
				populatedVariation;

			const result = mockRegisteredStore!.state.findProduct( {
				id: 1,
				selectedAttributes: [ { attribute: 'Color', value: 'red' } ],
			} );

			expect( result ).toBe( populatedVariation );
		} );

		it( 'returns null when attributes match but the variation is not populated', () => {
			expect( mockRegisteredStore ).not.toBeNull();

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
			mockRegisteredStore!.state.products[ 1 ] = variableProduct;
			// productVariations intentionally empty.

			const result = mockRegisteredStore!.state.findProduct( {
				id: 1,
				selectedAttributes: [ { attribute: 'Color', value: 'red' } ],
			} );

			expect( result ).toBeNull();
		} );

		it( 'returns the parent product when the product is variable and no attributes are selected', () => {
			expect( mockRegisteredStore ).not.toBeNull();

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
			mockRegisteredStore!.state.products[ 1 ] = variableProduct;

			expect( mockRegisteredStore!.state.findProduct( { id: 1 } ) ).toBe(
				variableProduct
			);
			expect(
				mockRegisteredStore!.state.findProduct( {
					id: 1,
					selectedAttributes: [],
				} )
			).toBe( variableProduct );
		} );

		it( 'returns null when attributes do not match any variation', () => {
			expect( mockRegisteredStore ).not.toBeNull();

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
			mockRegisteredStore!.state.products[ 1 ] = variableProduct;
			mockRegisteredStore!.state.productVariations[ 10 ] = {
				id: 10,
			} as ProductResponseItem;

			const result = mockRegisteredStore!.state.findProduct( {
				id: 1,
				selectedAttributes: [ { attribute: 'Color', value: 'blue' } ],
			} );

			expect( result ).toBeNull();
		} );

		it( 'returns the variation directly when given a variation ID', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			const variation = {
				id: 50,
				name: 'Direct Variation',
			} as ProductResponseItem;
			mockRegisteredStore!.state.productVariations[ 50 ] = variation;

			const result = mockRegisteredStore!.state.findProduct( {
				id: 50,
			} );

			expect( result ).toBe( variation );
		} );

		it( 'returns the variation directly and ignores selectedAttributes when given a variation ID', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			const variation = {
				id: 50,
				name: 'Direct Variation',
			} as ProductResponseItem;
			mockRegisteredStore!.state.productVariations[ 50 ] = variation;

			const result = mockRegisteredStore!.state.findProduct( {
				id: 50,
				selectedAttributes: [ { attribute: 'Color', value: 'blue' } ],
			} );

			expect( result ).toBe( variation );
		} );

		it( 'prefers variation lookup over product lookup when ID exists in both', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			const product = {
				id: 50,
				type: 'simple',
				name: 'Product 50',
			} as ProductResponseItem;
			const variation = {
				id: 50,
				name: 'Variation 50',
			} as ProductResponseItem;
			mockRegisteredStore!.state.products[ 50 ] = product;
			mockRegisteredStore!.state.productVariations[ 50 ] = variation;

			const result = mockRegisteredStore!.state.findProduct( {
				id: 50,
			} );

			expect( result ).toBe( variation );
		} );

		describe( 'attribute matching (variable products)', () => {
			it( 'matches with attribute prefix in selected attributes', () => {
				expect( mockRegisteredStore ).not.toBeNull();

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
				mockRegisteredStore!.state.products[ 3 ] = variableProduct;
				mockRegisteredStore!.state.productVariations[ 301 ] =
					populatedVariation301;
				mockRegisteredStore!.state.productVariations[ 302 ] =
					populatedVariation302;

				const result = mockRegisteredStore!.state.findProduct( {
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
					expect( mockRegisteredStore ).not.toBeNull();

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
					mockRegisteredStore!.state.products[ 3 ] = variableProduct;
					mockRegisteredStore!.state.productVariations[ 301 ] =
						populatedVariation;

					const result = mockRegisteredStore!.state.findProduct( {
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
					expect( mockRegisteredStore ).not.toBeNull();

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
					mockRegisteredStore!.state.products[ 2 ] = variableProduct;
					mockRegisteredStore!.state.productVariations[ 201 ] =
						populatedVariation;

					const result = mockRegisteredStore!.state.findProduct( {
						id: 2,
						selectedAttributes: [
							{ attribute: 'Color', value: 'Red' },
							{ attribute: 'Size', value: 'Small' },
						],
					} );

					expect( result ).toBe( populatedVariation );
				} );

				it( 'does not match "Any" attribute when selected value is null', () => {
					expect( mockRegisteredStore ).not.toBeNull();

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
					mockRegisteredStore!.state.products[ 2 ] = variableProduct;

					const result = mockRegisteredStore!.state.findProduct( {
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
					expect( mockRegisteredStore ).not.toBeNull();

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
					mockRegisteredStore!.state.products[ 2 ] = variableProduct;

					const result = mockRegisteredStore!.state.findProduct( {
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
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 42;
			mockRegisteredStore!.state.variationId = null;

			expect( mockRegisteredStore!.state.productInContext ).toBe(
				mockProduct
			);
		} );

		it( 'returns productVariationInContext when variationId is set and populated', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 42;
			mockRegisteredStore!.state.variationId = 99;

			expect( mockRegisteredStore!.state.productInContext ).toBe(
				mockVariation
			);
		} );

		it( 'falls back to product when variation is missing from productVariations', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 42;
			mockRegisteredStore!.state.variationId = 123;

			expect( mockRegisteredStore!.state.productInContext ).toBe(
				mockProduct
			);
		} );

		it( 'returns null when neither product nor variation resolves', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 0;
			mockRegisteredStore!.state.variationId = null;

			expect( mockRegisteredStore!.state.productInContext ).toBeNull();
		} );

		it( 'honors local context over state IDs', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 1;
			mockRegisteredStore!.state.variationId = null;
			mockContext = { productId: 42, variationId: 99 };

			expect( mockRegisteredStore!.state.productInContext ).toBe(
				mockVariation
			);
		} );
	} );

	describe( 'Product block path (context without variationId)', () => {
		it( 'mainProductInContext reads productId from context', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockContext = { productId: 42 };
			mockRegisteredStore!.state.variationId = null;

			expect( mockRegisteredStore!.state.mainProductInContext ).toBe(
				mockProduct
			);
		} );

		it( 'productVariationInContext reads variationId from context when available', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockContext = { productId: 42, variationId: 99 };
			mockRegisteredStore!.state.variationId = null;

			expect( mockRegisteredStore!.state.productVariationInContext ).toBe(
				mockVariation
			);
		} );

		it( 'productVariationInContext falls back to state when context exists but does not define variationId', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockContext = { productId: 42 };
			mockRegisteredStore!.state.variationId = 99;

			expect( mockRegisteredStore!.state.productVariationInContext ).toBe(
				mockVariation
			);
		} );

		it( 'productVariationInContext does not fall back to state when context explicitly sets variationId to null', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockContext = { productId: 42, variationId: null };
			mockRegisteredStore!.state.variationId = 99;

			expect( mockRegisteredStore!.state.productVariationInContext ).toBe(
				null
			);
		} );

		it( 'productVariationInContext falls back to state when context does not exist', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.variationId = 99;

			expect( mockRegisteredStore!.state.productVariationInContext ).toBe(
				mockVariation
			);
		} );

		it( 'productVariationInContext returns null when both context and state variationId are null', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.variationId = null;

			expect(
				mockRegisteredStore!.state.productVariationInContext
			).toBeNull();
		} );
	} );
} );
