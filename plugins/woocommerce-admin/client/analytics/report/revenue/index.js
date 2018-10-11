/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { format as formatDate } from '@wordpress/date';
import { map, find, isEqual } from 'lodash';
import PropTypes from 'prop-types';
import { withSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	Card,
	Chart,
	ChartPlaceholder,
	EmptyContent,
	ReportFilters,
	SummaryList,
	SummaryListPlaceholder,
	SummaryNumber,
	TableCard,
	TablePlaceholder,
} from '@woocommerce/components';
import { downloadCSVFile, generateCSVDataFromTable, generateCSVFileName } from 'lib/csv';
import { formatCurrency, getCurrencyFormatDecimal } from 'lib/currency';
import { getAdminLink, getNewPath, onQueryChange } from 'lib/nav-utils';
import { getReportChartData } from 'store/reports/utils';
import {
	getCurrentDates,
	getPreviousDate,
	getIntervalForQuery,
	getAllowedIntervalsForQuery,
	getDateFormatsForInterval,
} from 'lib/date';
import { MAX_PER_PAGE } from 'store/constants';

export class RevenueReport extends Component {
	constructor() {
		super();
		this.state = {
			primaryTotals: null,
			secondaryTotals: null,
		};
		this.onDownload = this.onDownload.bind( this );
	}

	// Track primary and secondary 'totals' indepdent of query.
	// We don't want each little query update (interval, sorting, etc)
	componentDidUpdate( prevProps ) {
		/* eslint-disable react/no-did-update-set-state */

		if ( ! isEqual( prevProps.dates, this.props.dates ) ) {
			this.setState( {
				primaryTotals: null,
				secondaryTotals: null,
			} );
		}

		const { secondaryData, primaryData } = this.props;
		if ( ! isEqual( prevProps.secondaryData, secondaryData ) ) {
			if ( secondaryData && secondaryData.data && secondaryData.data.totals ) {
				this.setState( { secondaryTotals: secondaryData.data.totals } );
			}
		}

		if ( ! isEqual( prevProps.primaryData, primaryData ) ) {
			if ( primaryData && primaryData.data && primaryData.data.totals ) {
				this.setState( { primaryTotals: primaryData.data.totals } );
			}
		}
		/* eslint-enable react/no-did-update-set-state */
	}

