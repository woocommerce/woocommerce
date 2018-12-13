/** @format */
/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { format as formatDate } from '@wordpress/date';
import { compose } from '@wordpress/compose';
import { map } from 'lodash';

/**
 * WooCommerce dependencies
 */
import {
	appendTimestamp,
	getCurrentDates,
	getIntervalForQuery,
	getDateFormatsForInterval,
} from '@woocommerce/date';
import { Link, OrderStatus, ViewMoreList } from '@woocommerce/components';
import { formatCurrency } from '@woocommerce/currency';
import { getAdminLink } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { QUERY_DEFAULTS } from 'store/constants';
import { getFilterQuery } from 'store/reports/utils';
import { numberFormat } from 'lib/number';
import withSelect from 'wc-api/with-select';
import ReportTable from 'analytics/components/report-table';
import { formatTableOrders } from './utils';
import './style.scss';

class OrdersReportTable extends Component {
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
				label: __( 'Order #', 'wc-admin' ),
				screenReaderLabel: __( 'Order ID', 'wc-admin' ),
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
				screenReaderLabel: __( 'Products', 'wc-admin' ),
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
				screenReaderLabel: __( 'Coupons', 'wc-admin' ),
				key: 'coupons',
				required: false,
				isSortable: false,
			},
			{
				label: __( 'N. Revenue', 'wc-admin' ),
				screenReaderLabel: __( 'Net Revenue', 'wc-admin' ),
				key: 'net_revenue',
				required: true,
				isSortable: false,
				isNumeric: true,
			},
		];
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
					display: numberFormat( items_sold ),
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
				label: _n( 'order', 'orders', totals.orders_count, 'wc-admin' ),
				value: numberFormat( totals.orders_count ),
			},
			{
				label: _n( 'new customer', 'new customers', totals.num_new_customers, 'wc-admin' ),
				value: numberFormat( totals.num_new_customers ),
			},
			{
				label: _n(
					'returning customer',
					'returning customers',
					totals.num_returning_customers,
					'wc-admin'
				),
				value: numberFormat( totals.num_returning_customers ),
			},
			{
				label: _n( 'product', 'products', totals.products, 'wc-admin' ),
				value: numberFormat( totals.products ),
			},
			{
				label: _n( 'item sold', 'items sold', totals.num_items_sold, 'wc-admin' ),
				value: numberFormat( totals.num_items_sold ),
			},
			{
				label: _n( 'coupon', 'coupons', totals.coupons, 'wc-admin' ),
				value: numberFormat( totals.coupons ),
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
		const { query, tableData } = this.props;

		return (
			<ReportTable
				endpoint="orders"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				getSummary={ this.getSummary }
				query={ query }
				tableData={ tableData }
				title={ __( 'Orders', 'wc-admin' ) }
				columnPrefsKey="orders_report_columns"
			/>
		);
	}
}

export default compose(
	withSelect( ( select, props ) => {
		const { query } = props;
		const datesFromQuery = getCurrentDates( query );
		const filterQuery = getFilterQuery( 'orders', query );

		const { getOrders, getOrdersTotalCount, isGetOrdersError, isGetOrdersRequesting } = select(
			'wc-api'
		);

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
		const ordersTotalCount = getOrdersTotalCount( tableQuery );
		const isError = isGetOrdersError( tableQuery );
		const isRequesting = isGetOrdersRequesting( tableQuery );

		return {
			tableData: {
				items: {
					data: formatTableOrders( orders ),
					totalCount: ordersTotalCount,
				},
				isError,
				isRequesting,
				query: tableQuery,
			},
		};
	} )
)( OrdersReportTable );
