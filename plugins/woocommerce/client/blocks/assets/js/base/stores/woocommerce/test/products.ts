/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

/**
 * Internal dependencies
 */
import type { ProductDataState } from '../products';

const mockStates: Record< string, Record< string, unknown > > = {};

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getContext: jest.fn(),
		// Each namespace gets one state object, and every `store()` call for it
		// merges its `state` descriptors (getters included) onto that object, as
		// the library does when several modules contribute to the same store.
		store: jest.fn( ( namespace: string, definition ) => {
			mockStates[ namespace ] ??= {};
			if ( definition?.state ) {
				Object.defineProperties(
					mockStates[ namespace ],
					Object.getOwnPropertyDescriptors( definition.state )
				);
			}
			return { state: mockStates[ namespace ] };
		} ),
	} ),
	{ virtual: true }
);

const product = { id: 42, name: 'Hoodie' } as ProductResponseItem;
const variation = { id: 99, name: 'Hoodie - Blue' } as ProductResponseItem;

describe( 'woocommerce store – product data', () => {
	let productsState: ProductsStore[ 'state' ];
	let state: ProductDataState;

	beforeEach( () => {
		for ( const namespace of Object.keys( mockStates ) ) {
			delete mockStates[ namespace ];
		}
		jest.isolateModules( () => require( '../products' ) );
		productsState = mockStates[
			'woocommerce/products'
		] as ProductsStore[ 'state' ];
		state = mockStates.woocommerce as ProductDataState;
	} );

	it( 'is empty when woocommerce/products holds no product data', () => {
		expect( state.products ).toEqual( {} );
		expect( state.productVariations ).toEqual( {} );
	} );

	it( 'returns what woocommerce/products holds under each ID', () => {
		productsState.products = { 42: product };
		productsState.productVariations = { 99: variation };

		expect( state.products[ 42 ] ).toBe( product );
		expect( state.productVariations[ 99 ] ).toBe( variation );
		expect( state.products[ 7 ] ).toBeUndefined();
		expect( state.productVariations[ 7 ] ).toBeUndefined();
	} );

	it( 'returns entries woocommerce/products gains later, such as after client-side navigation', () => {
		productsState.products = { 42: product };

		const nextPageProduct = { id: 43, name: 'Cap' } as ProductResponseItem;
		productsState.products[ 43 ] = nextPageProduct;

		expect( state.products ).toEqual( {
			42: product,
			43: nextPageProduct,
		} );
	} );
} );
