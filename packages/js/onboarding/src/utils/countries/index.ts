/**
 * External dependencies
 */
import stringSimilarity from 'string-similarity';

/**
 * Internal dependencies
 */
import { getMappingRegion } from './location-mapping';

/**
 * Country state option.
 */
export type CountryStateOption = {
	key: string;
	label: string;
};

export type Location = {
	country_short?: string;
	region?: string;
	city?: string;
};

/**
 * Returns a country option for the given location.
 */
export const findCountryOption = (
	countryStateOptions: CountryStateOption[],
	location: Location | undefined,
	minimumSimilarity = 0.7
) => {
	if ( ! location ) {
		return null;
	}

	let match = null;
	let matchSimilarity = minimumSimilarity;
	// eslint-disable-next-line @wordpress/no-unused-vars-before-return -- don't want to put this inside the loop
	const mappingRegion = getMappingRegion( location );

	for ( const option of countryStateOptions ) {
		// Country matches exactly.
		if ( option.key === location.country_short ) {
			return option;
		}

		// Countries have regions such as 'US:CA'.
		const countryCode = option.key.split( ':' )[ 0 ];
		if (
			countryCode === location.country_short &&
			option.label.includes( '—' )
		) {
			// WP GEO API Returns regions without accents.
			// Remove accents from the region to compare.
			// Málaga -> Malaga
			const wcRegion = option.label.split( '—' )[ 1 ].trim();

			// Region matches exactly with mapping.
			if ( mappingRegion === wcRegion ) {
				return option;
			}

			if (
				wcRegion.localeCompare( location.region || '', 'en', {
					sensitivity: 'base',
				} ) === 0 ||
				wcRegion.localeCompare( location.city || '', 'en', {
					sensitivity: 'base',
				} ) === 0
			) {
				return option;
			}

			// Find the region with the highest similarity.
			const similarity = Math.max(
				stringSimilarity.compareTwoStrings(
					wcRegion,
					location.region || ''
				),
				stringSimilarity.compareTwoStrings(
					wcRegion,
					location.city || ''
				)
			);
			if ( similarity >= matchSimilarity ) {
				match = option;
				matchSimilarity = similarity;
			}
		}
	}
	return match;
};

/**
 * Returns just the country portion of a country:state string that is delimited with a colon.
 *
 * @param countryPossiblyWithState Country string that may or may not have a state. e.g 'US:CA', 'UG:UG312'
 * @return Just the country portion of the string, or undefined if input is undefined. e.g 'US', 'UG'
 */
export const getCountry = ( countryPossiblyWithState: string ) =>
	countryPossiblyWithState?.split( ':' )[ 0 ] ?? undefined;
