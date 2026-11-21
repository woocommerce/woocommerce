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
	it( 'returns the woocommerce/products objects, empty when it holds no product data', () => {
		require( '../products' );
		const productsState = mockStates[
			'woocommerce/products'
		] as ProductsStore[ 'state' ];
		const state = mockStates.woocommerce as ProductDataState;

		expect( state.products ).toEqual( {} );
		expect( state.productVariations ).toEqual( {} );

		productsState.products = { 42: product };
		productsState.productVariations = { 99: variation };

		expect( state.products ).toBe( productsState.products );
		expect( state.productVariations ).toBe(
			productsState.productVariations
		);
	} );
} );
