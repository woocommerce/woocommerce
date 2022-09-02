/**
 * External dependencies
 */
import { isNil } from 'lodash';
import { format as d3Format } from 'd3-format';
import { utcParse as d3UTCParse } from 'd3-time-format';

/**
 * Allows an overriding formatter or defaults to d3Format or d3TimeFormat
 *
 * @param {string|Function} format    - either a format string for the D3 formatters or an overriding fomatting method
 * @param {Function}        formatter - default d3Format or another formatting method, which accepts the string `format`
 * @return {Function} to be used to format an input given the format and formatter
 */
export const getFormatter = ( format, formatter = d3Format ) =>
	typeof format === 'function' ? format : formatter( format );

/**
 * Returns an array of unique keys contained in the data.
 *
 * @param {Array} data - The chart component's `data` prop.
 * @return {Array} Array of unique keys.
 */
export const getUniqueKeys = ( data ) => {
	const keys = new Set(
		data.reduce( ( acc, curr ) => acc.concat( Object.keys( curr ) ), [] )
	);

	return [ ...keys ].filter( ( key ) => key !== 'date' );
};

/**
 * Describes `getOrderedKeys`
 *
 * @param {Array} data - The chart component's `data` prop.
 * @return {Array} Array of unique category keys ordered by cumulative total value
 */
export const getOrderedKeys = ( data ) => {
	const keys = getUniqueKeys( data );

	return keys
		.map( ( key ) => ( {
			key,
			focus: true,
			total: data.reduce( ( a, c ) => a + c[ key ].value, 0 ),
			visible: true,
		} ) )
		.sort( ( a, b ) => b.total - a.total );
};

/**
 * Describes `getUniqueDates`
 *
 * @param {Array}  data       - the chart component's `data` prop.
 * @param {string} dateParser - D3 time format
 * @return {Array} an array of unique date values sorted from earliest to latest
 */
export const getUniqueDates = ( data, dateParser ) => {
	const parseDate = d3UTCParse( dateParser );
	const dates = new Set( data.map( ( d ) => d.date ) );
	return [ ...dates ].sort( ( a, b ) => parseDate( a ) - parseDate( b ) );
};

/**
 * Check whether data is empty.
 *
 * @param {Array}  data      - the chart component's `data` prop.
 * @param {number} baseValue - base value to test data values against.
 * @return {boolean} `false` if there was at least one data value different than
 * the baseValue.
 */
export const isDataEmpty = ( data, baseValue = 0 ) => {
	for ( let i = 0; i < data.length; i++ ) {
		for ( const [ key, item ] of Object.entries( data[ i ] ) ) {
			if (
				key !== 'date' &&
				! isNil( item.value ) &&
				item.value !== baseValue
			) {
				return false;
			}
		}
	}

	return true;
};
