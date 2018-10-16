/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { format as formatDate } from '@wordpress/date';
import PropTypes from 'prop-types';
import { withSelect } from '@wordpress/data';
import { find, get, map } from 'lodash';

/**
 * Internal dependencies
 */
import {
	Chart,
	ChartPlaceholder,
	EmptyContent,
	ReportFilters,
	SummaryList,
	SummaryListPlaceholder,
	SummaryNumber,
} from '@woocommerce/components';
import { filters, advancedFilterConfig } from './config';
import { formatCurrency } from 'lib/currency';
import { getAdminLink, getNewPath } from 'lib/nav-utils';
import { getReportChartData, getSummaryNumbers } from 'store/reports/utils';
import {
	getAllowedIntervalsForQuery,
	getCurrentDates,
	getDateParamsFromQuery,
	getDateFormatsForInterval,
	getIntervalForQuery,
	getPreviousDate,
} from 'lib/date';
import { MAX_PER_PAGE } from 'store/constants';
import OrdersReportTable from './table';

class OrdersReport extends Component {
	constructor( props ) {
		super( props );
	}

	getCharts() {
		return [
			{
				key: 'net_revenue',
				label: __( 'Net Revenue', 'wc-admin' ),
				type: 'currency',
			},
			{
				key: 'avg_order_value',
				label: __( 'Avergae Order Value', 'wc-admin' ),
				type: 'currency',
			},
			{
				key: 'avg_items_per_order',
				label: __( 'Average Items Per Order', 'wc-admin' ),
				type: 'average',
			},
			{
				key: 'orders_count',
				label: __( 'Orders Count', 'wc-admin' ),
				type: 'number',
			},
		];
	}

	getSelectedChart() {
		const { query } = this.props;
		const charts = this.getCharts();

		const chart = find( charts, { key: query.chart } );
		if ( chart ) {
			return chart;
		}

		return charts[ 0 ];
	}

	renderChartSummaryNumbers() {
		const selectedChart = this.getSelectedChart();
		const charts = this.getCharts();
		if ( this.props.summaryNumbers.isRequesting ) {
			return <SummaryListPlaceholder numberOfItems={ charts.length } />;
		}

		const totals = this.props.summaryNumbers.totals.primary || {};
		const secondaryTotals = this.props.summaryNumbers.totals.secondary || {};
		const { compare } = getDateParamsFromQuery( this.props.query );

		const summaryNumbers = map( this.getCharts(), chart => {
			const { key, label, type } = chart;
			const isSelected = selectedChart.key === key;
			let value = parseFloat( totals[ key ] );
			let secondaryValue =
				( secondaryTotals[ key ] && parseFloat( secondaryTotals[ key ] ) ) || undefined;

			let delta = 0;
			if ( secondaryValue && secondaryValue !== 0 ) {
				delta = Math.round( ( value - secondaryValue ) / secondaryValue * 100 );
			}

			switch ( type ) {
				case 'average':
					value = Math.round( value );
					secondaryValue = secondaryValue && Math.round( secondaryValue );
					break;
				case 'currency':
					value = formatCurrency( value );
					secondaryValue = secondaryValue && formatCurrency( secondaryValue );
					break;
				case 'number':
					break;
			}

			const href = getNewPath( { chart: key } );

			return (
				<SummaryNumber
					key={ key }
					value={ value }
					label={ label }
					selected={ isSelected }
					prevValue={ secondaryValue }
					prevLabel={
						'previous_period' === compare
							? __( 'Previous Period:', 'wc-admin' )
							: __( 'Previous Year:', 'wc-admin' )
					}
					delta={ delta }
					href={ href }
				/>
			);
		} );

		return <SummaryList>{ summaryNumbers }</SummaryList>;
	}

