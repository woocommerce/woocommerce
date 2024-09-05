/**
 * Internal dependencies
 */
import {
	sanitizeNumber,
	sanitizeInput,
	numberToE164,
	guessCountryKey,
	decodeHtmlEntities,
	countryToFlag,
	parseData,
} from '../utils';

describe( 'PhoneNumberInput Utils', () => {
	describe( 'sanitizeNumber', () => {
		it( 'removes non-digit characters', () => {
			const result = sanitizeNumber( '+1 23-45 67' );
			expect( result ).toBe( '1234567' );
		} );
	} );

	describe( 'sanitizeInput', () => {
		it( 'removes non-digit characters except space and hyphen', () => {
			const result = sanitizeInput( '+1 23--45 67 abc' );
			expect( result ).toBe( '1 23--45 67 ' );
		} );
	} );

	describe( 'numberToE164', () => {
		it( 'converts a valid phone number to E.164 format', () => {
			const result = numberToE164( '+1 23-45 67' );
			expect( result ).toBe( '+1234567' );
		} );
	} );

	describe( 'guessCountryKey', () => {
		it( 'guesses the country code from a phone number', () => {
			const countryCodes = {
				'1': [ 'US' ],
				'34': [ 'ES' ],
			};

			const result = guessCountryKey( '34666777888', countryCodes );
			expect( result ).toBe( 'ES' );
		} );

		it( 'falls back to US if no match is found', () => {
			const countryCodes = {
				'34': [ 'ES' ],
			};

			const result = guessCountryKey( '1234567890', countryCodes );
			expect( result ).toBe( 'US' );
		} );
	} );

	describe( 'decodeHtmlEntities', () => {
		it( 'replaces HTML entities from a predefined table', () => {
			const result = decodeHtmlEntities(
				'&atilde;&ccedil;&eacute;&iacute;'
			);
			expect( result ).toBe( 'ãçéí' );
		} );
	} );

	describe( 'countryToFlag', () => {
		it( 'converts a country code to a flag twemoji URL', () => {
			const result = countryToFlag( 'US' );
			expect( result ).toBe(
				'https://s.w.org/images/core/emoji/14.0.0/72x72/1f1fa-1f1f8.png'
			);
		} );
	} );

	describe( 'parseData', () => {
		it( 'parses the data into a more usable format', () => {
			const data = {
				AF: {
					alpha2: 'AF',
					code: '93',
					priority: 0,
					start: [ '7' ],
					lengths: [ 9 ],
				},
			};

			const { countries, countryCodes } = parseData( data );
			expect( countries ).toEqual( {
				AF: {
					alpha2: 'AF',
					code: '93',
					flag: 'https://s.w.org/images/core/emoji/14.0.0/72x72/1f1e6-1f1eb.png',
					lengths: [ 9 ],
					name: 'AF',
					priority: 0,
					start: [ '7' ],
				},
			} );
			expect( countryCodes ).toEqual( {
				'93': [ 'AF' ],
				'937': [ 'AF' ],
			} );
		} );
	} );
} );
