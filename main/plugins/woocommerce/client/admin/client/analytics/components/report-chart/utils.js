/**
 * External dependencies
 */
import { find, get } from 'lodash';
import moment from 'moment';
import { flattenFilters } from '@woocommerce/navigation';
import { format as formatDate } from '@wordpress/date';
import { getPreviousDate } from '@woocommerce/date';

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
 * Returns true if the values of a chart metric can be added up across days.
 * Averages and percentages cannot. Core names averages `avg_*` and types most
 * of them as `average`, but `avg_order_value` is typed as `currency` for
 * formatting, so both signals are checked.
 *
 * @param {string} key  Chart key, e.x: `orders_count`
 * @param {string} type Chart type, e.x: `number`
 * @return {boolean} True if the values can be summed.
 */
function isAdditiveMetric( key, type ) {
	return (
		type !== 'average' && type !== 'percent' && ! key.startsWith( 'avg_' )
	);
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
 * @param {string} selectedChartType   Chart type, e.x: `number`
 * @return {Object} Chart data
 */
export function buildChartData(
	primaryData,
	secondaryData,
	primaryDatePicker,
	secondaryDatePicker,
	comparison,
	selectedChartKey,
	currentInterval,
	selectedChartType
) {
	const primaryDataIntervals = primaryData.data.intervals;
	const secondaryDataIntervals = secondaryData.data.intervals;
	const shift = secondaryDatePicker.shift;
	const yearShifted = shift
		? shift === 'year'
		: comparison === 'previous_year';

	// Day intervals are matched by date, so a secondary range that is a leap
	// day longer or shorter than the primary one cannot push later days out of
	// line. Coarser intervals do not start on comparable dates (a week starts
	// on its own weekday), so those keep matching by position.
	const matchByDate = currentInterval === 'day';
	const secondaryIntervalsByDate = new Map(
		matchByDate
			? secondaryDataIntervals.map( ( secondaryInterval ) => [
					moment( secondaryInterval.date_start ).format(
						'YYYY-MM-DD'
					),
					secondaryInterval,
			  ] )
			: []
	);

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

		const secondaryLabel = `${ secondaryDatePicker.label } (${ secondaryDatePicker.range })`;
		const secondaryDateMoment = getPreviousDate(
			interval.date_start,
			primaryDatePicker.after,
			secondaryDatePicker.after,
			comparison,
			currentInterval,
			matchByDate ? shift : undefined
		);
		let secondaryLabelDate = secondaryDateMoment.format(
			'YYYY-MM-DD HH:mm:ss'
		);
		let secondaryLabelDateEnd;
		let secondaryInterval;

		if ( ! matchByDate ) {
			secondaryInterval = secondaryDataIntervals[ index ];
		} else if (
			yearShifted &&
			index > 0 &&
			secondaryDateMoment.date() !== moment( interval.date_start ).date()
		) {
			// A primary 29th February has no counterpart a year earlier: moment
			// clamps it to the 28th, which already belongs to the primary 28th.
			// The label renders as "Invalid date", which is desirable since
			// 29th February is not a valid date for non-leap years.
			secondaryLabelDate = '-';
		} else {
			secondaryInterval = secondaryIntervalsByDate.get(
				secondaryDateMoment.format( 'YYYY-MM-DD' )
			);
		}

		let secondaryValue =
			( secondaryInterval &&
				secondaryInterval.subtotals[ selectedChartKey ] ) ||
			0;

		if (
			matchByDate &&
			yearShifted &&
			secondaryInterval &&
			isAdditiveMetric( selectedChartKey, selectedChartType )
		) {
			// A secondary 29th February has no column on a non-leap primary axis.
			// Fold it into the 28th so the line still adds up to the legend total.
			const dayAfter = secondaryDateMoment.clone().add( 1, 'days' );
			const leapDayInterval =
				dayAfter.month() === 1 && dayAfter.date() === 29
					? secondaryIntervalsByDate.get(
							dayAfter.format( 'YYYY-MM-DD' )
					  )
					: undefined;
			if ( leapDayInterval ) {
				secondaryValue +=
					leapDayInterval.subtotals[ selectedChartKey ] || 0;
				secondaryLabelDateEnd = leapDayInterval.date_start;
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
				...( secondaryLabelDateEnd && {
					labelDateEnd: secondaryLabelDateEnd,
				} ),
				value: secondaryValue,
			},
		} );
	}

	return chartData;
}
