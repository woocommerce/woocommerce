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
 * Describe activeFilter object.
 *
 * @typedef {Object} activeFilter
 * @property {string} key - filter key.
 * @property {string} [rule] - a modifying rule for a filter, eg 'includes' or 'is_not'.
 * @property {string} value - filter value(s).
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
					const value = query[ getUrlKey( configKey, match.value ) ];
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
 * Given activeFilters, create a new query object to update the url. Use previousFilters to
 * Remove unused params.
 *
 * @param {activeFilters[]} activeFilters - activeFilters shown in the UI
 * @param {object} query - the current url query object
 * @param {object} config - config object
 * @return {object} - query object representing the new parameters
 */
export const getQueryFromActiveFilters = ( activeFilters, query, config ) => {
	const previousFilters = getActiveFiltersFromQuery( query, config );
	const previousData = previousFilters.reduce( ( data, filter ) => {
		data[ getUrlKey( filter.key, filter.rule ) ] = undefined;
		return data;
	}, {} );
	const nextData = activeFilters.reduce( ( data, filter ) => {
		if ( filter.value ) {
			data[ getUrlKey( filter.key, filter.rule ) ] = filter.value;
		}
		return data;
	}, {} );

	return { ...previousData, ...nextData };
};

/**
 * Get the url query key from the filter key and rule.
 *
 * @param {object} config - a filter config object.
 * @param {array} options - select options.
 * @return {string|undefined}  - the value of the default option.
 */
export const getDefaultOptionValue = ( config, options ) => {
	const { defaultOption } = config.input;
	if ( config.input.defaultOption ) {
		const option = find( options, { value: defaultOption } );
		if ( ! option ) {
			console.warn( `invalid defaultOption ${ defaultOption } supplied to ${ config.labels.add }` );
			return undefined;
		}
		return option.value;
	}
	return options[ 0 ].value;
};
