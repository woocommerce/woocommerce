/** @format */
/**
 * External dependencies
 */
import { __, _n, _x } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { map } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { Link } from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ReportTable from 'analytics/components/report-table';
import { numberFormat } from 'lib/number';
import { isLowStock } from './utils';

export default class ProductsReportTable extends Component {
	constructor() {
		super();

		this.getHeadersContent = this.getHeadersContent.bind( this );
		this.getRowsContent = this.getRowsContent.bind( this );
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Product Title', 'wc-admin' ),
				key: 'name',
				required: true,
				isLeftAligned: true,
			},
			{
				label: __( 'SKU', 'wc-admin' ),
				key: 'sku',
				hiddenByDefault: true,
			},
			{
				label: __( 'Items Sold', 'wc-admin' ),
				key: 'items_sold',
				required: true,
				defaultSort: true,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'G. Revenue', 'wc-admin' ),
				screenReaderLabel: __( 'Gross Revenue', 'wc-admin' ),
				key: 'gross_revenue',
				required: true,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Orders', 'wc-admin' ),
				key: 'orders_count',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Category', 'wc-admin' ),
				key: 'product_cat',
			},
			{
				label: __( 'Variations', 'wc-admin' ),
				key: 'variation',
			},
			{
				label: __( 'Status', 'wc-admin' ),
				key: 'stock_status',
			},
			{
				label: __( 'Stock', 'wc-admin' ),
				key: 'stock',
				isNumeric: true,
			},
		];
	}

	getRowsContent( data = [] ) {
		const { stockStatuses } = wcSettings;
		const { query } = this.props;
		const persistedQuery = getPersistedQuery( query );

		return map( data, row => {
			const {
				product_id,
				sku = '', // @TODO
				extended_info,
				items_sold,
				gross_revenue,
				orders_count,
				categories = [], // @TODO
				variations = [], // @TODO
			} = row;
			const { name, stock_status, stock_quantity, low_stock_amount } = extended_info;
			const ordersLink = getNewPath( persistedQuery, 'orders', {
				filter: 'advanced',
				product_includes: product_id,
			} );
			const productDetailLink = getNewPath( persistedQuery, 'products', {
				filter: 'single_product',
				products: product_id,
			} );

			return [
				{
					display: (
						<Link href={ productDetailLink } type="wc-admin">
							{ name }
						</Link>
					),
					value: name,
				},
				{
					display: sku,
					value: sku,
				},
				{
					display: numberFormat( items_sold ),
					value: items_sold,
				},
				{
					display: formatCurrency( gross_revenue ),
					value: getCurrencyFormatDecimal( gross_revenue ),
				},
				{
					display: (
						<Link href={ ordersLink } type="wc-admin">
							{ orders_count }
						</Link>
					),
					value: orders_count,
				},
				{
					display: Array.isArray( categories )
						? categories.map( cat => cat.name ).join( ', ' )
						: '',
					value: Array.isArray( categories ) ? categories.map( cat => cat.name ).join( ', ' ) : '',
				},
				{
					display: numberFormat( variations.length ),
					value: variations.length,
				},
				{
					display: isLowStock( stock_status, stock_quantity, low_stock_amount ) ? (
						<Link href={ 'post.php?action=edit&post=' + product_id } type="wp-admin">
							{ _x( 'Low', 'Indication of a low quantity', 'wc-admin' ) }
						</Link>
					) : (
						stockStatuses[ stock_status ]
					),
					value: stockStatuses[ stock_status ],
				},
				{
					display: numberFormat( stock_quantity ),
					value: stock_quantity,
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
				label: _n( 'product sold', 'products sold', totals.products_count, 'wc-admin' ),
				value: numberFormat( totals.products_count ),
			},
			{
				label: _n( 'item sold', 'items sold', totals.items_sold, 'wc-admin' ),
				value: numberFormat( totals.items_sold ),
			},
			{
				label: __( 'gross revenue', 'wc-admin' ),
				value: formatCurrency( totals.gross_revenue ),
			},
			{
				label: _n( 'orders', 'orders', totals.orders_count, 'wc-admin' ),
				value: numberFormat( totals.orders_count ),
			},
		];
	}

	render() {
		const { query } = this.props;

		const labels = {
			helpText: __( 'Select at least two products to compare', 'wc-admin' ),
			placeholder: __( 'Search by product name or SKU', 'wc-admin' ),
		};

		return (
			<ReportTable
				compareBy="products"
				endpoint="products"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				getSummary={ this.getSummary }
				itemIdField="product_id"
				labels={ labels }
				query={ query }
				tableQuery={ {
					orderby: query.orderby || 'items_sold',
					order: query.order || 'desc',
					extended_info: true,
				} }
				title={ __( 'Products', 'wc-admin' ) }
				columnPrefsKey="products_report_columns"
			/>
		);
	}
}