	renderChart() {
		const { primaryData, secondaryData, query } = this.props;

		if ( primaryData.isRequesting || secondaryData.isRequesting ) {
			return (
				<Fragment>
					<span className="screen-reader-text">
						{ __( 'Your requested data is loading', 'wc-admin' ) }
					</span>
					<ChartPlaceholder />
				</Fragment>
			);
		}

		const currentInterval = getIntervalForQuery( query );
		const allowedIntervals = getAllowedIntervalsForQuery( query );
		const formats = getDateFormatsForInterval( currentInterval );

		const { primary, secondary } = getCurrentDates( query );
		const selectedChart = this.getSelectedChart();

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
					labelDate: secondaryDate,
					value: ( secondaryInterval && secondaryInterval.subtotals[ selectedChart.key ] ) || 0,
				},
			};
		} );

		return (
			<Chart
				data={ chartData }
				title={ selectedChart.label }
				interval={ currentInterval }
				allowedIntervals={ allowedIntervals }
				mode="time-comparison"
				pointLabelFormat={ formats.pointLabelFormat }
				tooltipTitle={ selectedChart.label }
				xFormat={ formats.xFormat }
				x2Format={ formats.x2Format }
				dateParser={ '%Y-%m-%dT%H:%M:%S' }
			/>
		);
	}

	render() {
		const {
			isTableDataError,
			isTableDataRequesting,
			orders,
			path,
			query,
			primaryData,
			secondaryData,
			summaryNumbers,
		} = this.props;

		if (
			primaryData.isError ||
			secondaryData.isError ||
			isTableDataError ||
			summaryNumbers.isError
		) {
			let title, actionLabel, actionURL, actionCallback;
			if ( primaryData.isError || secondaryData.isError || isTableDataError ) {
				title = __( 'There was an error getting your stats. Please try again.', 'wc-admin' );
				actionLabel = __( 'Reload', 'wc-admin' );
				actionCallback = () => {
					// TODO Add tracking for how often an error is displayed, and the reload action is clicked.
					window.location.reload();
				};
			} else {
				title = __( 'No results could be found for this date range.', 'wc-admin' );
				actionLabel = __( 'View Orders', 'wc-admin' );
				actionURL = getAdminLink( 'edit.php?post_type=shop_order' );
			}

			return (
				<Fragment>
					<ReportFilters query={ query } path={ path } />
					<EmptyContent
						title={ title }
						actionLabel={ actionLabel }
						actionURL={ actionURL }
						actionCallback={ actionCallback }
					/>
				</Fragment>
			);
		}

		return (
			<Fragment>
				<ReportFilters
					query={ query }
					path={ path }
					filters={ filters }
					advancedConfig={ advancedFilterConfig }
				/>
				{ this.renderChartSummaryNumbers() }
				{ this.renderChart() }
				<OrdersReportTable
					isRequesting={ isTableDataRequesting }
					orders={ orders }
					query={ query }
					totalRows={ get(
						primaryData,
						[ 'data', 'totals', 'orders_count' ],
						Object.keys( orders ).length
					) }
				/>
			</Fragment>
		);
	}
}

OrdersReport.propTypes = {
	params: PropTypes.object.isRequired,
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
};

export default compose(
	withSelect( ( select, props ) => {
		const { query } = props;
		const interval = getIntervalForQuery( query );
		const datesFromQuery = getCurrentDates( query );
		const baseArgs = {
			order: 'asc',
			interval: interval,
			per_page: MAX_PER_PAGE,
		};

		const summaryNumbers = getSummaryNumbers(
			'orders',
			{
				primary: datesFromQuery.primary,
				secondary: datesFromQuery.secondary,
			},
			select
		);

		const primaryData = getReportChartData(
			'orders',
			{
				...baseArgs,
				after: datesFromQuery.primary.after,
				before: datesFromQuery.primary.before,
			},
			select
		);

		const secondaryData = getReportChartData(
			'orders',
			{
				...baseArgs,
				after: datesFromQuery.secondary.after,
				before: datesFromQuery.secondary.before,
			},
			select
		);

		const { getOrders, isGetOrdersError, isGetOrdersRequesting } = select( 'wc-admin' );
		const tableQuery = {
			orderby: query.orderby || 'date',
			order: query.order || 'asc',
			page: query.page || 1,
			per_page: query.per_page || 25,
			after: datesFromQuery.primary.after + 'T00:00:00+00:00',
			before: datesFromQuery.primary.before + 'T23:59:59+00:00',
			status: [ 'processing', 'on-hold', 'completed' ],
		};
		const orders = getOrders( tableQuery );
		const isTableDataError = isGetOrdersError( tableQuery );
		const isTableDataRequesting = isGetOrdersRequesting( tableQuery );

		return {
			isTableDataError,
			isTableDataRequesting,
			orders,
			primaryData,
			secondaryData,
			summaryNumbers,
		};
	} )
)( OrdersReport );
