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
				const stateBase = {
					products: {} as Record< number, ProductResponseItem >,
					variations: {} as Record< number, ProductResponseItem >,
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

describe( 'woocommerce/products store', () => {
	beforeEach( () => {
		mockRegisteredStore = null;
		mockContext = null;
		jest.isolateModules( () => require( '../products' ) );
	} );

	describe( 'findVariation', () => {
		it( 'returns null when product is not in store', () => {
			const result = mockRegisteredStore!.state.findVariation( {
				id: 999,
			} );
			expect( result ).toBeNull();
		} );

		it( 'returns the product for a simple product', () => {
			const product = {
				id: 1,
				type: 'simple',
			} as ProductResponseItem;
			mockRegisteredStore!.state.products[ 1 ] = product;

			const result = mockRegisteredStore!.state.findVariation( {
				id: 1,
			} );
			expect( result ).toBe( product );
		} );

		it( 'returns the product when no selectedAttributes are provided', () => {
			const product = {
				id: 1,
				type: 'variable',
				variations: [
					{
						id: 10,
						attributes: [ { name: 'Color', value: 'red' } ],
					},
				],
			} as ProductResponseItem;
			mockRegisteredStore!.state.products[ 1 ] = product;

			const result = mockRegisteredStore!.state.findVariation( {
				id: 1,
			} );
			expect( result ).toBe( product );
		} );

		it( 'returns the product when selectedAttributes is empty', () => {
			const product = {
				id: 1,
				type: 'variable',
				variations: [
					{
						id: 10,
						attributes: [ { name: 'Color', value: 'red' } ],
					},
				],
			} as ProductResponseItem;
			mockRegisteredStore!.state.products[ 1 ] = product;

			const result = mockRegisteredStore!.state.findVariation( {
				id: 1,
				selectedAttributes: [],
			} );
			expect( result ).toBe( product );
		} );

		describe( 'variable products', () => {
			it( 'returns the variation when attributes match', () => {
				const product = {
					id: 1,
					type: 'variable',
					variations: [
						{
							id: 10,
							attributes: [ { name: 'Color', value: 'red' } ],
						},
					],
				} as ProductResponseItem;
				const variation = {
					id: 10,
					type: 'variation',
				} as ProductResponseItem;
				mockRegisteredStore!.state.products[ 1 ] = product;
				mockRegisteredStore!.state.variations[ 10 ] = variation;

				const result = mockRegisteredStore!.state.findVariation( {
					id: 1,
					selectedAttributes: [
						{ attribute: 'Color', value: 'red' },
					],
				} );
				expect( result ).toBe( variation );
			} );

			it( 'returns null when variation is matched but not in variations', () => {
				const product = {
					id: 1,
					type: 'variable',
					variations: [
						{
							id: 10,
							attributes: [ { name: 'Color', value: 'red' } ],
						},
					],
				} as ProductResponseItem;
				mockRegisteredStore!.state.products[ 1 ] = product;

				const result = mockRegisteredStore!.state.findVariation( {
					id: 1,
					selectedAttributes: [
						{ attribute: 'Color', value: 'red' },
					],
				} );
				expect( result ).toBeNull();
			} );

			it( 'returns null when no variation matches the attributes', () => {
				const product = {
					id: 1,
					type: 'variable',
					variations: [
						{
							id: 10,
							attributes: [ { name: 'Color', value: 'red' } ],
						},
					],
				} as ProductResponseItem;
				mockRegisteredStore!.state.products[ 1 ] = product;

				const result = mockRegisteredStore!.state.findVariation( {
					id: 1,
					selectedAttributes: [
						{ attribute: 'Color', value: 'blue' },
					],
				} );
				expect( result ).toBeNull();
			} );
		} );
	} );

	describe( 'product-in-context getters', () => {
		beforeEach( () => {
			mockRegisteredStore!.state.products[ 42 ] = mockProduct;
			mockRegisteredStore!.state.variations[ 99 ] = mockVariation;
		} );

		it( 'has writable productId and variationId state', () => {
			mockRegisteredStore!.state.productId = 42;
			mockRegisteredStore!.state.variationId = 99;

			expect( mockRegisteredStore!.state.productId ).toBe( 42 );
			expect( mockRegisteredStore!.state.variationId ).toBe( 99 );
		} );

		describe( 'product', () => {
			it( 'returns the product when variationId is null', () => {
				mockRegisteredStore!.state.productId = 42;
				mockRegisteredStore!.state.variationId = null;

				expect( mockRegisteredStore!.state.product ).toBe(
					mockProduct
				);
			} );

			it( 'returns the product even when variationId is set', () => {
				mockRegisteredStore!.state.productId = 42;
				mockRegisteredStore!.state.variationId = 99;

				// product always returns the main product, never the variation.
				expect( mockRegisteredStore!.state.product ).toBe(
					mockProduct
				);
			} );

			it( 'returns null when product is not in the store', () => {
				mockRegisteredStore!.state.productId = 999;
				mockRegisteredStore!.state.variationId = null;

				expect( mockRegisteredStore!.state.product ).toBeNull();
			} );

			it( 'returns null when productId is 0', () => {
				expect( mockRegisteredStore!.state.product ).toBeNull();
			} );

			it( 'reads from block context when available', () => {
				mockRegisteredStore!.state.productId = 1;
				mockContext = { productId: 42 };

				expect( mockRegisteredStore!.state.product ).toBe(
					mockProduct
				);
			} );
		} );

		describe( 'variation', () => {
			it( 'returns null when variationId is null (simple product)', () => {
				mockRegisteredStore!.state.productId = 42;
				mockRegisteredStore!.state.variationId = null;

				expect( mockRegisteredStore!.state.variation ).toBeNull();
			} );

			it( 'returns null when variationId is null (variable product, no selection)', () => {
				mockRegisteredStore!.state.products[ 10 ] = {
					id: 10,
					type: 'variable',
				} as ProductResponseItem;
				mockRegisteredStore!.state.productId = 10;
				mockRegisteredStore!.state.variationId = null;

				expect( mockRegisteredStore!.state.variation ).toBeNull();
			} );

			it( 'returns the variation when variationId is set', () => {
				mockRegisteredStore!.state.productId = 42;
				mockRegisteredStore!.state.variationId = 99;

				expect( mockRegisteredStore!.state.variation ).toBe(
					mockVariation
				);
			} );

			it( 'returns null when variation is not in the store', () => {
				mockRegisteredStore!.state.productId = 42;
				mockRegisteredStore!.state.variationId = 999;

				expect( mockRegisteredStore!.state.variation ).toBeNull();
			} );
		} );

		describe( 'selected', () => {
			it( 'returns the product when no variation is selected', () => {
				mockRegisteredStore!.state.productId = 42;
				mockRegisteredStore!.state.variationId = null;

				expect( mockRegisteredStore!.state.selected ).toBe(
					mockProduct
				);
			} );

			it( 'returns the variation when one is selected', () => {
				mockRegisteredStore!.state.productId = 42;
				mockRegisteredStore!.state.variationId = 99;

				expect( mockRegisteredStore!.state.selected ).toBe(
					mockVariation
				);
			} );

			it( 'returns null when product is not in the store', () => {
				mockRegisteredStore!.state.productId = 999;
				mockRegisteredStore!.state.variationId = null;

				expect( mockRegisteredStore!.state.selected ).toBeNull();
			} );
		} );

		describe( 'block context path (product loops)', () => {
			it( 'product reads productId from context', () => {
				mockContext = { productId: 42 };
				mockRegisteredStore!.state.variationId = null;

				expect( mockRegisteredStore!.state.product ).toBe(
					mockProduct
				);
			} );

			it( 'variation reads variationId from context when available', () => {
				mockContext = { productId: 42, variationId: 99 };
				mockRegisteredStore!.state.variationId = null;

				expect( mockRegisteredStore!.state.variation ).toBe(
					mockVariation
				);
			} );

			it( 'variation does not fall back to state when context variationId is null but context exists', () => {
				mockContext = { productId: 42 };
				mockRegisteredStore!.state.variationId = 99;

				expect( mockRegisteredStore!.state.variation ).toBe( null );
			} );

			it( 'variation falls back to state when context does not exist', () => {
				mockRegisteredStore!.state.variationId = 99;

				expect( mockRegisteredStore!.state.variation ).toBe(
					mockVariation
				);
			} );

			it( 'variation returns null when both context and state variationId are null', () => {
				mockRegisteredStore!.state.variationId = null;

				expect( mockRegisteredStore!.state.variation ).toBeNull();
			} );
		} );
	} );
} );
