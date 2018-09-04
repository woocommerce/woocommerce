/** @format */
/**
 * External dependencies
 */
import moment from 'moment';
import { find } from 'lodash';
import { __ } from '@wordpress/i18n';
import { getSettings, format as formatDate } from '@wordpress/date';

/**
 * DateValue Object
 *
 * @typedef {Object} DateValue - Describes the date range supplied by the date picker.
 * @property {string} label - The translated value of the period.
 * @property {string} range - The human readable value of a date range.
 * @property {moment.Moment} after - Start of the date range.
 * @property {moment.Moment} before - End of the date range.
 */

/**
 * DateParams Object
 *
 * @typedef {Object} dateParams - date parameters derived from query parameters.
 * @property {string} period - period value, ie `last_week`
 * @property {string} compare - compare valuer, ie previous_year
 * @param {moment.Moment|null} after - If the period supplied is "custom", this is the after date
 * @param {moment.Moment|null} before - If the period supplied is "custom", this is the before date
 */

export const isoDateFormat = 'YYYY-MM-DD';

export const presetValues = [
	{ value: 'today', label: __( 'Today', 'wc-admin' ) },
	{ value: 'yesterday', label: __( 'Yesterday', 'wc-admin' ) },
	{ value: 'week', label: __( 'Week to Date', 'wc-admin' ) },
	{ value: 'last_week', label: __( 'Last Week', 'wc-admin' ) },
	{ value: 'month', label: __( 'Month to Date', 'wc-admin' ) },
	{ value: 'last_month', label: __( 'Last Month', 'wc-admin' ) },
	{ value: 'quarter', label: __( 'Quarter to Date', 'wc-admin' ) },
	{ value: 'last_quarter', label: __( 'Last Quarter', 'wc-admin' ) },
	{ value: 'year', label: __( 'Year to Date', 'wc-admin' ) },
	{ value: 'last_year', label: __( 'Last Year', 'wc-admin' ) },
	{ value: 'custom', label: __( 'Custom', 'wc-admin' ) },
];

export const periods = [
	{ value: 'previous_period', label: __( 'Previous Period', 'wc-admin' ) },
	{ value: 'previous_year', label: __( 'Previous Year', 'wc-admin' ) },
];

/**
 * Convert a string to Moment object
 *
 * @param {string} format - localized date string format
 * @param {string} str - date string
 * @return {Moment|null} - Moment object representing given string
 */
export function toMoment( format, str ) {
	if ( moment.isMoment( str ) ) {
		return str.isValid() ? str : null;
	}
	if ( 'string' === typeof str ) {
		const date = moment( str, [ isoDateFormat, format ], true );
		return date.isValid() ? date : null;
	}
	throw new Error( 'toMoment requires a string to be passed as an argument' );
}

/**
 * Given two dates, derive a string representation
 *
 * @param {Moment} after - start date
 * @param {Moment} before - end date
 * @return {string} - text value for the supplied date range
 */
export function getRangeLabel( after, before ) {
	const isSameDay = after.isSame( before, 'day' );
	const isSameYear = after.year() === before.year();
	const isSameMonth = isSameYear && after.month() === before.month();
	const fullDateFormat = __( 'MMM D, YYYY', 'wc-admin' );
	const monthDayFormat = __( 'MMM D', 'wc-admin' );

	if ( isSameDay ) {
		return after.format( fullDateFormat );
	} else if ( isSameMonth ) {
		const afterDate = after.date();
		return after
			.format( fullDateFormat )
			.replace( afterDate, `${ afterDate } - ${ before.date() }` );
	} else if ( isSameYear ) {
		return `${ after.format( monthDayFormat ) } - ${ before.format( fullDateFormat ) }`;
	}
	return `${ after.format( fullDateFormat ) } - ${ before.format( fullDateFormat ) }`;
}

/**
 * Get a DateValue object for a period prior to the current period.
 *
 * @param {string} period - the chosen period
 * @param {string} compare - `previous_period` or `previous_year`
 * @return {DateValue} -  DateValue data about the selected period
 */
