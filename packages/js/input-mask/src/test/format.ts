/**
 * Internal dependencies
 */
import { format, unescapeMask } from '../format';

const PHONE = '+00 [000] (000) {000}';

describe( 'format', () => {
	it.each( [
		[ '', '', '' ],
		[ '3', '+3', '3' ],
		[ '+3', '+3', '3' ],
		[ '34', '+34', '34' ],
		[ '346', '+34 [6', '346' ],
		[ '34 [6', '+34 [6', '346' ],
		[ '34[6', '+34 [6', '346' ],
		[ '34697745564', '+34 [697] (745) {564}', '34697745564' ],
		[ '+34 [697] (745) {564}', '+34 [697] (745) {564}', '34697745564' ],
	] )( 'formats %p as %p', ( typed, display, unmasked ) => {
		expect( format( typed, PHONE ) ).toMatchObject( {
			display,
			unmasked,
			fits: true,
		} );
	} );

	it.each( [ '346x', '3+', '++3', '346977455641' ] )(
		'shows %p as typed when it does not fit',
		( typed ) => {
			expect( format( typed, PHONE ) ).toEqual( {
				display: typed,
				unmasked: typed,
				fits: false,
				map: Array.from( typed, ( _, i ) => i ),
			} );
		}
	);

	it( 'maps display characters to typed characters', () => {
		expect( format( '346', PHONE ).map ).toEqual( [ -1, 0, 1, -1, -1, 2 ] );
		expect( format( '34 [6', PHONE ).map ).toEqual( [ -1, 0, 1, 2, 3, 4 ] );
	} );

	it( 'supports letters, any character, escapes and digit literals', () => {
		expect( format( 'ab1', 'aa0' ) ).toMatchObject( {
			display: 'ab1',
			fits: true,
		} );
		expect( format( 'é1', 'a0' ) ).toMatchObject( { fits: true } );
		expect( format( 'a1', '**' ) ).toMatchObject( {
			display: 'a1',
			fits: true,
		} );
		expect( format( '-!', '**' ) ).toMatchObject( {
			display: '-!',
			fits: true,
		} );
		expect( format( '12', '\\000' ) ).toMatchObject( {
			display: '012',
			unmasked: '12',
		} );
		expect( format( '2', '100' ) ).toMatchObject( {
			display: '12',
			unmasked: '2',
		} );
		expect( format( '12', '100' ) ).toMatchObject( {
			display: '12',
			unmasked: '2',
		} );
	} );

	it( 'formats a CPF', () => {
		expect( format( '12345678901', '000.000.000-00' ) ).toMatchObject( {
			display: '123.456.789-01',
			unmasked: '12345678901',
		} );
	} );
} );

describe( 'unescapeMask', () => {
	it( 'removes escapes', () => {
		expect( unescapeMask( '\\00-\\a' ) ).toBe( '00-a' );
	} );
} );
