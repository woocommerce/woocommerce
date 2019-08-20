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
import { formatCurrency, renderCurrency } from '@woocommerce/currency';
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
				label: __( 'Date', 'woocommerce-admin' ),
				key: 'date',
				required: true,
				defaultSort: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'Order #', 'woocommerce-admin' ),
				screenReaderLabel: __( 'Order Number', 'woocommerce-admin' ),
				key: 'order_number',
				required: true,
			},
			{
				label: __( 'Status', 'woocommerce-admin' ),
				key: 'status',
				required: false,
				isSortable: false,
			},
			{
				label: __( 'Customer', 'woocommerce-admin' ),
				key: 'customer_id',
				required: false,
				isSortable: false,
			},
			{
				label: __( 'Product(s)', 'woocommerce-admin' ),
				screenReaderLabel: __( 'Products', 'woocommerce-admin' ),
				key: 'products',
				required: false,
				isSortable: false,
			},
			{
				label: __( 'Items Sold', 'woocommerce-admin' ),
				key: 'num_items_sold',
				required: false,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Coupon(s)', 'woocommerce-admin' ),
				screenReaderLabel: __( 'Coupons', 'woocommerce-admin' ),
				key: 'coupons',
				required: false,
				isSortable: false,
			},
			{
				label: __( 'N. Revenue', 'woocommerce-admin' ),
				screenReaderLabel: __( 'Net Revenue', 'woocommerce-admin' ),
				key: 'net_total',
				required: true,
				isSortable: true,
				isNumeric: true,
			},
		];
	}

	getCustomerType( customerType ) {
		switch ( customerType ) {
			case 'new':
				return _x( 'New', 'customer type', 'woocommerce-admin' );
			case 'returning':
				return _x( 'Returning', 'customer type', 'woocommerce-admin' );
			default:
				return _x( 'N/A', 'customer type', 'woocommerce-admin' );
		}
	}

	getRowsContent( tableData ) {
		const { query } = this.props;
		const persistedQuery = getPersistedQuery( query );
		return map( tableData, row => {
			const {
				currency,
				customer_type,
				date_created,
				net_total,
				num_items_sold,
				order_id,
				order_number,
				parent_id,
				status,
			} = row;
			const extended_info = row.extended_info || {};
			const { coupons, products } = extended_info;

			const formattedProducts = products
				.sort( ( itemA, itemB ) => itemB.quantity - itemA.quantity )
				.map( item => ( {
					label: item.name,
					quantity: item.quantity,
					href: getNewPath( persistedQuery, '/analytics/products', {
						filter: 'single_product',
						products: item.id,
					} ),
				} ) );

			const formattedCoupons = coupons.map( coupon => ( {
				label: coupon.code,
				href: getNewPath( persistedQuery, '/analytics/coupons', {
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
						<Link
							href={
								'post.php?post=' +
								( parent_id ? parent_id : order_id ) +
								'&action=edit' +
								( parent_id ? '#order_refunds' : '' )
							}
							type="wp-admin"
						>
							{ order_number }
						</Link>
					),
					value: order_number,
				},
				{
					display: (
						<OrderStatus className="woocommerce-orders-table__status" order={ { status } } />
					),
					value: status,
				},
				{
					display: this.getCustomerType( customer_type ),
					value: customer_type,
				},
				{
					display: this.renderList(
						formattedProducts.length ? [ formattedProducts[ 0 ] ] : [],
						formattedProducts.map( product => ( {
							label: sprintf(
								__( '%s× %s', 'woocommerce-admin' ),
								product.quantity,
								product.label
							),
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
					value: formattedCoupons.map( coupon => coupon.label ).join( ', ' ),
				},
				{
					display: renderCurrency( net_total, currency ),
					value: net_total,
				},
			];
		} );
	}

	getSummary( totals ) {
		const {
			orders_count = 0,
			num_new_customers = 0,
			num_returning_customers = 0,
			products = 0,
			num_items_sold = 0,
			coupons_count = 0,
			net_revenue = 0,
		} = totals;
		return [
			{
				label: _n( 'order', 'orders', orders_count, 'woocommerce-admin' ),
				value: numberFormat( orders_count ),
			},
			{
				label: _n( 'new customer', 'new customers', num_new_customers, 'woocommerce-admin' ),
				value: numberFormat( num_new_customers ),
			},
			{
				label: _n(
					'returning customer',
					'returning customers',
					num_returning_customers,
					'woocommerce-admin'
				),
				value: numberFormat( num_returning_customers ),
			},
			{
				label: _n( 'product', 'products', products, 'woocommerce-admin' ),
				value: numberFormat( products ),
			},
			{
				label: _n( 'item sold', 'items sold', num_items_sold, 'woocommerce-admin' ),
				value: numberFormat( num_items_sold ),
			},
			{
				label: _n( 'coupon', 'coupons', coupons_count, 'woocommerce-admin' ),
				value: numberFormat( coupons_count ),
			},
			{
				label: __( 'net revenue', 'woocommerce-admin' ),
				value: formatCurrency( net_revenue ),
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
		const { query, filters, advancedFilters } = this.props;

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
				title={ __( 'Orders', 'woocommerce-admin' ) }
				columnPrefsKey="orders_report_columns"
				filters={ filters }
				advancedFilters={ advancedFilters }
			/>
		);
	}
}
