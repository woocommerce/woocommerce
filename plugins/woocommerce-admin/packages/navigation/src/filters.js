/**
 * External dependencies
 */
import { compact, find, get, omit } from 'lodash';

/**
 * Collapse an array of filter values with subFilters into a 1-dimensional array.
 *
 * @param {Array} filters Set of filters with possible subfilters.
 * @return {Array} Flattened array of all filters.
 */
export function flattenFilters( filters ) {
	const allFilters = [];
	filters.forEach( ( f ) => {
		if ( ! f.subFilters ) {
			allFilters.push( f );
		} else {
			allFilters.push( omit( f, 'subFilters' ) );
			const subFilters = flattenFilters( f.subFilters );
			allFilters.push( ...subFilters );
		}
	} );
	return allFilters;
}

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
 * @param {Object} query - query oject
 * @param {Object} config - config object
 * @return {activeFilters[]} - array of activeFilters
 */
export function getActiveFiltersFromQuery( query, config ) {
	return compact(
		Object.keys( config ).map( ( configKey ) => {
			const filter = config[ configKey ];
			if ( filter.rules ) {
				const match = find( filter.rules, ( rule ) => {
					return query.hasOwnProperty(
						getUrlKey( configKey, rule.value )
					);
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
}

/**
 * Get the default option's value from the configuration object for a given filter. The first
 * option is used as default if no `defaultOption` is provided.
 *
 * @param {Object} config - a filter config object.
 * @param {Array} options - select options.
 * @return {string|undefined}  - the value of the default option.
 */
export function getDefaultOptionValue( config, options ) {
	const { defaultOption } = config.input;
	if ( config.input.defaultOption ) {
		const option = find( options, { value: defaultOption } );
		if ( ! option ) {
			/* eslint-disable no-console */
			console.warn(
				`invalid defaultOption ${ defaultOption } supplied to ${ config.labels.add }`
			);
			/* eslint-enable */
			return undefined;
		}
		return option.value;
	}
	return get( options, [ 0, 'value' ] );
}

/**
 * @typedef {Object} activeFilters
 * @property
 */

/**
 * Given activeFilters, create a new query object to update the url. Use previousFilters to
 * Remove unused params.
 *
 * @param {activeFilters[]} activeFilters - activeFilters shown in the UI
 * @param {Object} query - the current url query object
 * @param {Object} config - config object
 * @return {Object} - query object representing the new parameters
 */
export function getQueryFromActiveFilters( activeFilters, query, config ) {
	const previousFilters = getActiveFiltersFromQuery( query, config );
	const previousData = previousFilters.reduce( ( data, filter ) => {
		data[ getUrlKey( filter.key, filter.rule ) ] = undefined;
		return data;
	}, {} );
	const nextData = activeFilters.reduce( ( data, filter ) => {
		if (
			filter.rule === 'between' &&
			( ! Array.isArray( filter.value ) ||
				filter.value.some( ( value ) => ! value ) )
		) {
			return data;
		}

		if ( filter.value ) {
			data[ getUrlKey( filter.key, filter.rule ) ] = filter.value;
		}
		return data;
	}, {} );

	return { ...previousData, ...nextData };
}

/**
 * Get the url query key from the filter key and rule.
 *
 * @param {string} key - filter key.
 * @param {string} rule - filter rule.
 * @return {string} - url query key.
 */
export function getUrlKey( key, rule ) {
	if ( rule && rule.length ) {
		return `${ key }_${ rule }`;
	}
	return key;
}
