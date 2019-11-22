/** @format */
/**
 * External dependencies
 */
import { __, _n, _x } from '@wordpress/i18n';
import { Component } from '@wordpress/element';

/**
 * WooCommerce dependencies
 */
import { Link } from '@woocommerce/components';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import { formatValue } from 'lib/number-format';
import { getAdminLink, getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import ReportTable from 'analytics/components/report-table';
import { isLowStock } from './utils';

const stockStatuses = getSetting( 'stockStatuses', {} );

export default class StockReportTable extends Component {
	constructor() {
		super();

		this.getHeadersContent = this.getHeadersContent.bind( this );
		this.getRowsContent = this.getRowsContent.bind( this );
		this.getSummary = this.getSummary.bind( this );
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Product / Variation', 'woocommerce-admin' ),
				key: 'title',
				required: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'SKU', 'woocommerce-admin' ),
				key: 'sku',
				isSortable: true,
			},
			{
				label: __( 'Status', 'woocommerce-admin' ),
				key: 'stock_status',
				isSortable: true,
				defaultSort: true,
			},
			{
				label: __( 'Stock', 'woocommerce-admin' ),
				key: 'stock_quantity',
				isSortable: true,
			},
		];
	}

	getRowsContent( products ) {
		const { query } = this.props;
		const persistedQuery = getPersistedQuery( query );

		return products.map( product => {
			const {
				id,
				manage_stock,
				name,
				parent_id,
				sku,
				stock_quantity,
				stock_status,
				low_stock_amount,
			} = product;

			const productDetailLink = getNewPath( persistedQuery, '/analytics/products', {
				filter: 'single_product',
				products: parent_id || id,
			} );

			const nameLink = (
				<Link href={ productDetailLink } type="wc-admin">
					{ name }
				</Link>
			);

			const editProductLink = getAdminLink( 'post.php?action=edit&post=' + ( parent_id || id ) );
			const stockStatusLink = isLowStock( stock_status, stock_quantity, low_stock_amount ) ? (
				<Link href={ editProductLink } type="wp-admin">
					{ _x( 'Low', 'Indication of a low quantity', 'woocommerce-admin' ) }
				</Link>
			) : (
				<Link href={ editProductLink } type="wp-admin">
					{ stockStatuses[ stock_status ] }
				</Link>
			);

			return [
				{
					display: nameLink,
					value: name,
				},
				{
					display: sku,
					value: sku,
				},
				{
					display: stockStatusLink,
					value: stock_status,
				},
				{
					display: manage_stock
						? formatValue( 'number', stock_quantity )
						: __( 'N/A', 'woocommerce-admin' ),
					value: stock_quantity,
				},
			];
		} );
	}

	getSummary( totals ) {
		const { products = 0, outofstock = 0, lowstock = 0, instock = 0, onbackorder = 0 } = totals;
		return [
			{
				label: _n( 'product', 'products', products, 'woocommerce-admin' ),
				value: formatValue( 'number', products ),
			},
			{
				label: __( 'out of stock', outofstock, 'woocommerce-admin' ),
				value: formatValue( 'number', outofstock ),
			},
			{
				label: __( 'low stock', lowstock, 'woocommerce-admin' ),
				value: formatValue( 'number', lowstock ),
			},
			{
				label: __( 'on backorder', onbackorder, 'woocommerce-admin' ),
				value: formatValue( 'number', onbackorder ),
			},
			{
				label: __( 'in stock', instock, 'woocommerce-admin' ),
				value: formatValue( 'number', instock ),
			},
		];
	}

	render() {
		const { advancedFilters, filters, query } = this.props;

		return (
			<ReportTable
				endpoint="stock"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				getSummary={ this.getSummary }
				query={ query }
				tableQuery={ {
					orderby: query.orderby || 'stock_status',
					order: query.order || 'asc',
					type: query.type || 'all',
				} }
				title={ __( 'Stock', 'woocommerce-admin' ) }
				filters={ filters }
				advancedFilters={ advancedFilters }
			/>
		);
	}
}
