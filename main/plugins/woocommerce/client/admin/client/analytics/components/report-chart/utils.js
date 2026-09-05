/**
 * External dependencies
 */
import { find, get } from 'lodash';
import { flattenFilters } from '@woocommerce/navigation';
import { format as formatDate } from '@wordpress/date';
import {
	containsLeapYear,
	getPreviousDate,
	isLeapYear,
} from '@woocommerce/date';

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
	const primarydataContainsLeapYear = dataContainsLeapYear( primaryData );
	const secondarydataContainsLeapYear = dataContainsLeapYear( secondaryData );
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

		const secondaryInterval = secondaryDataIntervals[ index ];
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

		if ( currentInterval === 'day' ) {
			if (
				primarydataContainsLeapYear &&
				! secondarydataContainsLeapYear &&
				secondaryDataIntervals?.[ index ]
			) {
				// Only fix the data if the date is in 29th Feb and secondary data is in 1st March,
				// which signifies incorrect comparison.
				const primaryDate = new Date( interval.date_start );
				const secondaryDate = new Date(
					secondaryDataIntervals[ index ].date_start
				);
				if (
					isLeapYear( primaryDate.getFullYear() ) &&
					primaryDate.getMonth() === 1 &&
					primaryDate.getDate() === 29 &&
					secondaryDate.getMonth() === 2 &&
					secondaryDate.getDate() === 1
				) {
					// This is going to be displayed as "Invalid date" label from D3.js, but desirable imo since
					// 29th February is not a valid date for non-leap years.
					secondaryLabelDate = '-';
					secondaryValue = 0;

					// Move the data up by 1 day for the missing leap day
					// so everything else is shifted to the right correctly.
					secondaryDataIntervals.splice(
						index,
						0,
						secondaryDataIntervals[ index ]
					);
				}
			} else if (
				! primarydataContainsLeapYear &&
				secondarydataContainsLeapYear
			) {
				// Todo: Do something about secondary data having leap year while first does not.
				// Currently, there are issues to render chart where primary data does not have the date since
				// the x-axis is based on primary data.
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
