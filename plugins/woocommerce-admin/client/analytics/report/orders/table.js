/** @format */
/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
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
import { Link, OrderStatus, TableCard, ViewMoreList } from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { getAdminLink, onQueryChange } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ReportError from 'analytics/components/report-error';
import { QUERY_DEFAULTS } from 'store/constants';
import { getReportChartData, getFilterQuery } from 'store/reports/utils';
import './style.scss';

class OrdersReportTable extends Component {
	constructor( props ) {
		super( props );
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
				label: __( 'Order #', 'wc-admin' ),
				key: 'id',
				required: true,
				isSortable: true,
			},
			{
				label: __( 'Status', 'wc-admin' ),
				key: 'status',
				required: false,
				isSortable: false,
			},
			{
				label: __( 'Customer', 'wc-admin' ),
				key: 'customer_id',
				required: false,
				isSortable: false,
			},
			{
				label: __( 'Product(s)', 'wc-admin' ),
				key: 'products',
				required: false,
				isSortable: false,
			},
			{
				label: __( 'Items Sold', 'wc-admin' ),
				key: 'items_sold',
				required: false,
				isSortable: false,
				isNumeric: true,
			},
			{
				label: __( 'Coupon(s)', 'wc-admin' ),
				key: 'coupons',
				required: false,
				isSortable: false,
			},
			{
				label: __( 'N. Revenue', 'wc-admin' ),
				key: 'net_revenue',
				required: true,
				isSortable: false,
				isNumeric: true,
			},
		];
	}

	formatTableData( data ) {
		return map( data, row => {
			const {
				date_created,
				id,
				status,
				customer_id,
				line_items,
				coupon_lines,
				currency,
				total,
				total_tax,
				shipping_total,
				discount_total,
			} = row;

			return {
				date: date_created,
				id,
				status,
				customer_id,
				line_items,
				items_sold: line_items.reduce( ( acc, item ) => item.quantity + acc, 0 ),
				coupon_lines,
				currency,
				net_revenue: getCurrencyFormatDecimal(
					total - total_tax - shipping_total - discount_total
				),
			};
		} );
	}

	getRowsContent( tableData ) {
		const { query } = this.props;
		const currentInterval = getIntervalForQuery( query );
		const { tableFormat } = getDateFormatsForInterval( currentInterval );

		return map( tableData, row => {
			const {
				date,
				id,
				status,
				customer_id,
				line_items,
				items_sold,
				coupon_lines,
				currency,
				net_revenue,
			} = row;

			const products = line_items
				.sort( ( itemA, itemB ) => itemB.quantity - itemA.quantity )
				.map( item => ( {
					label: item.name,
					href: 'post.php?post=' + item.product_id + '&action=edit',
					quantity: item.quantity,
				} ) );

			const coupons = coupon_lines.map( coupon => ( {
				label: coupon.code,
				// @TODO It should link to the coupons report
				href: 'edit.php?s=' + coupon.code + '&post_type=shop_coupon',
			} ) );

			return [
				{
					display: formatDate( tableFormat, date ),
					value: date,
				},
				{
					display: <a href={ getAdminLink( 'post.php?post=' + id + '&action=edit' ) }>{ id }</a>,
					value: id,
				},
				{
					display: (
						<OrderStatus className="woocommerce-orders-table__status" order={ { status } } />
					),
					value: status,
				},
				{
					// @TODO This should display customer type (new/returning) once it's
					// implemented in the API.
					display: customer_id,
					value: customer_id,
				},
				{
					display: this.renderList(
						products.length ? [ products[ 0 ] ] : [],
						products.map( product => ( {
							label: sprintf( __( '%s× %s', 'wc-admin' ), product.quantity, product.label ),
							href: product.href,
						} ) )
					),
					value: products.map( product => product.label ).join( ' ' ),
				},
				{
					display: items_sold,
					value: items_sold,
				},
				{
					display: this.renderList( coupons.length ? [ coupons[ 0 ] ] : [], coupons ),
					value: coupons.map( item => item.code ).join( ' ' ),
				},
				{
					display: formatCurrency( net_revenue, currency ),
					value: net_revenue,
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
				label: _n( 'order', 'orders', totals.num_items_sold, 'wc-admin' ),
				value: totals.orders_count,
			},
			{
				label: _n( 'new customer', 'new customers', totals.num_new_customers, 'wc-admin' ),
				value: totals.num_new_customers,
			},
			{
				label: _n(
					'returning customer',
					'returning customers',
					totals.num_returning_customers,
					'wc-admin'
				),
				value: totals.num_returning_customers,
			},
			{
				label: _n( 'product', 'products', totals.products, 'wc-admin' ),
				value: totals.products,
			},
			{
				label: _n( 'item sold', 'items sold', totals.num_items_sold, 'wc-admin' ),
				value: totals.num_items_sold,
			},
			{
				label: _n( 'coupon', 'coupons', totals.coupons, 'wc-admin' ),
				value: totals.coupons,
			},
			{
				label: __( 'net revenue', 'wc-admin' ),
				value: formatCurrency( totals.net_revenue ),
			},
		];
	}

	renderLinks( items = [] ) {
		return items.map( ( item, i ) => (
			<Link href={ item.href } key={ i } type="wp-admin">
				{ item.label }
			</Link>
		) );
	}

	renderList( visibleItems, popoverItems ) {
		return (
			<Fragment>
				{ this.renderLinks( visibleItems ) }
				{ popoverItems.length > 1 && <ViewMoreList items={ this.renderLinks( popoverItems ) } /> }
			</Fragment>
		);
	}

	render() {
		const { isTableDataError, isTableDataRequesting, primaryData, query, orders } = this.props;
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
		const rows = this.getRowsContent(
			orderBy( this.formatTableData( orders ), tableQuery.orderby, tableQuery.order )
		);
		const rowsPerPage = parseInt( tableQuery.per_page ) || QUERY_DEFAULTS.pageSize;
		const totalRows = get( primaryData, [ 'data', 'totals', 'orders_count' ], orders.length );
		const summary = primaryData.data.totals ? this.getSummary( primaryData.data.totals ) : null;

		return (
			<TableCard
				title={ __( 'Orders', 'wc-admin' ) }
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
		const primaryData = getReportChartData( 'orders', 'primary', query, select );
		const filterQuery = getFilterQuery( 'orders', query );

		const { getOrders, isGetOrdersError, isGetOrdersRequesting } = select( 'wc-admin' );
		const tableQuery = {
			orderby: query.orderby || 'date',
			order: query.order || 'asc',
			page: query.page || 1,
			per_page: query.per_page || QUERY_DEFAULTS.pageSize,
			after: appendTimestamp( datesFromQuery.primary.after, 'start' ),
			before: appendTimestamp( datesFromQuery.primary.before, 'end' ),
			status: [ 'processing', 'on-hold', 'completed' ],
			...filterQuery,
		};
		const orders = getOrders( tableQuery );
		const isTableDataError = isGetOrdersError( tableQuery );
		const isTableDataRequesting = isGetOrdersRequesting( tableQuery );

		return {
			isTableDataError,
			isTableDataRequesting,
			orders,
			primaryData,
		};
	} )
)( OrdersReportTable );
