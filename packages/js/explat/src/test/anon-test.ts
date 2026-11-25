/**
 * Internal dependencies
 */
import { readAnonCookie } from '../anon';

describe( 'readAnonCookie', () => {
	afterEach( () => jest.restoreAllMocks() );

	it.each( [
		[ 'other=1; other_tk_ai=unrelated', null ],
		[ 'other=1; tk_ai=', null ],
		[ 'other=1; tk_ai=one%3Dtwo=value; last=2', 'one=two=value' ],
		[ 'tk_ai=bad%2; other=2', 'bad%2' ],
		[ 'tk_ai="one%3Dtwo"', 'one=two' ],
		[ 'tk_ai="', null ],
	] )( 'reads the anonymous ID from %s', ( cookies, expected ) => {
		jest.spyOn( document, 'cookie', 'get' ).mockReturnValue( cookies );

		expect( readAnonCookie() ).toBe( expected );
	} );
} );
