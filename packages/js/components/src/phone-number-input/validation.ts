/**
 * Internal dependencies
 */
import data from './data';
import { guessCountryKey, parseData } from './utils';

const { countries, countryCodes } = parseData( data );

/**
 *	Mobile phone number validation based on `data.ts` rules.
 *  If no country is provided, it will try to guess it from the number or fallback to US.
 *
 * @param number        Phone number to validate in E.164 format. e.g. +12345678901
 * @param countryAlpha2 Country code in ISO 3166-1 alpha-2 format. e.g. US
 * @return boolean
 */
export const validatePhoneNumber = (
	number: string,
	countryAlpha2?: string
): boolean => {
	// Sanitize number.
	number = '+' + number.replace( /\D/g, '' );

	// Return early If format is not E.164.
	if ( ! /^\+[1-9]\d{1,14}$/.test( number ) ) {
		return false;
	}

	// If country is not provided, try to guess it from the number or fallback to US.
	if ( ! countryAlpha2 ) {
		countryAlpha2 = guessCountryKey( number, countryCodes );
	}

	const country = countries[ countryAlpha2 ];

	// Remove `+` and country code.
	number = number.slice( country.code.length + 1 );

	// If country as `lengths` defined check if number matches.
	if ( country.lengths && ! country.lengths.includes( number.length ) ) {
		return false;
	}

	// If country has `start` defined check if number starts with one of them.
	if (
		country.start &&
		! country.start.some( ( prefix ) => number.startsWith( prefix ) )
	) {
		return false;
	}

	return true;
};
