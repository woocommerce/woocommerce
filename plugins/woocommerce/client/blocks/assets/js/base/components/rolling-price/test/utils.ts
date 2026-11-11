/**
 * Internal dependencies
 */
import { getRollingCharacters } from '../utils';

describe( 'getRollingCharacters', () => {
	it( 'rolls each digit from the digit at the same place', () => {
		expect( getRollingCharacters( '12.30', '45.60' ) ).toEqual( [
			{ key: '4', character: '4', digit: 4, fromDigit: 1 },
			{ key: '3', character: '5', digit: 5, fromDigit: 2 },
			{ key: '2', character: '.', digit: null, fromDigit: 0 },
			{ key: '1', character: '6', digit: 6, fromDigit: 3 },
			{ key: '0', character: '0', digit: 0, fromDigit: 0 },
		] );
	} );

	it( 'counts places from the end when the price gains digits', () => {
		const characters = getRollingCharacters( '999.99', '1,000.00' );

		expect( characters.map( ( { key } ) => key ) ).toEqual( [
			'7',
			'6',
			'5',
			'4',
			'3',
			'2',
			'1',
			'0',
		] );
		// The new leading digit rolls from 0, and the separator does not roll.
		expect( characters[ 0 ] ).toEqual( {
			key: '7',
			character: '1',
			digit: 1,
			fromDigit: 0,
		} );
		expect( characters[ 1 ] ).toMatchObject( {
			character: ',',
			digit: null,
		} );
		expect( characters[ 2 ] ).toMatchObject( { digit: 0, fromDigit: 9 } );
	} );

	it( 'keeps the places of the decimals when the price loses digits', () => {
		expect(
			getRollingCharacters( '1.234,56', '987,65' ).map(
				( { character, fromDigit } ) => [ character, fromDigit ]
			)
		).toEqual( [
			[ '9', 2 ],
			[ '8', 3 ],
			[ '7', 4 ],
			[ ',', 0 ],
			[ '6', 5 ],
			[ '5', 6 ],
		] );
	} );

	it( 'rolls a digit from 0 when the old character at its place is a separator', () => {
		expect( getRollingCharacters( '1 000', '12000' )[ 1 ] ).toEqual( {
			key: '3',
			character: '2',
			digit: 2,
			fromDigit: 0,
		} );
	} );
} );
