/** @format */
/**
 * External dependencies
 */
import moment from 'moment';
import { find } from 'lodash';
import { __ } from '@wordpress/i18n';

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
export function getRangeLabel( start, end ) {
	const isSameDay = start.isSame( end, 'day' );
	const isSameYear = start.year() === end.year();
	const isSameMonth = isSameYear && start.month() === end.month();
	const fullDateFormat = __( 'MMM D, YYYY', 'woo-dash' );
	const monthDayFormat = __( 'MMM D', 'woo-dash' );

	if ( isSameDay ) {
		return start.format( fullDateFormat );
	} else if ( isSameMonth ) {
		const startDate = start.date();
		return start.format( fullDateFormat ).replace( startDate, `${ startDate } - ${ end.date() }` );
	} else if ( isSameYear ) {
		return `${ start.format( monthDayFormat ) } - ${ end.format( fullDateFormat ) }`;
	}
	return `${ start.format( fullDateFormat ) } - ${ end.format( fullDateFormat ) }`;
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
