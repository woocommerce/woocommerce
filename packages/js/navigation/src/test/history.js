/**
 * Internal dependencies
 */
import { getHistory } from '../index';

// Unfortunately, we need to use `getHistory` to test `getHistory`.
// `getHistory` uses `remix-run/history` to observe and manipulate the history.
// The library ignores changes made using native `window.location` or `history.pushState`.
// Therefore, to change the location for testing, we need to use `getHistory().push().`
describe( 'getHistory', () => {
	it( 'should set returned `location.pathname` to the value of `path` QueryParam if there is one in `window.location`', () => {
		// Check given value.
		// window.location = new URL( 'https://www.example.com?path=foo' );
		getHistory().push( '?path=foo' );

		expect( getHistory().location.pathname ).toEqual( 'foo' );

		// Use empty string.
		getHistory().push( '?path=' );

		expect( getHistory().location.pathname ).toEqual( '' );
	} );

	it( 'should set returned `location.pathname` to `/` if there is no `path` QueryParam in `window.location`', () => {
		// window.location = new URL( '', window.location );
		getHistory().push( '' );

		expect( getHistory().location.pathname ).toEqual( '/' );
	} );

	it( 'should set returned `location.pathname` to `/` if there is no `path` QueryParam in `window.location`, even if there is a pathname', () => {
		// window.location = new URL( '/foo', window.location );
		const baseURL = window.location || document.location;
		// Normalize URL to `remix-run/history`'s `Path`, as `URL` is not supported.
		// See https://github.com/remix-run/history/pull/963.
		const nextUrl = new URL( '/foo', baseURL );
		getHistory().push( {
			pathname: nextUrl.pathname,
			search: nextUrl.search,
		} );

		expect( getHistory().location.pathname ).toEqual( '/' );
	} );
} );
