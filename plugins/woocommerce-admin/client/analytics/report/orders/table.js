/** @format */
/**
 * External dependencies
 */
import { __, _n, _x, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { map } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { Date, Link, OrderStatus, ViewMoreList } from '@woocommerce/components';
import { defaultTableDateFormat } from '@woocommerce/date';
import { formatCurrency } from '@woocommerce/currency';
import { numberFormat } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import ReportTable from 'analytics/components/report-table';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import './style.scss';

export default class OrdersReportTable extends Component {
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
		const persistedQuery = getPersistedQuery( query );
		return map( tableData, row => {
			const {
				currency,
				customer_type,
				date_created,
				extended_info,
				net_total,
				num_items_sold,
				order_id,
				status,
			} = row;
			const { coupons, products } = extended_info;

			const formattedProducts = products
				.sort( ( itemA, itemB ) => itemB.quantity - itemA.quantity )
				.map( item => ( {
					label: item.name,
					quantity: item.quantity,
					href: getNewPath( persistedQuery, 'products', {
						filter: 'single_product',
						products: item.id,
					} ),
				} ) );

			const formattedCoupons = coupons.map( coupon => ( {
				label: coupon.code,
				href: getNewPath( persistedQuery, 'coupons', {
					filter: 'single_coupon',
					coupons: coupon.id,
				} ),
			} ) );

			return [
				{
					display: <Date date={ date_created } visibleFormat={ defaultTableDateFormat } />,
					value: date_created,
				},
				{
					display: (
						<Link href={ 'post.php?post=' + order_id + '&action=edit' } type="wp-admin">
							{ order_id }
						</Link>
					),
					value: order_id,
				},
				{
					display: (
						<OrderStatus className="woocommerce-orders-table__status" order={ { status } } />
					),
					value: status,
				},
				{
					display:
						customer_type === 'new'
							? _x( 'New', 'customer type', 'wc-admin' )
							: _x( 'Returning', 'customer type', 'wc-admin' ),
					value: customer_type,
				},
				{
					display: this.renderList(
						formattedProducts.length ? [ formattedProducts[ 0 ] ] : [],
						formattedProducts.map( product => ( {
							label: sprintf( __( '%s× %s', 'wc-admin' ), product.quantity, product.label ),
							href: product.href,
						} ) )
					),
					value: formattedProducts.map( product => product.label ).join( ' ' ),
				},
				{
					display: numberFormat( num_items_sold ),
					value: num_items_sold,
				},
				{
					display: this.renderList(
						formattedCoupons.length ? [ formattedCoupons[ 0 ] ] : [],
						formattedCoupons
					),
					value: formattedCoupons.map( item => item.code ).join( ' ' ),
				},
				{
					display: formatCurrency( net_total, currency ),
					value: net_total,
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
			<Link href={ item.href } key={ i } type="wc-admin">
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
		const { query } = this.props;

		return (
			<ReportTable
				endpoint="orders"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				getSummary={ this.getSummary }
				query={ query }
				tableQuery={ {
					extended_info: true,
				} }
				title={ __( 'Orders', 'wc-admin' ) }
				columnPrefsKey="orders_report_columns"
			/>
		);
	}
}
