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
			const { id, name, parent_id, sku, stock_quantity, stock_status } = product;

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
				<Link href={ 'post.php?action=edit&post=' + parent_id || id } type="wp-admin">
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
				label: _n( 'product', 'products', totals.products, 'wc-admin' ),
				value: numberFormat( totals.products ),
			},
			{
				label: __( 'out of stock', totals.out_of_stock, 'wc-admin' ),
				value: numberFormat( totals.out_of_stock ),
			},
			{
				label: __( 'low stock', totals.low_stock, 'wc-admin' ),
				value: numberFormat( totals.low_stock ),
			},
			{
				label: __( 'in stock', totals.in_stock, 'wc-admin' ),
				value: numberFormat( totals.in_stock ),
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
				// getSummary={ this.getSummary }
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
