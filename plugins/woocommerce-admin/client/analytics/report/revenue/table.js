/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { format as formatDate } from '@wordpress/date';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { get, map } from 'lodash';

/**
 * WooCommerce dependencies
 */
import {
	appendTimestamp,
	getCurrentDates,
	getDateFormatsForInterval,
	getIntervalForQuery,
} from '@woocommerce/date';
import { Link, TableCard } from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { onQueryChange } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ReportError from 'analytics/components/report-error';
import { QUERY_DEFAULTS } from 'store/constants';

class RevenueReportTable extends Component {
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
				<Link
					href={ 'edit.php?post_type=shop_order&m=' + formatDate( 'Ymd', row.date_start ) }
					type="wp-admin"
				>
					{ orders_count }
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

	render() {
		const { isTableDataError, isTableDataRequesting, query, tableData } = this.props;

		if ( isTableDataError ) {
			return <ReportError isError />;
		}

		const tableQuery = {
			...query,
			orderby: query.orderby || 'date',
			order: query.order || 'asc',
		};

		const headers = this.getHeadersContent();
		const rows = this.getRowsContent( get( tableData, [ 'data', 'intervals' ], [] ) );
		const rowsPerPage =
			( tableQuery && tableQuery.per_page && parseInt( tableQuery.per_page ) ) ||
			QUERY_DEFAULTS.pageSize;
		const totalRows = get( tableData, [ 'totalResults' ], Object.keys( rows ).length );

		return (
			<TableCard
				title={ __( 'Revenue', 'wc-admin' ) }
				rows={ rows }
				totalRows={ totalRows }
				rowsPerPage={ rowsPerPage }
				headers={ headers }
				isLoading={ isTableDataRequesting }
				onQueryChange={ onQueryChange }
				query={ tableQuery }
				summary={ null }
				downloadable
			/>
		);
	}
}

export default compose(
	withSelect( ( select, props ) => {
		const { query } = props;
		const datesFromQuery = getCurrentDates( query );
		const { getReportStats, isReportStatsRequesting, isReportStatsError } = select( 'wc-admin' );

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
		const tableData = getReportStats( 'revenue', tableQuery );
		const isTableDataError = isReportStatsError( 'revenue', tableQuery );
		const isTableDataRequesting = isReportStatsRequesting( 'revenue', tableQuery );

		return {
			isTableDataError,
			isTableDataRequesting,
			tableQuery,
			tableData,
		};
	} )
)( RevenueReportTable );
