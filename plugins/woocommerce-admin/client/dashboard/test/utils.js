/**
 * Internal dependencies
 */
import { isWCAdmin } from '../utils';

describe( 'isWCAdmin', () => {
	it( 'correctly identifies WC admin urls', () => {
		[
			'https://example.com/wp-admin/admin.php?page=wc-admin',
			'https://example.com/wp-admin/admin.php?page=wc-admin&foo=bar',
			'/admin.php?page=wc-admin',
			'/admin.php?page=wc-admin&foo=bar',
		].forEach( ( url ) => {
			expect( isWCAdmin( url ) ).toBe( true );
		} );
	} );

	it( 'rejects URLs that are not WC admin urls', () => {
		[
			'https://example.com/wp-admin/edit.php?page=wc-admin',
			'https://example.com/wp-admin/admin.php?page=other',
			'/edit.php?page=wc-admin',
			'/admin.php?page=other',
		].forEach( ( url ) => {
			expect( isWCAdmin( url ) ).toBe( false );
		} );
	} );
} );
