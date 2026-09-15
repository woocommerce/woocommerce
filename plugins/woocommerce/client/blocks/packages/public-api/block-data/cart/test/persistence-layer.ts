/**
 * Internal dependencies
 */
import { persistenceLayer } from '../persistence-layer';

describe( 'persistenceLayer.get', () => {
	const cartHash = 'test-cart-hash';

	beforeEach( () => {
		window.localStorage.clear();
		// A live cart session whose hash matches the cached one, so get() reaches the cached data.
		document.cookie = 'woocommerce_items_in_cart=1';
		document.cookie = `woocommerce_cart_hash=${ cartHash }`;
		window.localStorage.setItem( 'storeApiCartHash', cartHash );
	} );

	afterEach( () => {
		window.localStorage.clear();
		document.cookie =
			'woocommerce_items_in_cart=; expires=Thu, 01 Jan 1970 00:00:00 GMT';
		document.cookie =
			'woocommerce_cart_hash=; expires=Thu, 01 Jan 1970 00:00:00 GMT';
	} );

	it( 'returns null instead of throwing when the cached cart data is corrupt', () => {
		window.localStorage.setItem( 'storeApiCartData', '{not valid json' );

		expect( () => persistenceLayer.get() ).not.toThrow();
		expect( persistenceLayer.get() ).toBeNull();
	} );

	it( 'returns the parsed cart when the cached data is valid JSON', () => {
		window.localStorage.setItem(
			'storeApiCartData',
			JSON.stringify( { itemsCount: 2 } )
		);

		expect( persistenceLayer.get() ).toEqual( { itemsCount: 2 } );
	} );

	it( 'returns null when the cached value parses to a non-object', () => {
		window.localStorage.setItem( 'storeApiCartData', JSON.stringify( 42 ) );

		expect( persistenceLayer.get() ).toBeNull();
	} );
} );
