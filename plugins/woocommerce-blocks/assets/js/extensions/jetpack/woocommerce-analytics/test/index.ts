/**
 * Internal dependencies
 */
import { cleanUrl } from '../index';

describe( 'WooCommerce Analytics', () => {
	describe( 'cleanUrl', () => {
		it( 'returns a clean URL with a trailing slash', () => {
			expect( cleanUrl( 'https://test.com?test=1' ) ).toEqual(
				'https://test.com/'
			);
			expect( cleanUrl( '' ) ).toEqual( '/' );
			expect( cleanUrl( 'https://test.com/' ) ).toEqual(
				'https://test.com/'
			);
			expect( cleanUrl( 'https://test.com' ) ).toEqual(
				'https://test.com/'
			);
		} );
	} );
} );
