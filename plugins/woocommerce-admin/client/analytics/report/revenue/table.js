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
import {
	appendTimestamp,
	getCurrentDates,
	getDateFormatsForInterval,
	getIntervalForQuery,
} from '@woocommerce/date';
import { Link } from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import { QUERY_DEFAULTS } from 'store/constants';
import { numberFormat } from 'lib/number';
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

			// @TODO How to create this per-report? Can use `w`, `year`, `m` to build time-specific order links
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

	getSummary( totals, totalCount ) {
		if ( ! totals ) {
			return [];
		}

		return [
			{
				label: _n( 'day', 'days', totalCount, 'wc-admin' ),
				value: numberFormat( totalCount ),
			},
			{
				label: _n( 'order', 'orders', totals.orders_count, 'wc-admin' ),
				value: numberFormat( totals.orders_count ),
			},
			{
				label: __( 'gross revenue', 'wc-admin' ),
				value: formatCurrency( totals.gross_revenue ),
			},
			{
				label: __( 'refunds', 'wc-admin' ),
				value: formatCurrency( totals.refunds ),
			},
			{
				label: __( 'coupons', 'wc-admin' ),
				value: formatCurrency( totals.coupons ),
			},
			{
				label: __( 'taxes', 'wc-admin' ),
				value: formatCurrency( totals.taxes ),
			},
			{
				label: __( 'shipping', 'wc-admin' ),
				value: formatCurrency( totals.shipping ),
			},
			{
				label: __( 'net revenue', 'wc-admin' ),
				value: formatCurrency( totals.net_revenue ),
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
				title={ __( 'Revenue', 'wc-admin' ) }
			/>
		);
	}
}

export default compose(
	withSelect( ( select, props ) => {
		const { query } = props;
		const datesFromQuery = getCurrentDates( query );
		const { getReportStats, isReportStatsRequesting, isReportStatsError } = select( 'wc-api' );

		// TODO Support hour here when viewing a single day
		const tableQuery = {
			interval: 'day',
			orderby: query.orderby || 'date',
			order: query.order || 'asc',
			page: query.page || 1,
			per_page: query.per_page || QUERY_DEFAULTS.pageSize,
			after: appendTimestamp( datesFromQuery.primary.after, 'start' ),
			before: appendTimestamp( datesFromQuery.primary.before, 'end' ),
		};
		const revenueData = getReportStats( 'revenue', tableQuery );
		const isError = isReportStatsError( 'revenue', tableQuery );
		const isRequesting = isReportStatsRequesting( 'revenue', tableQuery );

		return {
			tableData: {
				items: {
					data: get( revenueData, [ 'data', 'intervals' ] ),
					totalCount: get( revenueData, [ 'totalResults' ] ),
				},
				isError,
				isRequesting,
				query: tableQuery,
			},
		};
	} )
)( RevenueReportTable );
