/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { format as formatDate } from '@wordpress/date';
import PropTypes from 'prop-types';
import { find, get } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { flattenFilters } from '@woocommerce/navigation';
import {
	getAllowedIntervalsForQuery,
	getCurrentDates,
	getDateFormatsForInterval,
	getIntervalForQuery,
	getChartTypeForQuery,
	getPreviousDate,
} from '@woocommerce/date';
import { Chart } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { getReportChartData, getTooltipValueFormat } from 'wc-api/reports/utils';
import ReportError from 'analytics/components/report-error';
import withSelect from 'wc-api/with-select';

export const DEFAULT_FILTER = 'all';

/**
 * Component that renders the chart in reports.
 */
export class ReportChart extends Component {
	getSelectedFilter( filters, query ) {
		if ( filters.length === 0 ) {
			return null;
		}

		const filterConfig = filters.pop();

		if ( filterConfig.showFilters( query ) ) {
			const allFilters = flattenFilters( filterConfig.filters );
			const value = query[ filterConfig.param ] || DEFAULT_FILTER;
			const selectedFilter = find( allFilters, { value } );
			const selectedFilterParam = get( selectedFilter, [ 'settings', 'param' ] );

			if ( ! selectedFilterParam || Object.keys( query ).includes( selectedFilterParam ) ) {
				return selectedFilter;
			}
		}

		return this.getSelectedFilter( filters, query );
	}

	getChartMode() {
		const { filters, query } = this.props;
		if ( ! filters ) {
			return;
		}
		const clonedFilters = filters.slice( 0 );
		const selectedFilter = this.getSelectedFilter( clonedFilters, query );

		return get( selectedFilter, [ 'chartMode' ] );
	}

	render() {
		const {
			interactiveLegend,
			itemsLabel,
			legendPosition,
			mode,
			path,
			primaryData,
			query,
			secondaryData,
			selectedChart,
			showHeaderControls,
		} = this.props;

		if ( primaryData.isError || secondaryData.isError ) {
			return <ReportError isError />;
		}

		const currentInterval = getIntervalForQuery( query );
		const allowedIntervals = getAllowedIntervalsForQuery( query );
		const formats = getDateFormatsForInterval( currentInterval, primaryData.data.intervals.length );
		const { primary, secondary } = getCurrentDates( query );
		const primaryKey = `${ primary.label } (${ primary.range })`;
		const secondaryKey = `${ secondary.label } (${ secondary.range })`;

		const chartData = primaryData.data.intervals.map( function( interval, index ) {
			const secondaryDate = getPreviousDate(
				interval.date_start,
				primary.after,
				secondary.after,
				query.compare,
				currentInterval
			);

			const secondaryInterval = secondaryData.data.intervals[ index ];
			return {
				date: formatDate( 'Y-m-d\\TH:i:s', interval.date_start ),
				[ primaryKey ]: {
					labelDate: interval.date_start,
					value: interval.subtotals[ selectedChart.key ] || 0,
				},
				[ secondaryKey ]: {
					labelDate: secondaryDate.format( 'YYYY-MM-DD HH:mm:ss' ),
					value: ( secondaryInterval && secondaryInterval.subtotals[ selectedChart.key ] ) || 0,
				},
			};
		} );

		return (
			<Chart
				allowedIntervals={ allowedIntervals }
				data={ chartData }
				dateParser={ '%Y-%m-%dT%H:%M:%S' }
				interactiveLegend={ interactiveLegend }
				interval={ currentInterval }
				isRequesting={ primaryData.isRequesting || secondaryData.isRequesting }
				itemsLabel={ itemsLabel }
				legendPosition={ legendPosition }
				mode={ mode || this.getChartMode() }
				path={ path }
				query={ query }
				showHeaderControls={ showHeaderControls }
				title={ selectedChart.label }
				tooltipLabelFormat={ formats.tooltipLabelFormat }
				tooltipTitle={ selectedChart.label }
				tooltipValueFormat={ getTooltipValueFormat( selectedChart.type ) }
				type={ getChartTypeForQuery( query ) }
				valueType={ selectedChart.type }
				xFormat={ formats.xFormat }
				x2Format={ formats.x2Format }
			/>
		);
	}
}

ReportChart.propTypes = {
	/**
	 * Filters available for that report.
	 */
	filters: PropTypes.array,
	/**
	 * Label describing the legend items.
	 */
	itemsLabel: PropTypes.string,
	/**
	 * `items-comparison` (default) or `time-comparison`, this is used to generate correct
	 * ARIA properties.
	 */
	mode: PropTypes.string,
	/**
	 * Current path
	 */
	path: PropTypes.string.isRequired,
	/**
	 * Primary data to display in the chart.
	 */
	primaryData: PropTypes.object.isRequired,
	/**
	 * The query string represented in object form.
	 */
	query: PropTypes.object.isRequired,
	/**
	 * Secondary data to display in the chart.
	 */
	secondaryData: PropTypes.object.isRequired,
	/**
	 * Properties of the selected chart.
	 */
	selectedChart: PropTypes.object.isRequired,
};

export default compose(
	withSelect( ( select, props ) => {
		const { query, endpoint } = props;
		const primaryData = getReportChartData( endpoint, 'primary', query, select );
		const secondaryData = getReportChartData( endpoint, 'secondary', query, select );
		return {
			primaryData,
			secondaryData,
		};
	} )
)( ReportChart );
