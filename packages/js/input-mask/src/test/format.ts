/**
 * Internal dependencies
 */
import { format, unescapeMask } from '../format';

const PHONE = '+00 [000] (000) {000}';

describe( 'format', () => {
	it.each( [
		[ '', '', '' ],
		[ '3', '+3', '3' ],
		[ '34', '+34', '34' ],
		[ '346', '+34 [6', '346' ],
		[ '34697745564', '+34 [697] (745) {564}', '34697745564' ],
	] )( 'formats %p as %p', ( typed, display, unmasked ) => {
		expect( format( typed, PHONE ) ).toMatchObject( {
			display,
			unmasked,
			fits: true,
		} );
	} );

	it.each( [
		'346x',
		'3+',
		'++3',
		'346977455641',
		'+3',
		'34 [6',
		'34[6',
		'+34 [697] (745) {564}',
	] )( 'shows %p as typed when it does not fit', ( typed ) => {
		expect( format( typed, PHONE ) ).toEqual( {
			display: typed,
			unmasked: typed,
			fits: false,
			map: Array.from( typed, ( _, i ) => i ),
		} );
	} );

	it( 'maps display characters to typed characters', () => {
		expect( format( '346', PHONE ).map ).toEqual( [ -1, 0, 1, -1, -1, 2 ] );
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
			display: '112',
			unmasked: '12',
		} );
	} );

	it( 'formats a CPF', () => {
		expect( format( '12345678901', '000.000.000-00' ) ).toMatchObject( {
			display: '123.456.789-01',
			unmasked: '12345678901',
		} );
	} );

	it.each( [
		[ '1234', '10000', '11234' ],
		[ '012', '\\0000', '0012' ],
		[ 'abc', '\\aaaa', 'aabc' ],
		[ '(ab', '(***)', '((ab)' ],
	] )(
		'formats raw %p without consuming literals',
		( raw, mask, display ) => {
			expect( format( raw, mask ) ).toMatchObject( {
				display,
				unmasked: raw,
				fits: true,
			} );
		}
	);

	it.each( [
		[ '𐐀1', 'a-0', '𐐀-1', [ 0, 1, -1, 2 ] ],
		[ '😀1', '*-0', '😀-1', [ 0, 1, -1, 2 ] ],
		[ '12', '0😀0', '1😀2', [ 0, -1, -1, 1 ] ],
		[ '1', '\\😀0', '😀1', [ -1, -1, 0 ] ],
	] )(
		'formats Unicode input %p with DOM offsets',
		( typed, mask, display, map ) => {
			expect( format( typed, mask ) ).toMatchObject( {
				display,
				map,
				fits: true,
			} );
		}
	);

	it( 'maps every UTF-16 code unit when the input does not fit', () => {
		expect( format( '😀12', '000' ) ).toMatchObject( {
			fits: false,
			map: [ 0, 1, 2, 3 ],
		} );
	} );
} );

describe( 'unescapeMask', () => {
	it( 'removes escapes', () => {
		expect( unescapeMask( '\\00-\\a' ) ).toBe( '00-a' );
	} );

	it.each( [ '\n', '\r', '\u2028', '\u2029', '😀' ] )(
		'unescapes %p',
		( char ) => expect( unescapeMask( '\\' + char ) ).toBe( char )
	);
} );
