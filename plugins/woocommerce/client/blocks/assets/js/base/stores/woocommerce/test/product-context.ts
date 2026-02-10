/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { ProductContextStore } from '../product-context';

type MockStore = {
	state: ProductContextStore[ 'state' ];
	actions: ProductContextStore[ 'actions' ];
};

let mockRegisteredStore: MockStore | null = null;
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
					actions: definition.actions,
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

	describe( 'actions', () => {
		it( 'setProductId updates productId in state', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.actions.setProductId( 100 );

			expect( mockRegisteredStore!.state.productId ).toBe( 100 );
		} );

		it( 'setVariationId updates variationId in state', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.actions.setVariationId( 200 );

			expect( mockRegisteredStore!.state.variationId ).toBe( 200 );
		} );

		it( 'setVariationId accepts null to clear selection', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.actions.setVariationId( 200 );
			mockRegisteredStore!.actions.setVariationId( null );

			expect( mockRegisteredStore!.state.variationId ).toBeNull();
		} );
	} );

	describe( 'computed getters', () => {
		it( 'selectedProduct returns variation when set, otherwise product', () => {
			expect( mockRegisteredStore ).not.toBeNull();

			mockRegisteredStore!.state.productId = 42;
			mockRegisteredStore!.state.variationId = 99;

			expect( mockRegisteredStore!.state.selectedProduct ).toBe(
				mockVariation
			);

			mockRegisteredStore!.actions.setVariationId( null );

			expect( mockRegisteredStore!.state.selectedProduct ).toBe(
				mockProduct
			);
		} );
	} );
} );
