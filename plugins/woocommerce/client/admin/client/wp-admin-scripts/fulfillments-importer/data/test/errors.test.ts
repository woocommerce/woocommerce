/**
 * Internal dependencies
 */
import { errorCode, errorMessage, errorStatus } from '../errors';

const GENERIC = 'Something went wrong. Please try again.';

describe( 'errorMessage', () => {
	it.each( [
		[ 'an Error', new Error( 'Failed to fetch' ), 'Failed to fetch' ],
		[
			'an apiFetch rejection',
			{ code: 'x', message: 'Bad mapping.', data: { status: 400 } },
			'Bad mapping.',
		],
		[ 'a string', 'Plain text', 'Plain text' ],
		[ 'an Error with no message', new Error(), GENERIC ],
		[ 'a blank message', { message: '   ' }, GENERIC ],
		[ 'a non-string message', { message: 42 }, GENERIC ],
		[ 'null', null, GENERIC ],
		[ 'undefined', undefined, GENERIC ],
	] )( 'returns the text to show for %s', ( _label, error, expected ) => {
		expect( errorMessage( error ) ).toBe( expected );
	} );
} );

describe( 'errorStatus', () => {
	it.each( [
		[ { data: { status: 409 } }, 409 ],
		[ { data: { status: '409' } }, null ],
		[ { data: null }, null ],
		[ { code: 'x' }, null ],
		[ new Error( 'x' ), null ],
		[ null, null ],
	] )( 'reads %p as %p', ( error, expected ) => {
		expect( errorStatus( error ) ).toBe( expected );
	} );
} );

describe( 'errorCode', () => {
	it.each( [
		[ { code: 'rest_forbidden' }, 'rest_forbidden' ],
		[ { code: 403 }, null ],
		[ 'rest_forbidden', null ],
		[ null, null ],
	] )( 'reads %p as %p', ( error, expected ) => {
		expect( errorCode( error ) ).toBe( expected );
	} );
} );
