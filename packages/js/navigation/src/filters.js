/**
 * External dependencies
 */
import { find, get, omit } from 'lodash';

/**
 * Get the url query key from the filter key and rule.
 *
 * @param {string} key  - filter key.
 * @param {string} rule - filter rule.
 * @return {string} - url query key.
 */
export function getUrlKey( key, rule ) {
	if ( rule && rule.length ) {
		return `${ key }_${ rule }`;
	}
	return key;
}

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
 * @property {string} key    - filter key.
 * @property {string} [rule] - a modifying rule for a filter, eg 'includes' or 'is_not'.
 * @property {string} value  - filter value(s).
 */

/**
 * Given a query object, return an array of activeFilters, if any.
 *
 * @param {Object} query  - query oject
 * @param {Object} config - config object
 * @return {Array} - array of activeFilters
 */
export function getActiveFiltersFromQuery( query, config ) {
	return Object.keys( config ).reduce( ( activeFilters, configKey ) => {
		const filter = config[ configKey ];

		if ( filter.rules ) {
			// Get all rules found in the query string.
			const matches = filter.rules.filter( ( rule ) =>
				query.hasOwnProperty( getUrlKey( configKey, rule.value ) )
			);

			if ( matches.length ) {
				if ( filter.allowMultiple ) {
					// If rules were found in the query string, and this filter supports
					// multiple instances, add all matches to the active filters array.
					matches.forEach( ( match ) => {
						const value =
							query[ getUrlKey( configKey, match.value ) ];

						value.forEach( ( filterValue ) => {
							activeFilters.push( {
								key: configKey,
								rule: match.value,
								value: filterValue,
							} );
						} );
					} );
				} else {
					// If the filter is a single instance, just process the first rule match.
					const value =
						query[ getUrlKey( configKey, matches[ 0 ].value ) ];
					activeFilters.push( {
						key: configKey,
						rule: matches[ 0 ].value,
						value,
					} );
				}
			}
		} else if ( query[ configKey ] ) {
			// If the filter doesn't have rules, but allows multiples.
			if ( filter.allowMultiple ) {
				const value = query[ configKey ];

				value.forEach( ( filterValue ) => {
					activeFilters.push( {
						key: configKey,
						value: filterValue,
					} );
				} );
			} else {
				// Filter with no rules and only one instance.
				activeFilters.push( {
					key: configKey,
					value: query[ configKey ],
				} );
			}
		}

		return activeFilters;
	}, [] );
}

/**
 * Get the default option's value from the configuration object for a given filter. The first
 * option is used as default if no `defaultOption` is provided.
 *
 * @param {Object} config  - a filter config object.
 * @param {Array}  options - select options.
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
 * Given activeFilters, create a new query object to update the url. Use previousFilters to
 * Remove unused params.
 *
 * @param {Array}  activeFilters - Array of activeFilters shown in the UI
 * @param {Object} query         - the current url query object
 * @param {Object} config        - config object
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
			const urlKey = getUrlKey( filter.key, filter.rule );

			if ( config[ filter.key ] && config[ filter.key ].allowMultiple ) {
				if ( ! data.hasOwnProperty( urlKey ) ) {
					data[ urlKey ] = [];
				}
				data[ urlKey ].push( filter.value );
			} else {
				data[ urlKey ] = filter.value;
			}
		}
		return data;
	}, {} );

	return { ...previousData, ...nextData };
}