export function getLastPeriod( period, compare ) {
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
export function getCurrentPeriod( period, compare ) {
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
 * @param {Moment} [after] - after date if custom period
 * @param {Moment} [before] - before date if custom period
 * @return {DateValue} - DateValue data about the selected period
 */
function getDateValue( period, compare, after, before ) {
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
			const difference = before.diff( after, 'days' );
			if ( 'previous_period' === compare ) {
				const secondaryEnd = after.clone().subtract( 1, 'days' );
				const secondaryStart = secondaryEnd.clone().subtract( difference, 'days' );
				return {
					primaryStart: after,
					primaryEnd: before,
					secondaryStart,
					secondaryEnd,
				};
			}
			return {
				primaryStart: after,
				primaryEnd: before,
				secondaryStart: after.clone().subtract( 1, 'years' ),
				secondaryEnd: before.clone().subtract( 1, 'years' ),
			};
	}
}

/**
 * Add default date-related parameters to a query object
 *
 * @param {string} [period] - period value, ie `last_week`
 * @param {string} [compare] - compare valuer, ie previous_year
 * @param {string} [after] - date in iso date format, ie 2018-07-03
 * @param {string} [before] - date in iso date format, ie 2018-07-03
 * @return {DateParams} - date parameters derived from query parameters with added defaults
 */
export const getDateParamsFromQuery = ( { period, compare, after, before } ) => {
	return {
		period: period || 'today',
		compare: compare || 'previous_period',
		after: after ? moment( after ) : null,
		before: before ? moment( before ) : null,
	};
};

/**
 * Get Date Value Objects for a primary and secondary date range
 *
 * @param {Object} query - date parameters derived from query parameters
 * @property {string} [period] - period value, ie `last_week`
 * @property {string} [compare] - compare valuer, ie previous_year
 * @property {string} [after] - date in iso date format, ie 2018-07-03
 * @property {string} [before] - date in iso date format, ie 2018-07-03
 * @return {{primary: DateValue, secondary: DateValue}} - Primary and secondary DateValue objects
 */
export const getCurrentDates = query => {
	const { period, compare, after, before } = getDateParamsFromQuery( query );
	const { primaryStart, primaryEnd, secondaryStart, secondaryEnd } = getDateValue(
		period,
		compare,
		after,
		before
	);

	return {
		primary: {
			label: find( presetValues, item => item.value === period ).label,
			range: getRangeLabel( primaryStart, primaryEnd ),
			after: primaryStart.format( isoDateFormat ),
			before: primaryEnd.format( isoDateFormat ),
		},
		secondary: {
			label: find( periods, item => item.value === compare ).label,
			range: getRangeLabel( secondaryStart, secondaryEnd ),
			after: secondaryStart.format( isoDateFormat ),
			before: secondaryEnd.format( isoDateFormat ),
		},
	};
};

/**
 * Calculates the date difference between two dates. Used in calculating a matching date for previous period.
 *
 * @param {String} date - Date to compare
 * @param {String} date2 - Seconary date to compare
 * @return {Int}  - Difference in days.
 */
export const getDateDifferenceInDays = ( date, date2 ) => {
	const _date = toMoment( isoDateFormat, formatDate( 'Y-m-d', date ) );
	const _date2 = toMoment( isoDateFormat, formatDate( 'Y-m-d', date2 ) );
	return _date.diff( _date2, 'days' );
};

/**
 * Get the previous date for either the previous period of year.
 *
 * @param {String} date - Base date
 * @param {String} date1 - primary start
 * @param {String} date2 - secondary start
 * @param {String} compare - `previous_period`  or `previous_year`
 * @param {String} interval - interval
 * @return {String}  - Calculated date
 */
export const getPreviousDate = ( date, date1, date2, compare, interval ) => {
	const dateMoment = toMoment( isoDateFormat, formatDate( 'Y-m-d', date ) );
	const _date1 = toMoment( isoDateFormat, formatDate( 'Y-m-d', date1 ) );
	const _date2 = toMoment( isoDateFormat, formatDate( 'Y-m-d', date2 ) );
	if ( 'previous_period' === compare ) {
		const difference = _date1.diff( _date2, interval );
		return dateMoment.clone().subtract( difference, interval );
	}
	return dateMoment.clone().subtract( 1, 'years' );
};

/**
 * Returns the allowed selectable intervals for a specific query.
 *
 * @param  {Object} query Current query
 * @return {Array} Array containing allowed intervals.
 */
export function getAllowedIntervalsForQuery( query ) {
	let allowed = [];
	if ( 'custom' === query.period ) {
		const { primary } = getCurrentDates( query );
		const differenceInDays = getDateDifferenceInDays( primary.before, primary.after );
		if ( differenceInDays > 728 ) {
			allowed = [ 'day', 'week', 'month', 'quarter', 'year' ];
		} else if ( differenceInDays > 364 ) {
			allowed = [ 'day', 'week', 'month', 'quarter' ];
		} else if ( differenceInDays > 90 ) {
			allowed = [ 'day', 'week', 'month' ];
		} else if ( differenceInDays > 7 ) {
			allowed = [ 'day', 'week' ];
		} else if ( differenceInDays > 1 && differenceInDays <= 7 ) {
			allowed = [ 'day' ];
		} else if ( differenceInDays <= 1 ) {
			allowed = [ 'hour' ];
		} else {
			allowed = [ 'day' ];
		}
	} else {
		switch ( query.period ) {
			case 'today':
			case 'yesterday':
				allowed = [ 'hour' ];
				break;
			case 'week':
			case 'last_week':
				allowed = [ 'day' ];
				break;
			case 'month':
			case 'last_month':
				allowed = [ 'day', 'week' ];
				break;
			case 'quarter':
			case 'last_quarter':
				allowed = [ 'day', 'week', 'month' ];
				break;
			case 'year':
			case 'last_year':
				allowed = [ 'day', 'week', 'month', 'quarter' ];
				break;
			default:
				allowed = [ 'day' ];
				break;
		}
	}
	return allowed;
}

/**
 * Returns the current interval to use.
 *
 * @param  {Object} query Current query
 * @return {String} Current interval.
 */
export function getIntervalForQuery( query ) {
	const allowed = getAllowedIntervalsForQuery( query );
	const defaultInterval = allowed[ 0 ];
	let current = query.interval || defaultInterval;
	if ( query.interval && ! allowed.includes( query.interval ) ) {
		current = defaultInterval;
	}

	return current;
}

/**
 * Returns date formats for the current interval.
 * See https://github.com/d3/d3-time-format for chart formats.
 *
 * @param  {String} interval Interval to get date formats for.
 * @return {String} Current interval.
 */
export function getDateFormatsForInterval( interval ) {
	let tooltipFormat = '%B %d %Y';
	let xFormat = '%Y-%m-%d';
	let tableFormat = 'm/d/Y';

	switch ( interval ) {
		case 'hour':
			tooltipFormat = '%I %p';
			xFormat = '%I %p';
			tableFormat = 'h A';
			break;
		case 'week':
			tooltipFormat = __( 'Week of %B %d %Y', 'wc-admin' );
			break;
		case 'quarter':
		case 'month':
			tooltipFormat = '%B %Y';
			xFormat = '%b %y';
			tableFormat = 'M Y';
			break;
		case 'year':
			tooltipFormat = '%Y';
			xFormat = '%Y';
			tableFormat = 'Y';
			break;
	}

	return {
		tooltipFormat,
		xFormat,
		tableFormat,
	};
}

/**
 * Gutenberg's moment instance is loaded with i18n values. If the locale isn't english
 * we can use that data and enhance it with additional translations
 */
export function loadLocaleData() {
	const { date } = wcSettings;
	const settings = getSettings();
	const userLocale = settings.l10n.locale;
	const { weekdaysShort } = settings.l10n;

	// Keep the default Momentjs English settings for any English
	if ( ! userLocale.match( /en_/ ) ) {
		moment.updateLocale( userLocale, {
			longDateFormat: {
				L: __( 'MM/DD/YYYY', 'wc-admin' ),
				LL: __( 'MMMM D, YYYY', 'wc-admin' ),
				LLL: __( 'D MMMM YYYY LT', 'wc-admin' ),
				LLLL: __( 'dddd, D MMMM YYYY LT', 'wc-admin' ),
				LT: __( 'HH:mm', 'wc-admin' ),
			},
			calendar: {
				lastDay: __( '[Yesterday at] LT', 'wc-admin' ),
				lastWeek: __( '[Last] dddd [at] LT', 'wc-admin' ),
				nextDay: __( '[Tomorrow at] LT', 'wc-admin' ),
				nextWeek: __( 'dddd [at] LT', 'wc-admin' ),
				sameDay: __( '[Today at] LT', 'wc-admin' ),
				sameElse: __( 'L', 'wc-admin' ),
			},
			week: {
				dow: Number( date.dow ),
			},
			weekdaysMin: weekdaysShort,
		} );
	}
}

loadLocaleData();

export const dateValidationMessages = {
	invalid: __( 'Invalid date', 'wc-admin' ),
	future: __( 'Select a date in the past', 'wc-admin' ),
	startAfterEnd: __( 'Start date must be before end date', 'wc-admin' ),
	endBeforeStart: __( 'Start date must be before end date', 'wc-admin' ),
};

/**
 * Validate text input supplied for a date range.
 *
 * @param {string} type - Designate begining or end of range, eg `before` or `after`.
 * @param {string} value - User input value
 * @param {Moment|null} [before] - If already designated, the before date parameter
 * @param {Moment|null} [after] - If already designated, the after date parameter
 * @param {string} format - The expected date format in a user's locale
 * @return {Object} validatedDate - validated date oject
 * @param {Moment|null} validatedDate.date - A resulting Moment date object or null, if invalid
 * @param {string} validatedDate.error - An optional error message if date is invalid
 */
export function validateDateInputForRange( type, value, before, after, format ) {
	const date = toMoment( format, value );
	if ( ! date ) {
		return {
			date: null,
			error: dateValidationMessages.invalid,
		};
	}
	if ( moment().isBefore( date, 'day' ) ) {
		return {
			date: null,
			error: dateValidationMessages.future,
		};
	}
	if ( 'after' === type && before && date.isAfter( before, 'day' ) ) {
		return {
			date: null,
			error: dateValidationMessages.startAfterEnd,
		};
	}
	if ( 'before' === type && after && date.isBefore( after, 'day' ) ) {
		return {
			date: null,
			error: dateValidationMessages.endBeforeStart,
		};
	}
	return { date };
}
