/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { map } from 'lodash';
import { Date, Link, OrderStatus, ViewMoreList } from '@woocommerce/components';
import { formatValue } from '@woocommerce/number';
import { getSetting } from '@woocommerce/wc-admin-settings';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import { defaultTableDateFormat } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import ReportTable from '../../components/report-table';
import { CurrencyContext } from '../../../lib/currency-context';
import './style.scss';

const capitalizeFirstLetter = ( expr ) =>
	expr.charAt( 0 ).toUpperCase() + expr.slice( 1 );

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
				label: __( 'Customer type', 'woocommerce-admin' ),
				key: 'customer_type',
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
				label: __( 'Items sold', 'woocommerce-admin' ),
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
				label: __( 'Net sales', 'woocommerce-admin' ),
				screenReaderLabel: __( 'Net sales', 'woocommerce-admin' ),
				key: 'net_total',
				required: true,
				isSortable: true,
				isNumeric: true,
			},
		];
	}

	getCustomerName( customer ) {
		const { first_name: firstName, last_name: lastName } = customer || {};

		if ( ! firstName && ! lastName ) {
			return '';
		}

		return [ firstName, lastName ].join( ' ' );
	}

	getRowsContent( tableData ) {
		const { query } = this.props;
		const persistedQuery = getPersistedQuery( query );
		const dateFormat = getSetting( 'dateFormat', defaultTableDateFormat );
		const { render: renderCurrency, getCurrencyConfig } = this.context;

		return map( tableData, ( row ) => {
			const {
				currency,
				date_created: dateCreated,
				net_total: netTotal,
				num_items_sold: numItemsSold,
				order_id: orderId,
				order_number: orderNumber,
				parent_id: parentId,
				status,
				customer_type: customerType,
			} = row;
			const extendedInfo = row.extended_info || {};
			const { coupons, customer, products } = extendedInfo;

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
							visibleFormat={ dateFormat }
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
					display: this.getCustomerName( customer ),
					value: this.getCustomerName( customer ),
				},
				{
					display: capitalizeFirstLetter( customerType ),
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
					display: formatValue(
						getCurrencyConfig(),
						'number',
						numItemsSold
					),
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
			total_customers: totalCustomers = 0,
			products = 0,
			num_items_sold: numItemsSold = 0,
			coupons_count: couponsCount = 0,
			net_revenue: netRevenue = 0,
		} = totals;
		const { formatAmount, getCurrencyConfig } = this.context;
		const currency = getCurrencyConfig();
		return [
			{
				label: _n(
					'Order',
					'Orders',
					ordersCount,
					'woocommerce-admin'
				),
				value: formatValue( currency, 'number', ordersCount ),
			},
			{
				label: _n(
					' Customer',
					' Customers',
					totalCustomers,
					'woocommerce-admin'
				),
				value: formatValue( currency, 'number', totalCustomers ),
			},
			{
				label: _n(
					'Product',
					'Products',
					products,
					'woocommerce-admin'
				),
				value: formatValue( currency, 'number', products ),
			},
			{
				label: _n(
					'Item sold',
					'Items sold',
					numItemsSold,
					'woocommerce-admin'
				),
				value: formatValue( currency, 'number', numItemsSold ),
			},
			{
				label: _n(
					'Coupon',
					'Coupons',
					couponsCount,
					'woocommerce-admin'
				),
				value: formatValue( currency, 'number', couponsCount ),
			},
			{
				label: __( 'net sales', 'woocommerce-admin' ),
				value: formatAmount( netRevenue ),
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
				summaryFields={ [
					'orders_count',
					'total_customers',
					'products',
					'num_items_sold',
					'coupons_count',
					'net_revenue',
				] }
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

OrdersReportTable.contextType = CurrencyContext;

export default OrdersReportTable;
