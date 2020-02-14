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
import { defaultTableDateFormat } from 'lib/date';
import { formatCurrency, renderCurrency } from 'lib/currency-format';
import { formatValue } from 'lib/number-format';
import { getSetting } from '@woocommerce/wc-admin-settings';

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
				label: __( 'Net Sales', 'woocommerce-admin' ),
				screenReaderLabel: __( 'Net Sales', 'woocommerce-admin' ),
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
		return map( tableData, ( row ) => {
			const {
				currency,
				customer_type: customerType,
				date_created: dateCreated,
				net_total: netTotal,
				num_items_sold: numItemsSold,
				order_id: orderId,
				order_number: orderNumber,
				parent_id: parentId,
				status,
			} = row;
			const extendedInfo = row.extended_info || {};
			const { coupons, products } = extendedInfo;

			const formattedProducts = products
				.sort( ( itemA, itemB ) => itemB.quantity - itemA.quantity )
				.map( ( item ) => ( {
					label: item.name,
					quantity: item.quantity,
					href: getNewPath( persistedQuery, '/analytics/products', {
						filter: 'single_product',
						products: item.id,
					} ),
				} ) );

			const formattedCoupons = coupons.map( ( coupon ) => ( {
				label: coupon.code,
				href: getNewPath( persistedQuery, '/analytics/coupons', {
					filter: 'single_coupon',
					coupons: coupon.id,
				} ),
			} ) );

			return [
				{
					display: (
						<Date
							date={ dateCreated }
							visibleFormat={ defaultTableDateFormat }
						/>
					),
					value: dateCreated,
				},
				{
					display: (
						<Link
							href={
								'post.php?post=' +
								( parentId ? parentId : orderId ) +
								'&action=edit' +
								( parentId ? '#order_refunds' : '' )
							}
							type="wp-admin"
						>
							{ orderNumber }
						</Link>
					),
					value: orderNumber,
				},
				{
					display: (
						<OrderStatus
							className="woocommerce-orders-table__status"
							order={ { status } }
							orderStatusMap={ getSetting( 'orderStatuses', {} ) }
						/>
					),
					value: status,
				},
				{
					display: this.getCustomerType( customerType ),
					value: customerType,
				},
				{
					display: this.renderList(
						formattedProducts.length
							? [ formattedProducts[ 0 ] ]
							: [],
						formattedProducts.map( ( product ) => ( {
							label: sprintf(
								__( '%s× %s', 'woocommerce-admin' ),
								product.quantity,
								product.label
							),
							href: product.href,
						} ) )
					),
					value: formattedProducts
						.map( ( { quantity, label } ) =>
							sprintf(
								__( '%s× %s', 'woocommerce-admin' ),
								quantity,
								label
							)
						)
						.join( ', ' ),
				},
				{
					display: formatValue( 'number', numItemsSold ),
					value: numItemsSold,
				},
				{
					display: this.renderList(
						formattedCoupons.length
							? [ formattedCoupons[ 0 ] ]
							: [],
						formattedCoupons
					),
					value: formattedCoupons
						.map( ( coupon ) => coupon.label )
						.join( ', ' ),
				},
				{
					display: renderCurrency( netTotal, currency ),
					value: netTotal,
				},
			];
		} );
	}

	getSummary( totals ) {
		const {
			orders_count: ordersCount = 0,
			num_new_customers: numNewCustomers = 0,
			num_returning_customers: numReturningCustomers = 0,
			products = 0,
			num_items_sold: numItemsSold = 0,
			coupons_count: couponsCount = 0,
			net_revenue: netRevenue = 0,
		} = totals;
		return [
			{
				label: _n(
					'order',
					'orders',
					ordersCount,
					'woocommerce-admin'
				),
				value: formatValue( 'number', ordersCount ),
			},
			{
				label: _n(
					'new customer',
					'new customers',
					numNewCustomers,
					'woocommerce-admin'
				),
				value: formatValue( 'number', numNewCustomers ),
			},
			{
				label: _n(
					'returning customer',
					'returning customers',
					numReturningCustomers,
					'woocommerce-admin'
				),
				value: formatValue( 'number', numReturningCustomers ),
			},
			{
				label: _n(
					'product',
					'products',
					products,
					'woocommerce-admin'
				),
				value: formatValue( 'number', products ),
			},
			{
				label: _n(
					'item sold',
					'items sold',
					numItemsSold,
					'woocommerce-admin'
				),
				value: formatValue( 'number', numItemsSold ),
			},
			{
				label: _n(
					'coupon',
					'coupons',
					couponsCount,
					'woocommerce-admin'
				),
				value: formatValue( 'number', couponsCount ),
			},
			{
				label: __( 'net sales', 'woocommerce-admin' ),
				value: formatCurrency( netRevenue ),
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
				{ popoverItems.length > 1 && (
					<ViewMoreList items={ this.renderLinks( popoverItems ) } />
				) }
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
