/**
 * Internal dependencies
 */
import { safeHref } from '../safe-href';

describe( 'safeHref', () => {
	it( 'keeps http, https and relative links', () => {
		expect( safeHref( 'https://ucpplayground.com/' ) ).toBe(
			'https://ucpplayground.com/'
		);
		expect( safeHref( '  https://ucpplayground.com/ ' ) ).toBe(
			'https://ucpplayground.com/'
		);
		expect( safeHref( 'http://example.com/wp-admin/' ) ).toBe(
			'http://example.com/wp-admin/'
		);
		expect( safeHref( 'admin.php?page=wc-settings' ) ).toBe(
			'admin.php?page=wc-settings'
		);
		expect( safeHref( 'admin.php?foo=a:b' ) ).toBe( 'admin.php?foo=a:b' );
		expect( safeHref( '/wp-admin/plugins.php' ) ).toBe(
			'/wp-admin/plugins.php'
		);
		expect( safeHref( 'HTTPS://ucpplayground.com/Path' ) ).toBe(
			'HTTPS://ucpplayground.com/Path'
		);
		expect( safeHref( 'Http://example.com/' ) ).toBe(
			'Http://example.com/'
		);
	} );

	it( 'drops links with any other scheme, and empty ones', () => {
		expect( safeHref( 'javascript:alert(1)' ) ).toBeUndefined();
		expect( safeHref( ' JavaScript:alert(1)' ) ).toBeUndefined();
		expect( safeHref( 'data:text/html,x' ) ).toBeUndefined();
		expect( safeHref( 'FTPS://files.example/a' ) ).toBeUndefined();
		expect( safeHref( '' ) ).toBeUndefined();
		expect( safeHref( null ) ).toBeUndefined();
	} );
} );
