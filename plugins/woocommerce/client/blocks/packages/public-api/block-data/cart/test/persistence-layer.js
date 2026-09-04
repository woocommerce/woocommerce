/**
 * Internal dependencies
 */
import { persistenceLayer } from '../persistence-layer';

describe( 'persistenceLayer', () => {
	beforeEach( () => {
		window.localStorage.clear();
		document.cookie = 'woocommerce_items_in_cart=1';
		document.cookie = 'woocommerce_cart_hash=abc';
		window.localStorage.setItem( 'storeApiCartHash', 'abc' );
	} );

	it( 'returns null instead of throwing when the cached cart is corrupt', () => {
		window.localStorage.setItem( 'storeApiCartData', '{corrupt-json' );
		expect( () => persistenceLayer.get() ).not.toThrow();
		expect( persistenceLayer.get() ).toBeNull();
	} );

	it( 'returns the parsed cart when the cached cart is valid', () => {
		window.localStorage.setItem(
			'storeApiCartData',
			JSON.stringify( { itemsCount: 2 } )
		);
		expect( persistenceLayer.get() ).toEqual( { itemsCount: 2 } );
	} );
} );
