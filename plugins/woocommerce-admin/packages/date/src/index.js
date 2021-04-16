/**
 * External dependencies
 */
import moment from 'moment';
import { find, memoize } from 'lodash';
import { __ } from '@wordpress/i18n';
import { parse } from 'qs';

export const isoDateFormat = 'YYYY-MM-DD';

export const defaultDateTimeFormat = 'YYYY-MM-DDTHH:mm:ss';

/**
 * DateValue Object
 *
 * @typedef  {Object} DateValue - Describes the date range supplied by the date picker.
 * @property {string} label - The translated value of the period.
 * @property {string} range - The human readable value of a date range.
 * @property {moment.Moment} after - Start of the date range.
 * @property {moment.Moment} before - End of the date range.
 */

/**
 * DateParams Object
 *
 * @typedef {Object} DateParams - date parameters derived from query parameters.
 * @property {string} period - period value, ie `last_week`
 * @property {string} compare - compare valuer, ie previous_year
 * @param {moment.Moment|null} after - If the period supplied is "custom", this is the after date
 * @param {moment.Moment|null} before - If the period supplied is "custom", this is the before date
 */

export const presetValues = [
	{ value: 'today', label: __( 'Today', 'woocommerce-admin' ) },
	{ value: 'yesterday', label: __( 'Yesterday', 'woocommerce-admin' ) },
	{ value: 'week', label: __( 'Week to Date', 'woocommerce-admin' ) },
	{ value: 'last_week', label: __( 'Last Week', 'woocommerce-admin' ) },
	{ value: 'month', label: __( 'Month to Date', 'woocommerce-admin' ) },
	{ value: 'last_month', label: __( 'Last Month', 'woocommerce-admin' ) },
	{ value: 'quarter', label: __( 'Quarter to Date', 'woocommerce-admin' ) },
	{ value: 'last_quarter', label: __( 'Last Quarter', 'woocommerce-admin' ) },
	{ value: 'year', label: __( 'Year to Date', 'woocommerce-admin' ) },
	{ value: 'last_year', label: __( 'Last Year', 'woocommerce-admin' ) },
	{ value: 'custom', label: __( 'Custom', 'woocommerce-admin' ) },
];

export const periods = [
	{
		value: 'previous_period',
		label: __( 'Previous Period', 'woocommerce-admin' ),
	},
	{
		value: 'previous_year',
		label: __( 'Previous Year', 'woocommerce-admin' ),
	},
];

/**
 * Adds timestamp to a string date.
 *
 * @param {moment.Moment} date - Date as a moment object.
 * @param {string} timeOfDay - Either `start`, `now` or `end` of the day.
 * @return {string} - String date with timestamp attached.
 */
export const appendTimestamp = ( date, timeOfDay ) => {
	if ( timeOfDay === 'start' ) {
		return date.startOf( 'day' ).format( defaultDateTimeFormat );
	}
	if ( timeOfDay === 'now' ) {
		// Set seconds to 00 to avoid consecutives calls happening before the previous
		// one finished.
		return date.format( defaultDateTimeFormat );
	}
	if ( timeOfDay === 'end' ) {
		return date.endOf( 'day' ).format( defaultDateTimeFormat );
	}
	throw new Error(
		'appendTimestamp requires second parameter to be either `start`, `now` or `end`'
	);
};

/**
 * Convert a string to Moment object
 *
 * @param {string} format - localized date string format
 * @param {string} str - date string
 * @return {Object|null} - Moment object representing given string
 */
export function toMoment( format, str ) {
	if ( moment.isMoment( str ) ) {
		return str.isValid() ? str : null;
	}
	if ( typeof str === 'string' ) {
		const date = moment( str, [ isoDateFormat, format ], true );
		return date.isValid() ? date : null;
	}
	throw new Error( 'toMoment requires a string to be passed as an argument' );
}

/**
 * Given two dates, derive a string representation
 *
 * @param {Object} after - start date
 * @param {Object} before - end date
 * @return {string} - text value for the supplied date range
 */
