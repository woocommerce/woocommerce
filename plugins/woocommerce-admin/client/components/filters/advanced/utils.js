/** @format */
/**
 * External dependencies
 */
import { find, compact } from 'lodash';

/**
 * Get the url query key from the filter key and rule.
 *
 * @param {string} key - filter key.
 * @param {string} rule - filter rule.
 * @return {string} - url query key.
 */
export const getUrlKey = ( key, rule ) => {
	if ( rule && rule.length ) {
		return `${ key }_${ rule }`;
	}
	return key;
};

/**
 * Convert url values to array of objects for <Search /> component
 *
 * @param {string} str - url query parameter value
 * @return {array} - array of Search values
 */
export const getSearchFilterValue = str => {
	return str.length ? str.trim().split( ',' ) : [];
};

/**
 * Describe activeFilter object.
 *
 * @typedef {Object} activeFilter
 * @property {string} key - filter key.
 * @property {string} [rule] - a modifying rule for a filter, eg 'includes' or 'is_not'.
 * @property {string|array} value - filter value(s).
 */

/**
 * Given a query object, return an array of activeFilters, if any.
 *
 * @param {object} query - query oject
 * @param {object} config - config object
 * @return {activeFilters[]} - array of activeFilters
 */
export const getActiveFiltersFromQuery = ( query, config ) => {
	return compact(
		Object.keys( config ).map( configKey => {
			const filter = config[ configKey ];
			if ( filter.rules ) {
				const match = find( filter.rules, rule => {
					return query.hasOwnProperty( getUrlKey( configKey, rule.value ) );
				} );

				if ( match ) {
					const rawValue = query[ getUrlKey( configKey, match.value ) ];
					const value =
						'Search' === filter.input.component ? getSearchFilterValue( rawValue ) : rawValue;
					return {
						key: configKey,
						rule: match.value,
						value,
					};
				}
				return null;
			}
			if ( query[ configKey ] ) {
				return {
					key: configKey,
					value: query[ configKey ],
				};
			}
			return null;
		} )
	);
};

/**
 * Create a string value for url. Return a string directly or concatenate ids if supplied
 * an array of objects.
 *
 * @param {string|array} value - value of an activeFilter
 * @return {string|null} - url query param value
 */
export const getUrlValue = value => {
	if ( Array.isArray( value ) ) {
		return value.length ? value.join( ',' ) : null;
	}
	return 'string' === typeof value ? value : null;
};

/**
 * Given activeFilters, create a new query object to update the url. Use previousFilters to
 * Remove unused params.
 *
 * @param {activeFilters[]} nextFilters - activeFilters shown in the UI
 * @param {activeFilters[]} previousFilters - filters represented by the current url
 * @return {object} - query object representing the new parameters
 */
export const getQueryFromActiveFilters = ( nextFilters, previousFilters = [] ) => {
	const previousData = previousFilters.reduce( ( query, filter ) => {
		query[ getUrlKey( filter.key, filter.rule ) ] = undefined;
		return query;
	}, {} );
	const data = nextFilters.reduce( ( query, filter ) => {
		const urlValue = getUrlValue( filter.value );
		if ( urlValue ) {
			query[ getUrlKey( filter.key, filter.rule ) ] = urlValue;
		}
		return query;
	}, {} );

	return { ...previousData, ...data };
};
