/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { format as formatDate } from '@wordpress/date';
import { map, find, orderBy } from 'lodash';
import PropTypes from 'prop-types';
import { withSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	Card,
	Chart,
	ReportFilters,
	SummaryList,
	SummaryNumber,
	TableCard,
} from '@woocommerce/components';
import { downloadCSVFile, generateCSVDataFromTable, generateCSVFileName } from 'lib/csv';
import { formatCurrency, getCurrencyFormatDecimal } from 'lib/currency';
import { getAdminLink, getNewPath, updateQueryString } from 'lib/nav-utils';
import { getAllReportData, isReportDataEmpty } from 'store/reports/utils';
import { getCurrentDates, isoDateFormat, getPreviousDate, getDateDifferenceInDays } from 'lib/date';
import { MAX_PER_PAGE } from 'store/constants';

export class RevenueReport extends Component {
	constructor() {
		super();
		this.onDownload = this.onDownload.bind( this );
		this.onQueryChange = this.onQueryChange.bind( this );
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

	/**
	 * This function returns an event handler for the given `param`
	 * @todo Move handling of this to a library?
	 * @param {string} param The parameter in the querystring which should be updated (ex `page`, `per_page`)
	 * @return {function} A callback which will update `param` to the passed value when called.
	 */
	onQueryChange( param ) {
		switch ( param ) {
			case 'sort':
				return ( key, dir ) => updateQueryString( { orderby: key, order: dir } );
			default:
				return value => updateQueryString( { [ param ]: value } );
		}
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Date', 'wc-admin' ),
				key: 'date_start',
				required: true,
				defaultSort: true,
				isSortable: true,
			},
			{
				label: __( 'Orders', 'wc-admin' ),
				key: 'orders_count',
				required: false,
				isSortable: true,
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
					display: formatDate( 'm/d/Y', row.date_start ),
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
		const { primaryData, secondaryData } = this.props;
		const { totals } = primaryData.data;
		const secondaryTotals = secondaryData.data.totals || {};

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
		const { primary, secondary } = getCurrentDates( query );
		const selectedChart = this.getSelectedChart();

		const primaryKey = `${ primary.label } (${ primary.range })`;
		const secondaryKey = `${ secondary.label } (${ secondary.range })`;

		const difference = getDateDifferenceInDays( primary.after, secondary.after );

		const chartData = primaryData.data.intervals.map( function( interval ) {
			const date = formatDate( 'Y-m-d', interval.date_start );
			const secondaryDate = getPreviousDate( date, difference, query.compare );
			const secondaryInterval = find( secondaryData.data.intervals, {
				date_start: secondaryDate.format( isoDateFormat ) + ' 00:00:00',
			} );

			return {
				date,
				[ primaryKey ]: interval.subtotals[ selectedChart.key ] || 0,
				[ secondaryKey ]:
					( secondaryInterval && secondaryInterval.subtotals[ selectedChart.key ] ) || 0,
			};
		} );

		return (
			<Card title="">
				<Chart data={ chartData } title={ selectedChart.label } />
			</Card>
		);
	}

	renderTable() {
		const { primaryData, query } = this.props;
		const intervals = primaryData.data.intervals;

		const page = parseInt( query.page ) || 1;
		const rowsPerPage = parseInt( query.per_page ) || 25;

		const rows =
			this.getRowsContent(
				orderBy(
					intervals,
					function( interval ) {
						return 'undefined' === typeof interval.subtotals[ query.orderby ]
							? interval.date_start
							: interval.subtotals[ query.orderby ];
					},
					query.order || 'asc'
				).slice( ( page - 1 ) * rowsPerPage, page * rowsPerPage )
			) || [];

		const headers = this.getHeadersContent();

		const tableQuery = { ...query, orderby: query.orderby || 'date_start', order: query.order || 'asc' };
		return (
			<TableCard
				title={ __( 'Revenue', 'wc-admin' ) }
				rows={ rows }
				totalRows={ intervals.length }
				rowsPerPage={ rowsPerPage }
				headers={ headers }
				onClickDownload={ this.onDownload( headers, rows, tableQuery ) }
				onQueryChange={ this.onQueryChange }
				query={ tableQuery }
				summary={ null }
			/>
		);
	}

	render() {
		const { path, query, primaryData, secondaryData } = this.props;

		// TODO The loading, error, and empty messages below are all temporary.
		// So we need to use an actual EmptyState components and add in a loading indicator.
		const tempMessage = message => {
			return (
				<div>
					<ReportFilters query={ query } path={ path } />
					<p>{ message }</p>
				</div>
			);
		};

		if ( isReportDataEmpty( primaryData ) ) {
			return tempMessage( 'Empty Data' );
		}

		if ( primaryData.isRequesting || secondaryData.isRequesting ) {
			return tempMessage( 'Loading...' );
		}

		if ( primaryData.isError || secondaryData.isError ) {
			return tempMessage( 'Error' );
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
		const datesFromQuery = getCurrentDates( query );
		const baseArgs = {
			order: 'asc',
			interval: 'day', // TODO support other intervals
			per_page: MAX_PER_PAGE,
		};

		const primaryData = getAllReportData(
			'revenue',
			{
				...baseArgs,
				after: datesFromQuery.primary.after,
				before: datesFromQuery.primary.before,
			},
			select
		);

		const secondaryData = getAllReportData(
			'revenue',
			{
				...baseArgs,
				after: datesFromQuery.secondary.after,
				before: datesFromQuery.secondary.before,
			},
			select
		);

		return {
			primaryData,
			secondaryData,
		};
	} )
)( RevenueReport );