export function getRangeLabel( after, before ) {
	const isSameYear = after.year() === before.year();
	const isSameMonth = isSameYear && after.month() === before.month();
	const isSameDay =
		isSameYear && isSameMonth && after.isSame( before, 'day' );
	const fullDateFormat = __( 'MMM D, YYYY', 'woocommerce-admin' );

	if ( isSameDay ) {
		return after.format( fullDateFormat );
	} else if ( isSameMonth ) {
		const afterDate = after.date();
		return after
			.format( fullDateFormat )
			.replace( afterDate, `${ afterDate } - ${ before.date() }` );
	} else if ( isSameYear ) {
		const monthDayFormat = __( 'MMM D', 'woocommerce-admin' );
		return `${ after.format( monthDayFormat ) } - ${ before.format(
			fullDateFormat
		) }`;
	}
	return `${ after.format( fullDateFormat ) } - ${ before.format(
		fullDateFormat
	) }`;
}

/**
 * Gets the current time in the store time zone if set.
 *
 * @return {string} - Datetime string.
 */
export function getStoreTimeZoneMoment() {
	if ( ! window.wcSettings || ! window.wcSettings.timeZone ) {
		return moment();
	}

	if ( [ '+', '-' ].includes( window.wcSettings.timeZone.charAt( 0 ) ) ) {
		return moment().utcOffset( window.wcSettings.timeZone );
	}

	return moment().tz( window.wcSettings.timeZone );
}

/**
 * Get a DateValue object for a period prior to the current period.
 *
 * @param {string} period - the chosen period
 * @param {string} compare - `previous_period` or `previous_year`
 * @return {DateValue} -  DateValue data about the selected period
 */
