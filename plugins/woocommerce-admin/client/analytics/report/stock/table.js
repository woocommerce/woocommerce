/** @format */
/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Component } from '@wordpress/element';

/**
 * WooCommerce dependencies
 */
import { Link } from '@woocommerce/components';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import { numberFormat } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import ReportTable from 'analytics/components/report-table';

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
				label: __( 'Product / Variation', 'wc-admin' ),
				key: 'title',
				required: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'SKU', 'wc-admin' ),
				key: 'sku',
				isSortable: true,
			},
			{
				label: __( 'Status', 'wc-admin' ),
				key: 'stock_status',
				isSortable: true,
				defaultSort: true,
			},
			{
				label: __( 'Stock', 'wc-admin' ),
				key: 'stock_quantity',
				isSortable: true,
			},
		];
	}

	getRowsContent( products ) {
		const { query } = this.props;
		const persistedQuery = getPersistedQuery( query );
		const { stockStatuses } = wcSettings;

		return products.map( product => {
			const { id, manage_stock, name, parent_id, sku, stock_quantity, stock_status } = product;

			const productDetailLink = getNewPath( persistedQuery, 'products', {
				filter: 'single_product',
				products: parent_id || id,
			} );

			const formattedName = name.replace( ' - ', ' / ' );

			const nameLink = (
				<Link href={ productDetailLink } type="wc-admin">
					{ formattedName }
				</Link>
			);

			const stockStatusLink = (
				<Link href={ 'post.php?action=edit&post=' + ( parent_id || id ) } type="wp-admin">
					{ stockStatuses[ stock_status ] }
				</Link>
			);

			return [
				{
					display: nameLink,
					value: formattedName,
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
					display: manage_stock ? numberFormat( stock_quantity ) : __( 'N/A', 'wc-admin' ),
					value: stock_quantity,
				},
			];
		} );
	}

	getSummary( totals ) {
		const { products = 0, outofstock = 0, lowstock = 0, instock = 0, onbackorder = 0 } = totals;
		return [
			{
				label: _n( 'product', 'products', products, 'wc-admin' ),
				value: numberFormat( products ),
			},
			{
				label: __( 'out of stock', outofstock, 'wc-admin' ),
				value: numberFormat( outofstock ),
			},
			{
				label: __( 'low stock', lowstock, 'wc-admin' ),
				value: numberFormat( lowstock ),
			},
			{
				label: __( 'on backorder', onbackorder, 'wc-admin' ),
				value: numberFormat( onbackorder ),
			},
			{
				label: __( 'in stock', instock, 'wc-admin' ),
				value: numberFormat( instock ),
			},
		];
	}

	render() {
		const { query } = this.props;

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
				title={ __( 'Stock', 'wc-admin' ) }
			/>
		);
	}
}