	onDownload( headers, rows, query ) {
		// @TODO The current implementation only downloads the contents displayed in the table.
		// Another solution is required when the data set is larger (see #311).
		return () => {
			downloadCSVFile(
				generateCSVFileName( 'revenue', query ),
				generateCSVDataFromTable( headers, rows )
			);
		};
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Date', 'wc-admin' ),
				key: 'date',
				required: true,
				defaultSort: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'Orders', 'wc-admin' ),
				key: 'orders_count',
				required: false,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Gross Revenue', 'wc-admin' ),
				key: 'gross_revenue',
				required: true,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Refunds', 'wc-admin' ),
				key: 'refunds',
				required: false,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Coupons', 'wc-admin' ),
				key: 'coupons',
				required: false,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Taxes', 'wc-admin' ),
				key: 'taxes',
				required: false,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Shipping', 'wc-admin' ),
				key: 'shipping',
				required: false,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Net Revenue', 'wc-admin' ),
				key: 'net_revenue',
				required: false,
				isSortable: true,
				isNumeric: true,
			},
		];
	}

	getRowsContent( data = [] ) {
		const { query } = this.props;
		const currentInterval = getIntervalForQuery( query );
		const formats = getDateFormatsForInterval( currentInterval );

		return map( data, row => {
			const {
				coupons,
				gross_revenue,
				net_revenue,
				orders_count,
				refunds,
				shipping,
				taxes,
			} = row.subtotals;

			// @TODO How to create this per-report? Can use `w`, `year`, `m` to build time-specific order links
			// we need to know which kind of report this is, and parse the `label` to get this row's date
			const orderLink = (
				<a
					href={ getAdminLink(
						'edit.php?post_type=shop_order&m=' + formatDate( 'Ymd', row.date_start )
					) }
				>
					{ orders_count }
				</a>
			);
			return [
				{
					display: formatDate( formats.tableFormat, row.date_start ),
					value: row.date_start,
				},
				{
					display: orderLink,
					value: Number( orders_count ),
				},
				{
					display: formatCurrency( gross_revenue ),
					value: getCurrencyFormatDecimal( gross_revenue ),
				},
				{
					display: formatCurrency( refunds ),
					value: getCurrencyFormatDecimal( refunds ),
				},
				{
					display: formatCurrency( coupons ),
					value: getCurrencyFormatDecimal( coupons ),
				},
				{
					display: formatCurrency( taxes ),
					value: getCurrencyFormatDecimal( taxes ),
				},
				{
					display: formatCurrency( shipping ),
					value: getCurrencyFormatDecimal( shipping ),
				},
				{
					display: formatCurrency( net_revenue ),
					value: getCurrencyFormatDecimal( net_revenue ),
				},
			];
		} );
	}

	getCharts() {
		return [
			{
				key: 'gross_revenue',
				label: __( 'Gross Revenue', 'wc-admin' ),
				type: 'currency',
			},
			{
				key: 'refunds',
				label: __( 'Refunds', 'wc-admin' ),
				type: 'currency',
			},
			{
				key: 'coupons',
				label: __( 'Coupons', 'wc-admin' ),
				type: 'currency',
			},
			{
				key: 'taxes',
				label: __( 'Taxes', 'wc-admin' ),
				type: 'currency',
			},
			{
				key: 'shipping',
				label: __( 'Shipping', 'wc-admin' ),
				type: 'currency',
			},
			{
				key: 'net_revenue',
				label: __( 'Net Revenue', 'wc-admin' ),
				type: 'currency',
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

	// TODO since this pattern will exist on every report, this possibly should become a component
	renderChartSummaryNumbers() {
		const selectedChart = this.getSelectedChart();
		const charts = this.getCharts();
		if ( ! this.state.primaryTotals || ! this.state.secondaryTotals ) {
			return <SummaryListPlaceholder numberOfItems={ charts.length } />;
		}

		const totals = this.state.primaryTotals || {};
		const secondaryTotals = this.state.secondaryTotals || {};

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
				// TODO: implement other format handlers
				case 'currency':
					value = formatCurrency( value );
					secondaryValue = secondaryValue && formatCurrency( secondaryValue );
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

	renderTable() {
		const { isTableDataRequesting, tableData, tableQuery } = this.props;
		const headers = this.getHeadersContent();

		if ( isTableDataRequesting ) {
			return (
				<Card
					title={ __( 'Revenue', 'wc-admin' ) }
					className="woocommerce-analytics__table-placeholder"
				>
					<TablePlaceholder
						caption={ __( 'Revenue', 'wc-admin' ) }
						headers={ headers }
						query={ tableQuery }
					/>
				</Card>
			);
		}

		const intervals = tableData.data.intervals;
		const rows = this.getRowsContent( intervals );

		const rowsPerPage =
			( tableQuery && tableQuery.per_page && parseInt( tableQuery.per_page ) ) || 25;

		return (
			<TableCard
				title={ __( 'Revenue', 'wc-admin' ) }
				rows={ rows }
				totalRows={ tableData.totalResults }
				rowsPerPage={ rowsPerPage }
				headers={ headers }
				onClickDownload={ this.onDownload( headers, rows, tableQuery ) }
				onQueryChange={ onQueryChange }
				query={ tableQuery }
				summary={ null }
			/>
		);
	}

	render() {
		const { path, query, primaryData, secondaryData, isTableDataError } = this.props;

		if ( primaryData.isError || secondaryData.isError || isTableDataError ) {
			let title, actionLabel, actionURL, actionCallback;
			if ( primaryData.isError || secondaryData.isError ) {
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
				<ReportFilters query={ query } path={ path } />

				{ this.renderChartSummaryNumbers() }
				{ this.renderChart() }
				{ this.renderTable() }
			</Fragment>
		);
	}
}

RevenueReport.propTypes = {
	params: PropTypes.object.isRequired,
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
};

export default compose(
	withSelect( ( select, props ) => {
		const { query } = props;
		const { getReportStats, isReportStatsRequesting, isReportStatsError } = select( 'wc-admin' );
		const datesFromQuery = getCurrentDates( query );
		const interval = getIntervalForQuery( query );
		const baseArgs = {
			order: 'asc',
			interval: interval,
			per_page: MAX_PER_PAGE,
		};

		const primaryData = getReportChartData(
			'revenue',
			{
				...baseArgs,
				after: datesFromQuery.primary.after,
				before: datesFromQuery.primary.before,
			},
			select
		);

		const secondaryData = getReportChartData(
			'revenue',
			{
				...baseArgs,
				after: datesFromQuery.secondary.after,
				before: datesFromQuery.secondary.before,
			},
			select
		);

		// TODO Support hour here when viewing a single day
		const tableQuery = {
			interval: 'day',
			orderby: query.orderby || 'date',
			order: query.order || 'asc',
			page: query.page || 1,
			per_page: query.per_page || 25,
			after: datesFromQuery.primary.after + 'T00:00:00+00:00',
			before: datesFromQuery.primary.before + 'T23:59:59+00:00',
		};
		const tableData = getReportStats( 'revenue', tableQuery );
		const isTableDataError = isReportStatsError( 'revenue', tableQuery );
		const isTableDataRequesting = isReportStatsRequesting( 'revenue', tableQuery );

		const primaryDates = {
			after: datesFromQuery.primary.after,
			before: datesFromQuery.primary.before,
		};
		const secondaryDates = {
			after: datesFromQuery.secondary.after,
			before: datesFromQuery.secondary.before,
		};

		const dates = {
			primaryDates,
			secondaryDates,
		};

		return {
			dates,
			primaryData,
			secondaryData,
			tableQuery,
			tableData,
			isTableDataError,
			isTableDataRequesting,
		};
	} )
)( RevenueReport );
