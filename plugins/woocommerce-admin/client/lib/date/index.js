/** @format */
/**
 * External dependencies
 */
import moment from 'moment';
import { find } from 'lodash';
import { __ } from '@wordpress/i18n';
import { date as datePHPFormat, getSettings } from '@wordpress/date';

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

// When `wp.date.setSettings()` is called on page initialization, PHP formats
// get saved to momentjs localeData. When react-dates attempts create date strings from
// those formats, moment isn't able to handle them. So lets convert those to moment strings.
// This logic should also live in Gutenberg. A PR will be made there.
function applyMomentFormats() {
	const settings = getSettings();
	const longDateFormats = moment.localeData()._longDateFormat;
	const momentLongDateFormats = Object.keys( longDateFormats ).reduce( ( formats, item ) => {
		if ( longDateFormats[ item ] ) {
			formats[ item ] = getMomentFormat( longDateFormats[ item ] );
		}
		return formats;
	}, {} );
	moment.updateLocale( settings.l10n.locale, {
		longDateFormat: momentLongDateFormats,
	} );
}

// i18n depends on this Gutenberg PR https://github.com/WordPress/gutenberg/pull/7542
// If it has been merged, we need to applyMomentFormats().
if ( ! moment.locale() ) {
	applyMomentFormats();
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

/**
 * Given two dates, derive a string representation
 *
 * @param {Moment} start - start date
 * @param {Moment} end - end date
 * @return {string} - text value for the supplied date range
 */
function getRangeLabel( start, end ) {
	const isSameDay = start.isSame( end, 'day' );
	const isSameYear = start.year() === end.year();
	const isSameMonth = isSameYear && start.month() === end.month();
	// If the date format in settings have Full Text Month (F), replace it wtih 3 letter abbreviations (M)
	const PHPFormat = getSettings().formats.date.replace( 'F', 'M' );
	if ( isSameDay ) {
		return datePHPFormat( PHPFormat, start );
	} else if ( isSameMonth ) {
		const startDate = start.date();
		return datePHPFormat( PHPFormat, start ).replace(
			startDate,
			`${ startDate } - ${ end.date() }`
		);
	} else if ( isSameYear ) {
		return `${ start.format( __( 'MMM D', 'woo-dash' ) ) } - ${ datePHPFormat( PHPFormat, end ) }`;
	}
	return `${ datePHPFormat( PHPFormat, start ) } - ${ datePHPFormat( PHPFormat, end ) }`;
}

/**
 * Get a DateValue object for a period prior to the current period.
 *
 * @param {string} period - the chosen period
 * @param {string} compare - `previous_period` or `previous_year`
 * @return {DateValue} -  DateValue data about the selected period
 */
function getLastPeriod( period, compare ) {
	const primaryStart = moment()
		.startOf( period )
		.subtract( 1, period );
	const primaryEnd = primaryStart.clone().endOf( period );
	let secondaryStart;
	let secondaryEnd;

	if ( 'previous_period' === compare ) {
		if ( 'year' === period ) {
			// Subtract two entire periods for years to take into account leap year
			secondaryStart = moment()
				.startOf( period )
				.subtract( 2, period );
			secondaryEnd = secondaryStart.clone().endOf( period );
		} else {
			// Otherwise, use days in primary period to figure out how far to go back
			const daysDiff = primaryEnd.diff( primaryStart, 'days' );
			secondaryEnd = primaryStart.clone().subtract( 1, 'days' );
			secondaryStart = secondaryEnd.clone().subtract( daysDiff, 'days' );
		}
	} else {
		secondaryStart =
			'week' === period
				? primaryStart
						.clone()
						.subtract( 1, 'years' )
						.week( primaryStart.week() )
						.startOf( 'week' )
				: primaryStart.clone().subtract( 1, 'years' );
		secondaryEnd = secondaryStart.clone().endOf( period );
	}
	return {
		primaryStart,
		primaryEnd,
		secondaryStart,
		secondaryEnd,
	};
}

/**
 * Get a DateValue object for a curent period. The period begins on the first day of the period,
 * and ends on the current day.
 *
 * @param {string} period - the chosen period
 * @param {string} compare - `previous_period` or `previous_year`
 * @return {DateValue} -  DateValue data about the selected period
 */
function getCurrentPeriod( period, compare ) {
	const primaryStart = moment().startOf( period );
	const primaryEnd = moment();
	const daysSoFar = primaryEnd.diff( primaryStart, 'days' );
	let secondaryStart;
	let secondaryEnd;

	if ( 'previous_period' === compare ) {
		secondaryStart = primaryStart.clone().subtract( 1, period );
		secondaryEnd = primaryEnd.clone().subtract( 1, period );
	} else {
		secondaryStart =
			'week' === period
				? primaryStart
						.clone()
						.subtract( 1, 'years' )
						.week( primaryStart.week() )
						.startOf( 'week' )
				: primaryStart.clone().subtract( 1, 'years' );
		secondaryEnd = secondaryStart.clone().add( daysSoFar, 'days' );
	}
	return {
		primaryStart,
		primaryEnd,
		secondaryStart,
		secondaryEnd,
	};
}

/**
 * Get a DateValue object for a period described by a period, compare value, and start/end
 * dates, for custom dates.
 *
 * @param {string} period - the chosen period
 * @param {string} compare - `previous_period` or `previous_year`
 * @param {Moment} [start] - start date if custom period
 * @param {Moment} [end] - end date if custom period
 * @return {DateValue} - DateValue data about the selected period
 */
function getDateValue( period, compare, start, end ) {
	switch ( period ) {
		case 'today':
			return getCurrentPeriod( 'day', compare );
		case 'yesterday':
			return getLastPeriod( 'day', compare );
		case 'week':
			return getCurrentPeriod( 'week', compare );
		case 'last_week':
			return getLastPeriod( 'week', compare );
		case 'month':
			return getCurrentPeriod( 'month', compare );
		case 'last_month':
			return getLastPeriod( 'month', compare );
		case 'quarter':
			return getCurrentPeriod( 'quarter', compare );
		case 'last_quarter':
			return getLastPeriod( 'quarter', compare );
		case 'year':
			return getCurrentPeriod( 'year', compare );
		case 'last_year':
			return getLastPeriod( 'year', compare );
		case 'custom':
			const difference = end.diff( start, 'days' );
			if ( 'previous_period' === compare ) {
				const secondaryEnd = start.clone().subtract( 1, 'days' );
				const secondaryStart = secondaryEnd.clone().subtract( difference, 'days' );
				return {
					primaryStart: start,
					primaryEnd: end,
					secondaryStart,
					secondaryEnd,
				};
			}
			return {
				primaryStart: start,
				primaryEnd: end,
				secondaryStart: start.clone().subtract( 1, 'years' ),
				secondaryEnd: end.clone().subtract( 1, 'years' ),
			};
	}
}

/**
 * Get Date Value Objects for a primary and secondary date range
 *
 * @param {string} period - Indicates period, 'last_week', 'quarter', or 'custom'
 * @param {string} compare - Indicates the period to compare against, 'previous_period', previous_year'
 * @param {moment.Moment} [start] - If the period supplied is "custom", this is the start date
 * @param {moment.Moment} [end] - If the period supplied is "custom", this is the end date
 * @return {{primary: DateValue, secondary: DateValue}} - Primary and secondary DateValue objects
 */
export const getCurrentDates = ( { period, compare, start, end } ) => {
	const { primaryStart, primaryEnd, secondaryStart, secondaryEnd } = getDateValue(
		period,
		compare,
		start,
		end
	);

	return {
		primary: {
			label: find( presetValues, item => item.value === period ).label,
			range: getRangeLabel( primaryStart, primaryEnd ),
			start: primaryStart,
			end: primaryEnd,
		},
		secondary: {
			label: find( compareValues, item => item.value === compare ).label,
			range: getRangeLabel( secondaryStart, secondaryEnd ),
			start: secondaryStart,
			end: secondaryEnd,
		},
	};
};
