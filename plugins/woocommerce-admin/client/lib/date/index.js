/** @format */
/**
 * External dependencies
 */
import moment from 'moment';
import { find } from 'lodash';
import { getSettings } from '@wordpress/date';

/**
 * Internal dependencies
 */
import { presetValues } from 'components/date-picker/preset-periods';
import { compareValues } from 'components/date-picker/compare-periods';

/**
 * DateValue Object
 *
 * @typedef {Object} DateValue - Describes the date range supplied by the date picker.
 * @property {string} label - The translated value of the period.
 * @property {string} range - The human readable value of a date range.
 * @property {moment.Moment} start - Start of the date range.
 * @property {moment.Moment} end - End of the date range.
 */

/**
 * <---------- MORE TO COME IN THE NEXT PR ----------->
 * Get Date Value Objects for a primary and secondary date range
 *
 * @param {string} period - Indicates period, 'last_week', 'quarter', or 'custom'
 * @param {string} compare - Indicates the period to compare against, 'previous_period', previous_year'
 * @param {moment.Moment} [start] - If the period supplied is "custom", this is the start date
 * @param {moment.Moment} [end] - If the period supplied is "custom", this is the end date
 * @return {{primary: DateValue, secondary: DateValue}} - Primary and secondary DateValue objects
 */
export const getDateValues = ( { period, compare, start, end } ) => {
	const presetValue = find( presetValues, item => item.value === period );
	const compareValue = find( compareValues, item => item.value === compare );

	let primaryStart = moment();
	let primaryEnd = moment();
	let secondaryStart = moment();
	let secondaryEnd = moment();

	switch ( period ) {
		case 'today':
			break;
		case 'yesterday':
			break;
		case 'week':
			break;
		case 'last_week':
			break;
		case 'month':
			break;
		case 'last_month':
			break;
		case 'quarter':
			break;
		case 'last_quarter':
			break;
		case 'year':
			break;
		case 'last_year':
			break;
		case 'custom':
			// For now...
			primaryStart = start;
			primaryEnd = end;
			secondaryStart = start.subtract( 1, 'years' );
			secondaryEnd = end.subtract( 1, 'years' );
			break;
	}
	return {
		primary: {
			label: presetValue.label,
			range: '<-- date str -->',
			start: primaryStart,
			end: primaryEnd,
		},
		secondary: {
			label: compareValue.label,
			range: '<-- date str -->',
			start: secondaryStart,
			end: secondaryEnd,
		},
	};
};

export const isoDateFormat = 'YYYY-MM-DD';

// This is temporary. This logic will be best addressed in Gutenberg's date package
// https://github.com/WordPress/gutenberg/blob/master/packages/date/src/index.js
// The following code is c/p from https://gist.github.com/phpmypython/f97c5f5f59f2a934599d
const formatMap = {
	d: 'DD',
	D: 'ddd',
	j: 'D',
	l: 'dddd',
	N: 'E',
	S: function() {
		return '[' + this.format( 'Do' ).replace( /\d*/g, '' ) + ']';
	},
	w: 'd',
	z: function() {
		return this.format( 'DDD' ) - 1;
	},
	W: 'W',
	F: 'MMMM',
	m: 'MM',
	M: 'MMM',
	n: 'M',
	t: function() {
		return this.daysInMonth();
	},
	L: function() {
		return this.isLeapYear() ? 1 : 0;
	},
	o: 'GGGG',
	Y: 'YYYY',
	y: 'YY',
	a: 'a',
	A: 'A',
	B: function() {
		const thisUTC = this.clone().utc(),
			// Shamelessly stolen from http://javascript.about.com/library/blswatch.htm
			swatch = ( thisUTC.hours() + 1 ) % 24 + thisUTC.minutes() / 60 + thisUTC.seconds() / 3600;
		return Math.floor( swatch * 1000 / 24 );
	},
	g: 'h',
	G: 'H',
	h: 'hh',
	H: 'HH',
	i: 'mm',
	s: 'ss',
	u: '[u]', // not sure if moment has this
	e: '[e]', // moment does not have this
	I: function() {
		return this.isDST() ? 1 : 0;
	},
	O: 'ZZ',
	P: 'Z',
	T: '[T]', // deprecated in moment
	Z: function() {
		return parseInt( this.format( 'ZZ' ), 10 ) * 36;
	},
	c: 'YYYY-MM-DD[T]HH:mm:ssZ',
	r: 'ddd, DD MMM YYYY HH:mm:ss ZZ',
	U: 'X',
};
const formatEx = /[dDjlNSwzWFmMntLoYyaABgGhHisueIOPTZcrU]/g;

function getMomentFormat( phpFormat ) {
	return phpFormat.replace( formatEx, function( phpStr ) {
		return typeof formatMap[ phpStr ] === 'function'
			? formatMap[ phpStr ].call( moment )
			: formatMap[ phpStr ];
	} );
}

/**
 * Convert a string to Moment object
 *
 * @param {string} str - localized date string
 * @return {Moment|null} - Moment object representing given string
 */
export function toMoment( str ) {
	if ( moment.isMoment( str ) ) {
		return str.isValid() ? str : null;
	}
	if ( 'string' === typeof str ) {
		const localeFormat = getMomentFormat( getSettings().formats.date );
		const date = moment( str, [ isoDateFormat, localeFormat ], true );
		return date.isValid ? date : null;
	}
	throw new Error( 'toMoment requires a string to be passed as an argument' );
}
