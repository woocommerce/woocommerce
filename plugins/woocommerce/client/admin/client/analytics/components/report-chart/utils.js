/**
 * External dependencies
 */
import { find, get } from 'lodash';
import moment from 'moment';
import { flattenFilters } from '@woocommerce/navigation';
import { format as formatDate } from '@wordpress/date';
import { containsLeapYear, getPreviousDate } from '@woocommerce/date';

export const DEFAULT_FILTER = 'all';

export function getSelectedFilter( filters, query, selectedFilterArgs = {} ) {
	if ( ! filters || filters.length === 0 ) {
		return null;
	}

	const clonedFilters = filters.slice( 0 );
	const filterConfig = clonedFilters.pop();

	if ( filterConfig.showFilters( query, selectedFilterArgs ) ) {
		const allFilters = flattenFilters( filterConfig.filters );
		const value =
			query[ filterConfig.param ] ||
			filterConfig.defaultValue ||
			DEFAULT_FILTER;
		return find( allFilters, { value } );
	}

	return getSelectedFilter( clonedFilters, query, selectedFilterArgs );
}

export function getChartMode( selectedFilter, query ) {
	if ( selectedFilter && query ) {
		const selectedFilterParam = get( selectedFilter, [
			'settings',
			'param',
		] );

		if (
			! selectedFilterParam ||
			Object.keys( query ).includes( selectedFilterParam )
		) {
			return get( selectedFilter, [ 'chartMode' ] );
		}
	}

	return null;
}

export function createDateFormatter( format ) {
	return ( date ) => formatDate( format, date );
}

/**
 * Returns true if the data contains a leap year.
 *
 * @param {Object} data Chart interval data
 * @return {boolean} True if data contains a leap year.
 */
export function dataContainsLeapYear( data ) {
	if ( data?.data?.intervals?.length > 1 ) {
		const start = data.data.intervals[ 0 ].date_start;
		const end =
			data.data.intervals[ data.data.intervals.length - 1 ].date_end;

		if ( containsLeapYear( start, end ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Returns true if the date is the 29th of February.
 *
 * @param {moment.Moment} date Date to check
 * @return {boolean} True if the date is a leap day.
 */
function isLeapDay( date ) {
	return date.month() === 1 && date.date() === 29;
}

/**
 * Returns true if the date is the 1st of March.
 *
 * @param {moment.Moment} date Date to check
 * @return {boolean} True if the date is the first of March.
 */
function isFirstOfMarch( date ) {
	return date.month() === 2 && date.date() === 1;
}

/**
 * Builds chart data for the given parameters.
 *
 * @param {Object} primaryData         Primary data
 * @param {Object} secondaryData       Secondary data
 * @param {Object} primaryDatePicker   DataPickerOptions object for primary data
 * @param {Object} secondaryDatePicker DataPickerOptions object for secondary data
 * @param {string} comparison          Comparison type, e.x: `previous_year`
 * @param {string} selectedChartKey    Chart key, e.x: `orders_count`
 * @param {string} currentInterval     Chart interval, e.x: `day`
 * @return {Object} Chart data
 */
export function buildChartData(
	primaryData,
	secondaryData,
	primaryDatePicker,
	secondaryDatePicker,
	comparison,
	selectedChartKey,
	currentInterval
) {
	const primaryDataIntervals = [ ...primaryData.data.intervals ];
	const secondaryDataIntervals = [ ...secondaryData.data.intervals ];

	const chartData = [];

	for ( let index = 0; index < primaryDataIntervals.length; index++ ) {
		const interval = primaryDataIntervals[ index ];

		const primaryDateFormatted = formatDate(
			'Y-m-d\\TH:i:s',
			interval.date_start
		);
		const primaryLabel = `${ primaryDatePicker.label } (${ primaryDatePicker.range })`;
		const primaryLabelDate = interval.date_start;
		const primaryValue = interval.subtotals[ selectedChartKey ] || 0;

		let secondaryInterval = secondaryDataIntervals[ index ];
		const secondaryLabel = `${ secondaryDatePicker.label } (${ secondaryDatePicker.range })`;

		const secondaryDateMoment = getPreviousDate(
			interval.date_start,
			primaryDatePicker.after,
			secondaryDatePicker.after,
			comparison,
			currentInterval
		);
		let secondaryLabelDate = secondaryDateMoment.format(
			'YYYY-MM-DD HH:mm:ss'
		);
		let secondaryValue =
			( secondaryInterval &&
				secondaryInterval.subtotals[ selectedChartKey ] ) ||
			0;

		if ( currentInterval === 'day' && secondaryInterval ) {
			const primaryDate = moment( interval.date_start );
			const secondaryDate = moment( secondaryInterval.date_start );

			if ( isLeapDay( primaryDate ) && isFirstOfMarch( secondaryDate ) ) {
				// The secondary range has no leap day, so its intervals run one short
				// from here on. Pad it with a blank slot so later days line up again.
				// The label renders as "Invalid date", which is desirable since
				// 29th February is not a valid date for non-leap years.
				secondaryLabelDate = '-';
				secondaryValue = 0;
				secondaryDataIntervals.splice( index, 0, secondaryInterval );
			} else if (
				isLeapDay( secondaryDate ) &&
				isFirstOfMarch( primaryDate )
			) {
				// The secondary range has a leap day the primary range lacks, so its
				// intervals run one long from here on. The x-axis is built from the
				// primary dates, so the leap day has no slot to render in: drop it
				// so later days line up again.
				secondaryDataIntervals.splice( index, 1 );
				secondaryInterval = secondaryDataIntervals[ index ];
				secondaryValue =
					( secondaryInterval &&
						secondaryInterval.subtotals[ selectedChartKey ] ) ||
					0;
			}
		}

		chartData.push( {
			date: primaryDateFormatted,
			primary: {
				label: primaryLabel,
				labelDate: primaryLabelDate,
				value: primaryValue,
			},
			secondary: {
				label: secondaryLabel,
				labelDate: secondaryLabelDate,
				value: secondaryValue,
			},
		} );
	}

	return chartData;
}
