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
				return {
					state: mockProductsState,
				};
			}
			if ( namespace === 'woocommerce/product-context' ) {
				// Simulate server-hydrated state merged with client definition.
				// Getters from definition.state are preserved, and productId /
				// variationId are added as plain values (simulating
				// wp_interactivity_state hydration).
				const stateBase = {
					productId: 0,
					variationId: null as number | null,
				};
				const descriptors = Object.getOwnPropertyDescriptors(
					definition.state
				);
				Object.defineProperties( stateBase, descriptors );

				mockRegisteredStore = {
					state: stateBase as ProductContextStore[ 'state' ],
				};
				return mockRegisteredStore;
			}
			return {};
		} ),
		getContext: jest.fn( () => mockContext ),
	} ),
	{ virtual: true }
);

describe( 'woocommerce/product-context store', () => {
	beforeEach( () => {
		mockRegisteredStore = null;
		mockContext = null;
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

	describe( 'product', () => {
		it( 'returns the product when variationId is null', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 42;
			mockRegisteredStore!.state.variationId = null;

			expect( mockRegisteredStore!.state.product ).toBe( mockProduct );
		} );

		it( 'returns the product even when variationId is set', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 42;
			mockRegisteredStore!.state.variationId = 99;

			// product always returns the main product, never the variation.
			expect( mockRegisteredStore!.state.product ).toBe( mockProduct );
		} );

		it( 'returns undefined when product is not in the store', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 999;
			mockRegisteredStore!.state.variationId = null;

			expect( mockRegisteredStore!.state.product ).toBeUndefined();
		} );

		it( 'returns undefined when productId is 0', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			expect( mockRegisteredStore!.state.product ).toBeUndefined();
		} );

		it( 'reads from block context when available', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 1;
			mockContext = { productId: 42 };

			expect( mockRegisteredStore!.state.product ).toBe( mockProduct );
		} );
	} );

	describe( 'selectedVariation', () => {
		it( 'returns null when variationId is null', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 42;
			mockRegisteredStore!.state.variationId = null;

			expect( mockRegisteredStore!.state.selectedVariation ).toBeNull();
		} );

		it( 'returns null when variationId is null (variable product, no selection)', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockProductsState.products[ 10 ] = {
				id: 10,
				type: 'variable',
			} as ProductResponseItem;
			mockRegisteredStore!.state.productId = 10;
			mockRegisteredStore!.state.variationId = null;

			expect( mockRegisteredStore!.state.selectedVariation ).toBeNull();
		} );

		it( 'returns the variation when variationId is set', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 42;
			mockRegisteredStore!.state.variationId = 99;

			expect( mockRegisteredStore!.state.selectedVariation ).toBe(
				mockVariation
			);
		} );

		it( 'returns null when variation is not in the store', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 42;
			mockRegisteredStore!.state.variationId = 999;

			expect( mockRegisteredStore!.state.selectedVariation ).toBeNull();
		} );
	} );

	describe( 'Product block path (context without variationId)', () => {
		it( 'product reads productId from context', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockContext = { productId: 42 };
			mockRegisteredStore!.state.variationId = null;

			expect( mockRegisteredStore!.state.product ).toBe( mockProduct );
		} );

		it( 'selectedVariation reads variationId from context when available', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockContext = { productId: 42, variationId: 99 };
			mockRegisteredStore!.state.variationId = null;

			expect( mockRegisteredStore!.state.selectedVariation ).toBe(
				mockVariation
			);
		} );

		it( 'selectedVariation falls back to state when context has no variationId', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockContext = { productId: 42 };
			mockRegisteredStore!.state.variationId = 99;

			expect( mockRegisteredStore!.state.selectedVariation ).toBe(
				mockVariation
			);
		} );

		it( 'selectedVariation returns null when both context and state variationId are null', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockContext = { productId: 42 };
			mockRegisteredStore!.state.variationId = null;

			expect( mockRegisteredStore!.state.selectedVariation ).toBeNull();
		} );
	} );

	describe( 'Product block path (context without variationId)', () => {
		it( 'currentProduct falls through to state variationId when context omits it', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			// SingleProduct block sets context with only productId (no variationId).
			mockContext = { productId: 42 };
			// Variation selector writes variationId to state.
			mockRegisteredStore!.state.variationId = 99;

			expect( mockRegisteredStore!.state.currentProduct ).toBe(
				mockVariation
			);
		} );

		it( 'parentProduct falls through to state variationId when context omits it', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockContext = { productId: 42 };
			mockRegisteredStore!.state.variationId = 99;

			expect( mockRegisteredStore!.state.parentProduct ).toBe(
				mockProduct
			);
		} );

		it( 'currentProduct returns the product when state variationId is null', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockContext = { productId: 42 };
			mockRegisteredStore!.state.variationId = null;

			expect( mockRegisteredStore!.state.currentProduct ).toBe(
				mockProduct
			);
		} );

		it( 'parentProduct returns null when state variationId is null', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockContext = { productId: 42 };
			mockRegisteredStore!.state.variationId = null;

			expect( mockRegisteredStore!.state.parentProduct ).toBeNull();
		} );
	} );
} );
