/**
 * Internal dependencies
 */
import type { ProductContextStore } from '../product-context';
import type { ProductsStore } from '../products';

const mockProducts: ProductsStore[ 'state' ] = {
	products: {},
	productVariations: {},
};

let mockRegisteredStore: {
	state: ProductContextStore[ 'state' ];
} | null = null;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: jest.fn( ( name, definition ) => {
			if ( name === 'woocommerce/products' ) {
				return { state: mockProducts };
			}
			if ( definition?.state ) {
				mockRegisteredStore = { state: definition.state };
			}
			return mockRegisteredStore;
		} ),
	} ),
	{ virtual: true }
);

// Mock the products store import (side-effect import).
jest.mock( '../products', () => ( {} ), { virtual: true } );

describe( 'Product Context Interactivity API Store', () => {
	beforeEach( () => {
		jest.resetModules();
		mockRegisteredStore = null;
		mockProducts.products = {};
		mockProducts.productVariations = {};

		jest.isolateModules( () => {
			require( '../product-context' );
		} );
	} );

	it( 'has writable productId and variationId state', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Store was not registered.' );
		}

		expect( mockRegisteredStore.state.productId ).toBe( 0 );
		expect( mockRegisteredStore.state.variationId ).toBeNull();

		mockRegisteredStore.state.productId = 42;
		mockRegisteredStore.state.variationId = 99;

		expect( mockRegisteredStore.state.productId ).toBe( 42 );
		expect( mockRegisteredStore.state.variationId ).toBe( 99 );
	} );

	describe( 'currentProduct', () => {
		it( 'returns the product when variationId is null', () => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Store was not registered.' );
			}

			const product = { id: 10 };
			mockProducts.products[ 10 ] = product;
			mockRegisteredStore.state.productId = 10;
			mockRegisteredStore.state.variationId = null;

			expect( mockRegisteredStore.state.currentProduct ).toBe(
				product
			);
		} );

		it( 'returns the variation when variationId is set', () => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Store was not registered.' );
			}

			const variation = { id: 20 };
			mockProducts.products[ 10 ] = { id: 10 };
			mockProducts.productVariations[ 20 ] = variation;
			mockRegisteredStore.state.productId = 10;
			mockRegisteredStore.state.variationId = 20;

			expect( mockRegisteredStore.state.currentProduct ).toBe(
				variation
			);
		} );

		it( 'returns undefined when product is not in the store', () => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Store was not registered.' );
			}

			mockRegisteredStore.state.productId = 999;
			mockRegisteredStore.state.variationId = null;

			expect(
				mockRegisteredStore.state.currentProduct
			).toBeUndefined();
		} );

		it( 'returns undefined when productId is 0', () => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Store was not registered.' );
			}

			expect(
				mockRegisteredStore.state.currentProduct
			).toBeUndefined();
		} );
	} );

	describe( 'parentProduct', () => {
		it( 'returns null when variationId is null (simple product)', () => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Store was not registered.' );
			}

			mockProducts.products[ 10 ] = { id: 10 };
			mockRegisteredStore.state.productId = 10;
			mockRegisteredStore.state.variationId = null;

			expect( mockRegisteredStore.state.parentProduct ).toBeNull();
		} );

		it( 'returns null when variationId is null (variable product, no selection)', () => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Store was not registered.' );
			}

			mockProducts.products[ 10 ] = {
				id: 10,
				type: 'variable',
				variations: [ { id: 20 } ],
			};
			mockRegisteredStore.state.productId = 10;
			mockRegisteredStore.state.variationId = null;

			expect( mockRegisteredStore.state.parentProduct ).toBeNull();
		} );

		it( 'returns the variable product when variationId is set', () => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Store was not registered.' );
			}

			const parent = { id: 10, type: 'variable' };
			mockProducts.products[ 10 ] = parent;
			mockProducts.productVariations[ 20 ] = { id: 20 };
			mockRegisteredStore.state.productId = 10;
			mockRegisteredStore.state.variationId = 20;

			expect( mockRegisteredStore.state.parentProduct ).toBe(
				parent
			);
		} );
	} );
} );
