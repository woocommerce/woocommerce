/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { ProductContextStore } from '../product-context';

let mockRegisteredStore: {
	state: ProductContextStore[ 'state' ];
} | null = null;

let mockProductsState: {
	products: Record< number, ProductResponseItem >;
	productVariations: Record< number, ProductResponseItem >;
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
				return {
					state: mockProductsState,
				};
			}
			if ( namespace === 'woocommerce/product-context' ) {
				mockRegisteredStore = {
					state: definition.state,
				};
				return mockRegisteredStore;
			}
			return {};
		} ),
	} ),
	{ virtual: true }
);

describe( 'woocommerce/product-context store', () => {
	beforeEach( () => {
		mockRegisteredStore = null;
		mockProductsState = {
			products: { 42: mockProduct },
			productVariations: { 99: mockVariation },
		};

		jest.isolateModules( () => require( '../product-context' ) );
	} );

	it( 'has writable productId and variationId state', () => {
		expect( mockRegisteredStore ).not.toBeNull();

		mockRegisteredStore!.state.productId = 42;
		mockRegisteredStore!.state.variationId = 99;

		expect( mockRegisteredStore!.state.productId ).toBe( 42 );
		expect( mockRegisteredStore!.state.variationId ).toBe( 99 );
	} );

	describe( 'currentProduct', () => {
		it( 'returns the product when variationId is null', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 42;
			mockRegisteredStore!.state.variationId = null;

			expect( mockRegisteredStore!.state.currentProduct ).toBe(
				mockProduct
			);
		} );

		it( 'returns the variation when variationId is set', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 42;
			mockRegisteredStore!.state.variationId = 99;

			expect( mockRegisteredStore!.state.currentProduct ).toBe(
				mockVariation
			);
		} );

		it( 'returns undefined when product is not in the store', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 999;
			mockRegisteredStore!.state.variationId = null;

			expect(
				mockRegisteredStore!.state.currentProduct
			).toBeUndefined();
		} );

		it( 'returns undefined when productId is 0', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			expect(
				mockRegisteredStore!.state.currentProduct
			).toBeUndefined();
		} );
	} );

	describe( 'parentProduct', () => {
		it( 'returns null when variationId is null (simple product)', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 42;
			mockRegisteredStore!.state.variationId = null;

			expect(
				mockRegisteredStore!.state.parentProduct
			).toBeNull();
		} );

		it( 'returns null when variationId is null (variable product, no selection)', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockProductsState.products[ 10 ] = {
				id: 10,
				type: 'variable',
			} as ProductResponseItem;
			mockRegisteredStore!.state.productId = 10;
			mockRegisteredStore!.state.variationId = null;

			expect(
				mockRegisteredStore!.state.parentProduct
			).toBeNull();
		} );

		it( 'returns the variable product when variationId is set', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 42;
			mockRegisteredStore!.state.variationId = 99;

			expect( mockRegisteredStore!.state.parentProduct ).toBe(
				mockProduct
			);
		} );
	} );
} );
