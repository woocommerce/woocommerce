/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { resolveSelect } from '@wordpress/data';
import { COUNTRIES_STORE_NAME } from '@woocommerce/data';
import { Flag } from '@woocommerce/components';

// Cache the locations to avoid rebuilding a few thousand of them on every keystroke.
let allLocations = null;

/**
 * The locations a tax code can belong to: every country, plus every state of that country.
 *
 * Keys are the form the `location_includes` and `location_excludes` report parameters read,
 * a country code (`GB`) or a country and state pair (`US:CA`).
 *
 * @return {Promise<Array<{key: string, label: string, country: string, keywords: string[]}>>} Locations.
 */
async function getLocations() {
	if ( allLocations ) {
		return allLocations;
	}

	const countries =
		await resolveSelect( COUNTRIES_STORE_NAME ).getCountries();

	const locations = ( countries || [] ).reduce( ( carry, country ) => {
		const countryName = decodeEntities( country.name );

		carry.push( {
			key: country.code,
			label: countryName,
			country: country.code,
			keywords: [ country.code, countryName ],
		} );

		( country.states || [] ).forEach( ( state ) => {
			const stateName = decodeEntities( state.name );
			const key = `${ country.code }:${ state.code }`;

			carry.push( {
				key,
				// The country code rather than its name: the filter input is narrow, and a
				// long label wraps a character at a time in the results list.
				label: sprintf(
					/* translators: 1: state name, 2: country code. Example: California (US) */
					__( '%1$s (%2$s)', 'woocommerce' ),
					stateName,
					country.code
				),
				country: country.code,
				keywords: [ key, stateName ],
			} );
		} );

		return carry;
	}, [] );

	if ( locations.length ) {
		allLocations = locations;
	}

	return locations;
}

/**
 * Wrap the part of a label the search matched, so the dropdown highlights it.
 *
 * @param {string} label Location label.
 * @param {string} query Search query.
 * @return {Object} Label split around the match.
 */
function highlightMatch( label, query ) {
	const start = query
		? label.toLowerCase().indexOf( query.toLowerCase() )
		: -1;

	if ( start === -1 ) {
		return { before: label, match: '', after: '' };
	}

	return {
		before: label.substring( 0, start ),
		match: label.substring( start, start + query.length ),
		after: label.substring( start + query.length ),
	};
}

/**
 * Autocompleter matching countries and their states.
 */
export const locationsAutocompleter = {
	name: 'locations',
	// Every result carries a flag beside a name, so it takes the country result styles.
	className: 'woocommerce-search__country-result',
	isDebounced: true,
	options: getLocations,
	getOptionIdentifier( location ) {
		return location.key;
	},
	getOptionKeywords( location ) {
		return location.keywords;
	},
	getSearchExpression( query ) {
		return '^' + query;
	},
	getOptionLabel( location, query ) {
		const { before, match, after } = highlightMatch(
			location.label,
			query
		);

		return (
			<>
				<Flag
					key="thumbnail"
					className="woocommerce-search__result-thumbnail"
					code={ location.country }
					size={ 18 }
					hideFromScreenReader
				/>
				<span
					key="name"
					className="woocommerce-search__result-name"
					aria-label={ location.label }
				>
					{ before }
					<strong className="components-form-token-field__suggestion-match">
						{ match }
					</strong>
					{ after }
				</span>
			</>
		);
	},
	getOptionCompletion( location ) {
		return {
			key: location.key,
			label: location.label,
		};
	},
};

/**
 * Labels of the locations a filter value holds, for the tags of a filter restored from the URL.
 *
 * @param {string} value Comma separated list of location keys.
 * @return {Promise<Array<{key: string, label: string}>>} Labels.
 */
export async function getLocationLabels( value ) {
	const keys = value.split( ',' ).filter( Boolean );
	const locations = await getLocations();

	return locations
		.filter( ( location ) => keys.includes( location.key ) )
		.map( ( { key, label } ) => ( { key, label } ) );
}
