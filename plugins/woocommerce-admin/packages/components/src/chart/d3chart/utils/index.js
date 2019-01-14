/** @format */

/**
 * External dependencies
 */
import { find, get } from 'lodash';
import { format as d3Format } from 'd3-format';
import { line as d3Line } from 'd3-shape';
import moment from 'moment';

/**
 * Allows an overriding formatter or defaults to d3Format or d3TimeFormat
 * @param {string|function} format - either a format string for the D3 formatters or an overriding fomatting method
 * @param {function} formatter - default d3Format or another formatting method, which accepts the string `format`
 * @returns {function} to be used to format an input given the format and formatter
 */
export const getFormatter = ( format, formatter = d3Format ) =>
	typeof format === 'function' ? format : formatter( format );

/**
 * Describes `getUniqueKeys`
 * @param {array} data - The chart component's `data` prop.
 * @returns {array} of unique category keys
 */
export const getUniqueKeys = data => {
	return [
		...new Set(
			data.reduce( ( accum, curr ) => {
				Object.keys( curr ).forEach( key => key !== 'date' && accum.push( key ) );
				return accum;
			}, [] )
		),
	];
};

/**
 * Describes `getOrderedKeys`
 * @param {array} data - The chart component's `data` prop.
 * @param {array} uniqueKeys - from `getUniqueKeys`.
 * @returns {array} of unique category keys ordered by cumulative total value
 */
export const getOrderedKeys = ( data, uniqueKeys ) =>
	uniqueKeys
		.map( key => ( {
			key,
			focus: true,
			total: data.reduce( ( a, c ) => a + c[ key ].value, 0 ),
			visible: true,
		} ) )
		.sort( ( a, b ) => b.total - a.total );

/**
 * Describes `getLineData`
 * @param {array} data - The chart component's `data` prop.
 * @param {array} orderedKeys - from `getOrderedKeys`.
 * @returns {array} an array objects with a category `key` and an array of `values` with `date` and `value` properties
 */
export const getLineData = ( data, orderedKeys ) =>
	orderedKeys.map( row => ( {
		key: row.key,
		focus: row.focus,
		visible: row.visible,
		values: data.map( d => ( {
			date: d.date,
			focus: row.focus,
			label: get( d, [ row.key, 'label' ], '' ),
			value: get( d, [ row.key, 'value' ], 0 ),
			visible: row.visible,
		} ) ),
	} ) );

/**
 * Describes `getUniqueDates`
 * @param {array} lineData - from `GetLineData`
 * @param {function} parseDate - D3 time format parser
 * @returns {array} an array of unique date values sorted from earliest to latest
 */
export const getUniqueDates = ( lineData, parseDate ) => {
	return [
		...new Set(
			lineData.reduce( ( accum, { values } ) => {
				values.forEach( ( { date } ) => accum.push( date ) );
				return accum;
			}, [] )
		),
	].sort( ( a, b ) => parseDate( a ) - parseDate( b ) );
};

/**
 * Describes getLine
 * @param {function} xLineScale - from `getXLineScale`.
 * @param {function} yScale - from `getYScale`.
 * @returns {function} the D3 line function for plotting all category values
 */
export const getLine = ( xLineScale, yScale ) =>
	d3Line()
		.x( d => xLineScale( moment( d.date ).toDate() ) )
		.y( d => yScale( d.value ) );

/**
 * Describes getDateSpaces
 * @param {array} data - The chart component's `data` prop.
 * @param {array} uniqueDates - from `getUniqueDates`
 * @param {number} width - calculated width of the charting space
 * @param {function} xLineScale - from `getXLineScale`
 * @returns {array} that icnludes the date, start (x position) and width to mode the mouseover rectangles
 */
export const getDateSpaces = ( data, uniqueDates, width, xLineScale ) =>
	uniqueDates.map( ( d, i ) => {
		const datapoints = find( data, { date: d } );
		const xNow = xLineScale( moment( d ).toDate() );
		const xPrev =
			i >= 1
				? xLineScale( moment( uniqueDates[ i - 1 ] ).toDate() )
				: xLineScale( moment( uniqueDates[ 0 ] ).toDate() );
		const xNext =
			i < uniqueDates.length - 1
				? xLineScale( moment( uniqueDates[ i + 1 ] ).toDate() )
				: xLineScale( moment( uniqueDates[ uniqueDates.length - 1 ] ).toDate() );
		let xWidth = i === 0 ? xNext - xNow : xNow - xPrev;
		const xStart = i === 0 ? 0 : xNow - xWidth / 2;
		xWidth = i === 0 || i === uniqueDates.length - 1 ? xWidth / 2 : xWidth;
		return {
			date: d,
			start: uniqueDates.length > 1 ? xStart : 0,
			width: uniqueDates.length > 1 ? xWidth : width,
			values: Object.keys( datapoints )
				.filter( key => key !== 'date' )
				.map( key => {
					return {
						key,
						value: datapoints[ key ].value,
						date: d,
					};
				} ),
		};
	} );
