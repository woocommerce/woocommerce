/** @format */
/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { format as formatDate } from '@wordpress/date';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { get, map, orderBy } from 'lodash';

/**
 * WooCommerce dependencies
 */
import {
	appendTimestamp,
	getCurrentDates,
	getIntervalForQuery,
	getDateFormatsForInterval,
} from '@woocommerce/date';
import { Link, TableCard } from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { onQueryChange } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ReportError from 'analytics/components/report-error';
import { QUERY_DEFAULTS } from 'store/constants';
import { getReportChartData, getFilterQuery } from 'store/reports/utils';

class CouponsReportTable extends Component {
	getHeadersContent() {
		return [
			{
				label: __( 'Coupon Code', 'wc-admin' ),
				// @TODO it should be the coupon code, not the coupon ID
				key: 'coupon_id',
				required: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'Orders', 'wc-admin' ),
				key: 'orders_count',
				required: true,
				defaultSort: true,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'G. Discounted', 'wc-admin' ),
				key: 'gross_discount',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Created', 'wc-admin' ),
				key: 'created',
				isSortable: true,
			},
			{
				label: __( 'Expires', 'wc-admin' ),
				key: 'expires',
				isSortable: true,
			},
			{
				label: __( 'Type', 'wc-admin' ),
				key: 'type',
				isSortable: false,
			},
		];
	}

	getRowsContent( coupons ) {
		const { query } = this.props;
		const currentInterval = getIntervalForQuery( query );
		const { tableFormat } = getDateFormatsForInterval( currentInterval );

		return map( coupons, coupon => {
			const { coupon_id, gross_discount, orders_count } = coupon;

			// @TODO must link to the coupon detail report
			const couponLink = (
				<Link href="" type="wc-admin">
					{ coupon_id }
				</Link>
			);

			const ordersLink = (
				<Link
					href={ '/analytics/orders?filter=advanced&code_includes=' + coupon_id }
					type="wc-admin"
				>
					{ orders_count }
				</Link>
			);

			return [
				// @TODO it should be the coupon code, not the coupon ID
				{
					display: couponLink,
					value: coupon_id,
				},
				{
					display: ordersLink,
					value: orders_count,
				},
				{
					display: formatCurrency( gross_discount ),
					value: getCurrencyFormatDecimal( gross_discount ),
				},
				{
					// @TODO
					display: formatDate( tableFormat, '' ),
					value: '',
				},
				{
					// @TODO
					display: formatDate( tableFormat, '' ),
					value: '',
				},
				{
					// @TODO
					display: '',
					value: '',
				},
			];
		} );
	}

	getSummary( totals ) {
		if ( ! totals ) {
			return [];
		}
		return [
			{
				label: _n( 'coupon', 'coupons', totals.coupons_count, 'wc-admin' ),
				value: totals.coupons_count,
			},
			{
				label: _n( 'order', 'orders', totals.orders_count, 'wc-admin' ),
				value: totals.orders_count,
			},
			{
				label: __( 'gross discounted', 'wc-admin' ),
				value: formatCurrency( totals.gross_discount ),
			},
		];
	}

	render() {
		const { coupons, isTableDataError, isTableDataRequesting, primaryData, query } = this.props;

		const isError = isTableDataError || primaryData.isError;

		if ( isError ) {
			return <ReportError isError />;
		}

		const isRequesting = isTableDataRequesting || primaryData.isRequesting;

		const tableQuery = {
			...query,
			orderby: query.orderby || 'date',
			order: query.order || 'asc',
		};

		const headers = this.getHeadersContent();
		const orderedCoupons = orderBy( coupons, tableQuery.orderby, tableQuery.order );
		const rows = this.getRowsContent( orderedCoupons );
		const rowsPerPage = parseInt( tableQuery.per_page ) || QUERY_DEFAULTS.pageSize;
		const totalRows = get( primaryData, [ 'data', 'totals', 'coupons_count' ], coupons.length );
		const summary = primaryData.data.totals ? this.getSummary( primaryData.data.totals ) : null;

		return (
			<TableCard
				title={ __( 'Coupons', 'wc-admin' ) }
				compareBy={ 'coupons' }
				ids={ orderedCoupons.map( coupon => coupon.coupon_id ) }
				rows={ rows }
				totalRows={ totalRows }
				rowsPerPage={ rowsPerPage }
				headers={ headers }
				isLoading={ isRequesting }
				onQueryChange={ onQueryChange }
				query={ tableQuery }
				summary={ summary }
				downloadable
			/>
		);
	}
}

export default compose(
	withSelect( ( select, props ) => {
		const { query } = props;
		const datesFromQuery = getCurrentDates( query );
		const primaryData = getReportChartData( 'coupons', 'primary', query, select );
		const filterQuery = getFilterQuery( 'coupons', query );

		const { getCoupons, isGetCouponsError, isGetCouponsRequesting } = select( 'wc-admin' );
		const tableQuery = {
			orderby: query.orderby || 'date',
			order: query.order || 'asc',
			page: query.page || 1,
			per_page: query.per_page || QUERY_DEFAULTS.pageSize,
			after: appendTimestamp( datesFromQuery.primary.after, 'start' ),
			before: appendTimestamp( datesFromQuery.primary.before, 'end' ),
			...filterQuery,
		};
		const coupons = getCoupons( tableQuery );
		const isTableDataError = isGetCouponsError( tableQuery );
		const isTableDataRequesting = isGetCouponsRequesting( tableQuery );

		return {
			isTableDataError,
			isTableDataRequesting,
			coupons,
			primaryData,
		};
	} )
)( CouponsReportTable );
