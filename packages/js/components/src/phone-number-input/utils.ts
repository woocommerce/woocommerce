/**
 * Internal dependencies
 */
import type { DataType } from './types';

const mapValues = < T, U >(
	object: Record< string, T >,
	iteratee: ( value: T ) => U
): Record< string, U > => {
	const result: Record< string, U > = {};

	for ( const key in object ) {
		result[ key ] = iteratee( object[ key ] );
	}

	return result;
};

/**
 * Removes any non-digit character.
 */
export const sanitizeNumber = ( number: string ): string =>
	number.replace( /\D/g, '' );

/**
 * Removes any non-digit character, except space and hyphen.
 */
export const sanitizeInput = ( number: string ): string =>
	number.replace( /[^\d -]/g, '' );

/**
 * Converts a valid phone number to E.164 format.
 */
export const numberToE164 = ( number: string ): string =>
	`+${ sanitizeNumber( number ) }`;

/**
 * Guesses the country code from a phone number.
 * If no match is found, it will fallback to US.
 *
 * @param number       Phone number including country code.
 * @param countryCodes List of country codes.
 * @return Country code in ISO 3166-1 alpha-2 format. e.g. US
 */
export const guessCountryKey = (
	number: string,
	countryCodes: Record< string, string[] >
): string => {
	number = sanitizeNumber( number );
	// Match each digit against countryCodes until a match is found
	for ( let i = number.length; i > 0; i-- ) {
		const match = countryCodes[ number.substring( 0, i ) ];
		if ( match ) return match[ 0 ];
	}
	return 'US';
};

const entityTable: Record< string, string > = {
	atilde: 'ã',
	ccedil: 'ç',
	eacute: 'é',
	iacute: 'í',
};

/**
 * Replaces HTML entities from a predefined table.
 */
export const decodeHtmlEntities = ( str: string ): string =>
	str.replace( /&(\S+?);/g, ( match, p1 ) => entityTable[ p1 ] || match );

const countryNames: Record< string, string > = mapValues(
	{
		AC: 'Ascension Island',
		XK: 'Kosovo',
		...( window.wcSettings?.countries || [] ),
	},
	( name ) => decodeHtmlEntities( name )
);

/**
 * Converts a country code to a flag twemoji URL from `s.w.org`.
 *
 * @param alpha2 Country code in ISO 3166-1 alpha-2 format. e.g. US
 * @return Country flag emoji URL.
 */
export const countryToFlag = ( alpha2: string ): string => {
	const name = alpha2
		.split( '' )
		.map( ( char ) =>
			( 0x1f1e5 + ( char.charCodeAt( 0 ) % 32 ) ).toString( 16 )
		)
		.join( '-' );

	return `https://s.w.org/images/core/emoji/14.0.0/72x72/${ name }.png`;
};

const pushOrAdd = (
	acc: Record< string, string[] >,
	key: string,
	value: string
) => {
	if ( acc[ key ] ) {
		if ( ! acc[ key ].includes( value ) ) acc[ key ].push( value );
	} else {
		acc[ key ] = [ value ];
	}
};

/**
 * Parses the data from `data.ts` into a more usable format.
 */
export const parseData = ( data: DataType ) => ( {
	countries: mapValues( data, ( country ) => ( {
		...country,
		name: countryNames[ country.alpha2 ] ?? country.alpha2,
		flag: countryToFlag( country.alpha2 ),
	} ) ),
	countryCodes: Object.values( data )
		.sort( ( a, b ) => ( a.priority > b.priority ? 1 : -1 ) )
		.reduce( ( acc, { code, alpha2, start } ) => {
			pushOrAdd( acc, code, alpha2 );
			if ( start ) {
				for ( const str of start ) {
					for ( let i = 1; i <= str.length; i++ ) {
						pushOrAdd( acc, code + str.substring( 0, i ), alpha2 );
					}
				}
			}
			return acc;
		}, {} as Record< string, string[] > ),
} );

export type Country = ReturnType< typeof parseData >[ 'countries' ][ 0 ];
