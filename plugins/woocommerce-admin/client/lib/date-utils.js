/** @format */
/**
 * External dependencies
 */
import moment from 'moment';
import { find } from 'lodash';

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
			range: 'May 13 - 19, 2018',
			start: primaryStart,
			end: primaryEnd,
		},
		secondary: {
			label: compareValue.label,
			range: 'May 13 - 19, 2017',
			start: secondaryStart,
			end: secondaryEnd,
		},
	};
};
