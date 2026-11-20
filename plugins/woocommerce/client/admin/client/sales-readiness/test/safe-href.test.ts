/**
 * Internal dependencies
 */
import { safeHref } from '../safe-href';

describe( 'safeHref', () => {
	it( 'keeps http, https and relative links', () => {
		expect( safeHref( 'https://ucpplayground.com/' ) ).toBe(
			'https://ucpplayground.com/'
		);
		expect( safeHref( 'http://example.com/wp-admin/' ) ).toBe(
			'http://example.com/wp-admin/'
		);
		expect( safeHref( 'admin.php?page=wc-settings' ) ).toBe(
			'admin.php?page=wc-settings'
		);
		expect( safeHref( '/wp-admin/plugins.php' ) ).toBe(
			'/wp-admin/plugins.php'
		);
	} );

	it( 'drops links with any other scheme, and empty ones', () => {
		expect( safeHref( 'javascript:alert(1)' ) ).toBeUndefined();
		expect( safeHref( ' JavaScript:alert(1)' ) ).toBeUndefined();
		expect( safeHref( 'data:text/html,x' ) ).toBeUndefined();
		expect( safeHref( '' ) ).toBeUndefined();
		expect( safeHref( null ) ).toBeUndefined();
	} );
} );
