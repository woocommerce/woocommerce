/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { get, map, orderBy } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { Link, TableCard } from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { getNewPath, getPersistedQuery, onQueryChange } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ReportError from 'analytics/components/report-error';
import { getReportChartData, getReportTableData } from 'store/reports/utils';
import { numberFormat } from 'lib/number';

class ProductsReportTable extends Component {
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
				name,
				items_sold,
				gross_revenue,
				orders_count,
				categories = [], // @TODO
				variations = [], // @TODO
				stock_status = 'outofstock', // @TODO
				stock_quantity = '0', // @TODO
			} = row;
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
					display: (
						<Link href={ 'post.php?action=edit&post=' + product_id } type="wp-admin">
							{ stockStatuses[ stock_status ] }
						</Link>
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

	render() {
		const { primaryData, tableData } = this.props;
		const { items, query } = tableData;
		const isError = tableData.isError || primaryData.isError;

		if ( isError ) {
			return <ReportError isError />;
		}

		const isRequesting = tableData.isRequesting || primaryData.isRequesting;

		const headers = this.getHeadersContent();
		const orderedProducts = orderBy( items, query.orderby, query.order );
		const rows = this.getRowsContent( orderedProducts );
		const totalRows = get( primaryData, [ 'data', 'totals', 'products_count' ], items.length );

		const labels = {
			helpText: __( 'Select at least two products to compare', 'wc-admin' ),
			placeholder: __( 'Search by product name or SKU', 'wc-admin' ),
		};

		return (
			<TableCard
				title={ __( 'Products', 'wc-admin' ) }
				rows={ rows }
				totalRows={ totalRows }
				rowsPerPage={ query.per_page }
				headers={ headers }
				labels={ labels }
				ids={ orderedProducts.map( p => p.product_id ) }
				isLoading={ isRequesting }
				compareBy={ 'products' }
				onQueryChange={ onQueryChange }
				query={ query }
				summary={ null } // @TODO
				downloadable
			/>
		);
	}
}

export default compose(
	withSelect( ( select, props ) => {
		const { query } = props;
		const primaryData = getReportChartData( 'products', 'primary', query, select );
		const tableData = getReportTableData( 'products', query, select, {
			orderby: query.orderby || 'items_sold',
			order: query.order || 'desc',
			extended_product_info: true,
		} );

		return {
			primaryData,
			tableData,
		};
	} )
)( ProductsReportTable );
