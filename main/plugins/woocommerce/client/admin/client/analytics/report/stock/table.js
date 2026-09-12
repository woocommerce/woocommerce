/**
 * External dependencies
 */
import { __, _n, _x } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { Link } from '@woocommerce/components';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import { formatValue } from '@woocommerce/number';
import { getAdminLink } from '@woocommerce/settings';
import { CurrencyContext } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import ReportTable from '../../components/report-table';
import { isLowStock } from './utils';
import { getAdminSetting } from '~/utils/admin-settings';

const stockStatuses = getAdminSetting( 'stockStatuses', {} );

class StockReportTable extends Component {
	constructor() {
		super();

		this.getHeadersContent = this.getHeadersContent.bind( this );
		this.getRowsContent = this.getRowsContent.bind( this );
		this.getSummary = this.getSummary.bind( this );
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Product / Variation', 'woocommerce' ),
				key: 'title',
				required: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'SKU', 'woocommerce' ),
				key: 'sku',
				isSortable: true,
			},
			{
				label: __( 'Status', 'woocommerce' ),
				key: 'stock_status',
				isSortable: true,
				defaultSort: true,
			},
			{
				label: __( 'Stock', 'woocommerce' ),
				key: 'stock_quantity',
				isSortable: true,
			},
		];
	}

	getRowsContent( products = [] ) {
		const { query } = this.props;
		const persistedQuery = getPersistedQuery( query );

		return products.map( ( product ) => {
			const {
				id,
				manage_stock: manageStock,
				parent_id: parentId,
				sku,
				stock_quantity: stockQuantity,
				stock_status: stockStatus,
				low_stock_amount: lowStockAmount,
			} = product;

			const name = decodeEntities( product.name );

			const productDetailLink = getNewPath(
				persistedQuery,
				'/analytics/products',
				{
					filter: 'single_product',
					products: parentId || id,
				}
			);

			const nameLink = (
				<Link href={ productDetailLink } type="wc-admin">
					{ name }
				</Link>
			);

			const editProductLink = getAdminLink(
				'post.php?action=edit&post=' + ( parentId || id )
			);
			const stockStatusLink = isLowStock(
				stockStatus,
				stockQuantity,
				lowStockAmount
			) ? (
				<Link href={ editProductLink } type="wp-admin">
					{ _x(
						'Low',
						'Indication of a low quantity',
						'woocommerce'
					) }
				</Link>
			) : (
				<Link href={ editProductLink } type="wp-admin">
					{ stockStatuses[ stockStatus ] }
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
					value: stockStatuses[ stockStatus ],
				},
				{
					display: manageStock
						? formatValue(
								this.context.getCurrencyConfig(),
								'number',
								stockQuantity
						  )
						: __( 'N/A', 'woocommerce' ),
					value: stockQuantity,
				},
			];
		} );
	}

	getSummary( totals ) {
		const {
			products = 0,
			outofstock = 0,
			lowstock = 0,
			instock = 0,
			onbackorder = 0,
		} = totals;
		const currency = this.context.getCurrencyConfig();
		return [
			{
				label: _n( 'Product', 'Products', products, 'woocommerce' ),
				value: formatValue( currency, 'number', products ),
			},
			{
				label: __( 'Out of stock', 'woocommerce' ),
				value: formatValue( currency, 'number', outofstock ),
			},
			{
				label: __( 'Low stock', 'woocommerce' ),
				value: formatValue( currency, 'number', lowstock ),
			},
			{
				label: __( 'On backorder', 'woocommerce' ),
				value: formatValue( currency, 'number', onbackorder ),
			},
			{
				label: __( 'In stock', 'woocommerce' ),
				value: formatValue( currency, 'number', instock ),
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
				summaryFields={ [
					'products',
					'outofstock',
					'lowstock',
					'instock',
					'onbackorder',
				] }
				query={ query }
				tableQuery={ {
					orderby: query.orderby || 'stock_status',
					order: query.order || 'asc',
					type: query.type || 'all',
				} }
				title={ __( 'Stock', 'woocommerce' ) }
				filters={ filters }
				advancedFilters={ advancedFilters }
			/>
		);
	}
}

StockReportTable.contextType = CurrencyContext;

export default StockReportTable;
