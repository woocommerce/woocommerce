/**
 * External dependencies
 */
import { keyBy, mapValues, sortBy } from 'lodash';

/**
 * Internal dependencies
 */
import { DataType } from './data';

export const sanitizeNumber = ( number: string ): string =>
	number.replace( /\D/g, '' );

export const sanitizeInput = ( number: string ): string =>
	number.replace( /[^\d- ]/g, '' );

export const numberToE164 = ( number: string ): string =>
	`+${ sanitizeNumber( number ) }`;

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

export const parseData = ( data: DataType ) => {
	const countries = keyBy( data, 'alpha2' );
	return {
		countries: mapValues( countries, ( country ) => ( {
			...country,
			name: countryNames[ country.alpha2 ] ?? country.alpha2,
			flag: countryToFlag( country.alpha2 ),
		} ) ),
		countryCodes: sortBy( data, 'priority' ).reduce(
			( acc, { code, alpha2, start } ) => {
				pushOrAdd( acc, code, alpha2 );
				if ( start ) {
					for ( const str of start ) {
						for ( let i = 1; i <= str.length; i++ ) {
							pushOrAdd(
								acc,
								code + str.substring( 0, i ),
								alpha2
							);
						}
					}
				}
				return acc;
			},
			{} as Record< string, string[] >
		),
	};
};

export type Country = ReturnType< typeof parseData >[ 'countries' ][ 0 ];
