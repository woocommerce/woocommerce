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
import { getReportChartData, getTooltipValueFormat } from 'store/reports/utils';
import ReportError from 'analytics/components/report-error';
import withSelect from 'wc-api/with-select';

export const DEFAULT_FILTER = 'all';

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
		const { query, itemsLabel, mode, path, primaryData, secondaryData, selectedChart } = this.props;

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
				formatDate( 'Y-m-d', interval.date_start ),
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
				path={ path }
				query={ query }
				data={ chartData }
				title={ selectedChart.label }
				interval={ currentInterval }
				type={ getChartTypeForQuery( query ) }
				allowedIntervals={ allowedIntervals }
				itemsLabel={ itemsLabel }
				mode={ mode || this.getChartMode() }
				tooltipLabelFormat={ formats.tooltipLabelFormat }
				tooltipValueFormat={ getTooltipValueFormat( selectedChart.type ) }
				tooltipTitle={ selectedChart.label }
				xFormat={ formats.xFormat }
				x2Format={ formats.x2Format }
				dateParser={ '%Y-%m-%dT%H:%M:%S' }
				valueType={ selectedChart.type }
				isRequesting={ primaryData.isRequesting || secondaryData.isRequesting }
			/>
		);
	}
}

ReportChart.propTypes = {
	filters: PropTypes.array,
	itemsLabel: PropTypes.string,
	mode: PropTypes.string,
	path: PropTypes.string.isRequired,
	primaryData: PropTypes.object.isRequired,
	query: PropTypes.object.isRequired,
	secondaryData: PropTypes.object.isRequired,
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