export function getLastPeriod( period, compare ) {
	const primaryStart = getStoreTimeZoneMoment()
		.startOf( period )
		.subtract( 1, period );
	const primaryEnd = primaryStart.clone().endOf( period );
	let secondaryStart;
	let secondaryEnd;

	if ( compare === 'previous_period' ) {
		if ( period === 'year' ) {
			// Subtract two entire periods for years to take into account leap year
			secondaryStart = moment().startOf( period ).subtract( 2, period );
			secondaryEnd = secondaryStart.clone().endOf( period );
		} else {
			// Otherwise, use days in primary period to figure out how far to go back
			// This is necessary for calculating weeks instead of using `endOf`.
			const daysDiff = primaryEnd.diff( primaryStart, 'days' );
			secondaryEnd = primaryStart.clone().subtract( 1, 'days' );
			secondaryStart = secondaryEnd.clone().subtract( daysDiff, 'days' );
		}
	} else {
		secondaryStart = primaryStart.clone().subtract( 1, 'years' );
		secondaryEnd = primaryEnd.clone().subtract( 1, 'years' );
	}

	// When the period is month, be sure to force end of month to take into account leap year
	if ( period === 'month' ) {
		secondaryEnd = secondaryEnd.clone().endOf( 'month' );
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
	const primaryStart = getStoreTimeZoneMoment().startOf( period );
	const primaryEnd = getStoreTimeZoneMoment();

	const daysSoFar = primaryEnd.diff( primaryStart, 'days' );
	let secondaryStart;
	let secondaryEnd;

	if ( compare === 'previous_period' ) {
		secondaryStart = primaryStart.clone().subtract( 1, period );
		secondaryEnd = primaryEnd.clone().subtract( 1, period );
	} else {
		secondaryStart = primaryStart.clone().subtract( 1, 'years' );
		// Set the end time to 23:59:59.
		secondaryEnd = secondaryStart
			.clone()
			.add( daysSoFar + 1, 'days' )
			.subtract( 1, 'seconds' );
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
 * @param {Object} [after] - after date if custom period
 * @param {Object} [before] - before date if custom period
 * @return {DateValue} - DateValue data about the selected period
 */
const getDateValue = memoize(
	( period, compare, after, before ) => {
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
				if ( compare === 'previous_period' ) {
					const secondaryEnd = after.clone().subtract( 1, 'days' );
					const secondaryStart = secondaryEnd
						.clone()
						.subtract( difference, 'days' );
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
	},
	( period, compare, after, before ) =>
		[
			period,
			compare,
			after && after.format(),
			before && before.format(),
		].join( ':' )
);

/**
 * Memoized internal logic of getDateParamsFromQuery().
 *
 * @param {string} period - period value, ie `last_week`
 * @param {string} compare - compare value, ie `previous_year`
 * @param {string} after - date in iso date format, ie `2018-07-03`
 * @param {string} before - date in iso date format, ie `2018-07-03`
 * @param {string} defaultDateRange - the store's default date range
 * @return {Object} - date parameters derived from query parameters with added defaults
 */
const getDateParamsFromQueryMemoized = memoize(
	( period, compare, after, before, defaultDateRange ) => {
		if ( period && compare ) {
			return {
				period,
				compare,
				after: after ? moment( after ) : null,
				before: before ? moment( before ) : null,
			};
		}
		const queryDefaults = parse(
			defaultDateRange.replace( /&amp;/g, '&' )
		);

		return {
			period: queryDefaults.period,
			compare: queryDefaults.compare,
			after: queryDefaults.after ? moment( queryDefaults.after ) : null,
			before: queryDefaults.before
				? moment( queryDefaults.before )
				: null,
		};
	},
	( period, compare, after, before, defaultDateRange ) =>
		[ period, compare, after, before, defaultDateRange ].join( ':' )
);

/**
 * Add default date-related parameters to a query object
 *
 * @param {Object} query - query object
 * @param {string} query.period - period value, ie `last_week`
 * @param {string} query.compare - compare value, ie `previous_year`
 * @param {string} query.after - date in iso date format, ie `2018-07-03`
 * @param {string} query.before - date in iso date format, ie `2018-07-03`
 * @param {string} defaultDateRange - the store's default date range
 * @return {DateParams} - date parameters derived from query parameters with added defaults
 */
export const getDateParamsFromQuery = (
	query,
	defaultDateRange = 'period=month&compare=previous_year'
) => {
	const { period, compare, after, before } = query;

	return getDateParamsFromQueryMemoized(
		period,
		compare,
		after,
		before,
		defaultDateRange
	);
};

/**
 * Memoized internal logic of getCurrentDates().
 *
 * @param {string} period - period value, ie `last_week`
 * @param {string} compare - compare value, ie `previous_year`
 * @param {Object} primaryStart - primary query start DateTime, in Moment instance.
 * @param {Object} primaryEnd - primary query start DateTime, in Moment instance.
 * @param {Object} secondaryStart - primary query start DateTime, in Moment instance.
 * @param {Object} secondaryEnd - primary query start DateTime, in Moment instance.
 * @return {{primary: DateValue, secondary: DateValue}} - Primary and secondary DateValue objects
 */
const getCurrentDatesMemoized = memoize(
	(
		period,
		compare,
		primaryStart,
		primaryEnd,
		secondaryStart,
		secondaryEnd
	) => ( {
		primary: {
			label: find( presetValues, ( item ) => item.value === period )
				.label,
			range: getRangeLabel( primaryStart, primaryEnd ),
			after: primaryStart,
			before: primaryEnd,
		},
		secondary: {
			label: find( periods, ( item ) => item.value === compare ).label,
			range: getRangeLabel( secondaryStart, secondaryEnd ),
			after: secondaryStart,
			before: secondaryEnd,
		},
	} ),
	(
		period,
		compare,
		primaryStart,
		primaryEnd,
		secondaryStart,
		secondaryEnd
	) =>
		[
			period,
			compare,
			primaryStart && primaryStart.format(),
			primaryEnd && primaryEnd.format(),
			secondaryStart && secondaryStart.format(),
			secondaryEnd && secondaryEnd.format(),
		].join( ':' )
);

/**
 * Get Date Value Objects for a primary and secondary date range
 *
 * @param {Object} query - query object
 * @param {string} query.period - period value, ie `last_week`
 * @param {string} query.compare - compare value, ie `previous_year`
 * @param {string} query.after - date in iso date format, ie `2018-07-03`
 * @param {string} query.before - date in iso date format, ie `2018-07-03`
 * @param {string} defaultDateRange - the store's default date range
 * @return {{primary: DateValue, secondary: DateValue}} - Primary and secondary DateValue objects
 */
export const getCurrentDates = (
	query,
	defaultDateRange = 'period=month&compare=previous_year'
) => {
	const { period, compare, after, before } = getDateParamsFromQuery(
		query,
		defaultDateRange
	);
	const {
		primaryStart,
		primaryEnd,
		secondaryStart,
		secondaryEnd,
	} = getDateValue( period, compare, after, before );

	return getCurrentDatesMemoized(
		period,
		compare,
		primaryStart,
		primaryEnd,
		secondaryStart,
		secondaryEnd
	);
};

/**
 * Calculates the date difference between two dates. Used in calculating a matching date for previous period.
 *
 * @param {string} date - Date to compare
 * @param {string} date2 - Seconary date to compare
 * @return {number}  - Difference in days.
 */
export const getDateDifferenceInDays = ( date, date2 ) => {
	const _date = moment( date );
	const _date2 = moment( date2 );
	return _date.diff( _date2, 'days' );
};

/**
 * Get the previous date for either the previous period of year.
 *
 * @param {string} date - Base date
 * @param {string} date1 - primary start
 * @param {string} date2 - secondary start
 * @param {string} compare - `previous_period`  or `previous_year`
 * @param {string} interval - interval
 * @return {Object}  - Calculated date
 */
export const getPreviousDate = ( date, date1, date2, compare, interval ) => {
	const dateMoment = moment( date );

	if ( compare === 'previous_year' ) {
		return dateMoment.clone().subtract( 1, 'years' );
	}

	const _date1 = moment( date1 );
	const _date2 = moment( date2 );
	const difference = _date1.diff( _date2, interval );

	return dateMoment.clone().subtract( difference, interval );
};

/**
 * Returns the allowed selectable intervals for a specific query.
 *
 * @param  {Object} query Current query
 * @return {Array} Array containing allowed intervals.
 */
export function getAllowedIntervalsForQuery( query ) {
	let allowed = [];
	if ( query.period === 'custom' ) {
		const { primary } = getCurrentDates( query );
		const differenceInDays = getDateDifferenceInDays(
			primary.before,
			primary.after
		);
		if ( differenceInDays >= 365 ) {
			allowed = [ 'day', 'week', 'month', 'quarter', 'year' ];
		} else if ( differenceInDays >= 90 ) {
			allowed = [ 'day', 'week', 'month', 'quarter' ];
		} else if ( differenceInDays >= 28 ) {
			allowed = [ 'day', 'week', 'month' ];
		} else if ( differenceInDays >= 7 ) {
			allowed = [ 'day', 'week' ];
		} else if ( differenceInDays > 1 && differenceInDays < 7 ) {
			allowed = [ 'day' ];
		} else {
			allowed = [ 'hour', 'day' ];
		}
	} else {
		switch ( query.period ) {
			case 'today':
			case 'yesterday':
				allowed = [ 'hour', 'day' ];
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
 * @return {string} Current interval.
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
 * Returns the current chart type to use.
 *
 * @param {Object} query Current query
 * @param {string} query.chartType
 * @return {string} Current chart type.
 */
export function getChartTypeForQuery( { chartType } ) {
	if ( [ 'line', 'bar' ].includes( chartType ) ) {
		return chartType;
	}
	return 'line';
}

export const dayTicksThreshold = 63;
export const weekTicksThreshold = 9;
export const defaultTableDateFormat = 'm/d/Y';

/**
 * Returns date formats for the current interval.
 * See https://github.com/d3/d3-time-format for chart formats.
 *
 * @param  {string} interval Interval to get date formats for.
 * @param  {number}    [ticks] Number of ticks the axis will have.
 * @return {string} Current interval.
 */
export function getDateFormatsForInterval( interval, ticks = 0 ) {
	let screenReaderFormat = '%B %-d, %Y';
	let tooltipLabelFormat = '%B %-d, %Y';
	let xFormat = '%Y-%m-%d';
	let x2Format = '%b %Y';
	let tableFormat = defaultTableDateFormat;

	switch ( interval ) {
		case 'hour':
			screenReaderFormat = '%_I%p %B %-d, %Y';
			tooltipLabelFormat = '%_I%p %b %-d, %Y';
			xFormat = '%_I%p';
			x2Format = '%b %-d, %Y';
			tableFormat = 'h A';
			break;
		case 'day':
			if ( ticks < dayTicksThreshold ) {
				xFormat = '%-d';
			} else {
				xFormat = '%b';
				x2Format = '%Y';
			}
			break;
		case 'week':
			if ( ticks < weekTicksThreshold ) {
				xFormat = '%-d';
				x2Format = '%b %Y';
			} else {
				xFormat = '%b';
				x2Format = '%Y';
			}
			screenReaderFormat = __(
				'Week of %B %-d, %Y',
				'woocommerce-admin'
			);
			tooltipLabelFormat = __(
				'Week of %B %-d, %Y',
				'woocommerce-admin'
			);
			break;
		case 'quarter':
		case 'month':
			screenReaderFormat = '%B %Y';
			tooltipLabelFormat = '%B %Y';
			xFormat = '%b';
			x2Format = '%Y';
			break;
		case 'year':
			screenReaderFormat = '%Y';
			tooltipLabelFormat = '%Y';
			xFormat = '%Y';
			break;
	}

	return {
		screenReaderFormat,
		tooltipLabelFormat,
		xFormat,
		x2Format,
		tableFormat,
	};
}

/**
 * Gutenberg's moment instance is loaded with i18n values, which are
 * PHP date formats, ie 'LLL: "F j, Y g:i a"'. Override those with translations
 * of moment style js formats.
 *
 * @param {Object} config Locale config object, from store settings.
 * @param {string} config.userLocale
 * @param {Array} config.weekdaysShort
 */
export function loadLocaleData( { userLocale, weekdaysShort } ) {
	// Don't update if the wp locale hasn't been set yet, like in unit tests, for instance.
	if ( moment.locale() !== 'en' ) {
		moment.updateLocale( userLocale, {
			longDateFormat: {
				L: __( 'MM/DD/YYYY', 'woocommerce-admin' ),
				LL: __( 'MMMM D, YYYY', 'woocommerce-admin' ),
				LLL: __( 'D MMMM YYYY LT', 'woocommerce-admin' ),
				LLLL: __( 'dddd, D MMMM YYYY LT', 'woocommerce-admin' ),
				LT: __( 'HH:mm', 'woocommerce-admin' ),
			},
			weekdaysMin: weekdaysShort,
		} );
	}
}

export const dateValidationMessages = {
	invalid: __( 'Invalid date', 'woocommerce-admin' ),
	future: __( 'Select a date in the past', 'woocommerce-admin' ),
	startAfterEnd: __(
		'Start date must be before end date',
		'woocommerce-admin'
	),
	endBeforeStart: __(
		'Start date must be before end date',
		'woocommerce-admin'
	),
};

/**
 * @typedef {Object} validatedDate
 * @property {Object|null} date - A resulting Moment date object or null, if invalid
 * @property {string} error - An optional error message if date is invalid
 */

/**
 * Validate text input supplied for a date range.
 *
 * @param {string} type - Designate beginning or end of range, eg `before` or `after`.
 * @param {string} value - User input value
 * @param {Object|null} [before] - If already designated, the before date parameter
 * @param {Object|null} [after] - If already designated, the after date parameter
 * @param {string} format - The expected date format in a user's locale
 * @return {Object} validatedDate - validated date object
 */
export function validateDateInputForRange(
	type,
	value,
	before,
	after,
	format
) {
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
	if ( type === 'after' && before && date.isAfter( before, 'day' ) ) {
		return {
			date: null,
			error: dateValidationMessages.startAfterEnd,
		};
	}
	if ( type === 'before' && after && date.isBefore( after, 'day' ) ) {
		return {
			date: null,
			error: dateValidationMessages.endBeforeStart,
		};
	}
	return { date };
}
