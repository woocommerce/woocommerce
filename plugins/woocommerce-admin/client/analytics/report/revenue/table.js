/** @format */
/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { format as formatDate } from '@wordpress/date';
import { compose } from '@wordpress/compose';
import { get } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { appendTimestamp, defaultTableDateFormat, getCurrentDates } from '@woocommerce/date';
import { Date, Link } from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal, renderCurrency } from '@woocommerce/currency';
import { numberFormat } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import { QUERY_DEFAULTS } from 'wc-api/constants';
import ReportTable from 'analytics/components/report-table';
import withSelect from 'wc-api/with-select';

class RevenueReportTable extends Component {
	constructor() {
		super();

		this.getHeadersContent = this.getHeadersContent.bind( this );
		this.getRowsContent = this.getRowsContent.bind( this );
		this.getSummary = this.getSummary.bind( this );
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Date', 'woocommerce-admin' ),
				key: 'date',
				required: true,
				defaultSort: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'Orders', 'woocommerce-admin' ),
				key: 'orders_count',
				required: false,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Gross Revenue', 'woocommerce-admin' ),
				key: 'gross_revenue',
				required: true,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Refunds', 'woocommerce-admin' ),
				key: 'refunds',
				required: false,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Coupons', 'woocommerce-admin' ),
				key: 'coupons',
				required: false,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Taxes', 'woocommerce-admin' ),
				key: 'taxes',
				required: false,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Shipping', 'woocommerce-admin' ),
				key: 'shipping',
				required: false,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Net Revenue', 'woocommerce-admin' ),
				key: 'net_revenue',
				required: false,
				isSortable: true,
				isNumeric: true,
			},
		];
	}

	getRowsContent( data = [] ) {
		return data.map( row => {
			const {
				coupons,
				gross_revenue,
				net_revenue,
				orders_count,
				refunds,
				shipping,
				taxes,
			} = row.subtotals;

			// @todo How to create this per-report? Can use `w`, `year`, `m` to build time-specific order links
			// we need to know which kind of report this is, and parse the `label` to get this row's date
			const orderLink = (
				<Link
					href={ 'edit.php?post_type=shop_order&m=' + formatDate( 'Ymd', row.date_start ) }
					type="wp-admin"
				>
					{ numberFormat( orders_count ) }
				</Link>
			);
			return [
				{
					display: <Date date={ row.date_start } visibleFormat={ defaultTableDateFormat } />,
					value: row.date_start,
				},
				{
					display: orderLink,
					value: Number( orders_count ),
				},
				{
					display: renderCurrency( gross_revenue ),
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
					display: renderCurrency( taxes ),
					value: getCurrencyFormatDecimal( taxes ),
				},
				{
					display: renderCurrency( shipping ),
					value: getCurrencyFormatDecimal( shipping ),
				},
				{
					display: renderCurrency( net_revenue ),
					value: getCurrencyFormatDecimal( net_revenue ),
				},
			];
		} );
	}

	getSummary( totals, totalResults = 0 ) {
		const {
			orders_count = 0,
			gross_revenue = 0,
			refunds = 0,
			coupons = 0,
			taxes = 0,
			shipping = 0,
			net_revenue = 0,
		} = totals;
		return [
			{
				label: _n( 'day', 'days', totalResults, 'woocommerce-admin' ),
				value: numberFormat( totalResults ),
			},
			{
				label: _n( 'order', 'orders', orders_count, 'woocommerce-admin' ),
				value: numberFormat( orders_count ),
			},
			{
				label: __( 'gross revenue', 'woocommerce-admin' ),
				value: formatCurrency( gross_revenue ),
			},
			{
				label: __( 'refunds', 'woocommerce-admin' ),
				value: formatCurrency( refunds ),
			},
			{
				label: __( 'coupons', 'woocommerce-admin' ),
				value: formatCurrency( coupons ),
			},
			{
				label: __( 'taxes', 'woocommerce-admin' ),
				value: formatCurrency( taxes ),
			},
			{
				label: __( 'shipping', 'woocommerce-admin' ),
				value: formatCurrency( shipping ),
			},
			{
				label: __( 'net revenue', 'woocommerce-admin' ),
				value: formatCurrency( net_revenue ),
			},
		];
	}

	render() {
		const { query, tableData } = this.props;

		return (
			<ReportTable
				endpoint="revenue"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				getSummary={ this.getSummary }
				query={ query }
				tableData={ tableData }
				title={ __( 'Revenue', 'woocommerce-admin' ) }
				columnPrefsKey="revenue_report_columns"
			/>
		);
	}
}

export default compose(
	withSelect( ( select, props ) => {
		const { query } = props;
		const datesFromQuery = getCurrentDates( query );
		const { getReportStats, getReportStatsError, isReportStatsRequesting } = select( 'wc-api' );

		// @todo Support hour here when viewing a single day
		const tableQuery = {
			interval: 'day',
			orderby: query.orderby || 'date',
			order: query.order || 'desc',
			page: query.page || 1,
			per_page: query.per_page || QUERY_DEFAULTS.pageSize,
			after: appendTimestamp( datesFromQuery.primary.after, 'start' ),
			before: appendTimestamp( datesFromQuery.primary.before, 'end' ),
		};
		const revenueData = getReportStats( 'revenue', tableQuery );
		const isError = Boolean( getReportStatsError( 'revenue', tableQuery ) );
		const isRequesting = isReportStatsRequesting( 'revenue', tableQuery );

		return {
			tableData: {
				items: {
					data: get( revenueData, [ 'data', 'intervals' ], [] ),
					totalResults: get( revenueData, [ 'totalResults' ], 0 ),
				},
				isError,
				isRequesting,
				query: tableQuery,
			},
		};
	} )
)( RevenueReportTable );
