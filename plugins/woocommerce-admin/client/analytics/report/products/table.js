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
import { appendTimestamp, getCurrentDates } from '@woocommerce/date';
import { Link, TableCard } from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { getNewPath, getTimeRelatedQuery, onQueryChange } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ReportError from 'analytics/components/report-error';
import { getFilterQuery, getReportChartData } from 'store/reports/utils';
import { QUERY_DEFAULTS } from 'store/constants';

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
			},
		];
	}

	getRowsContent( data = [] ) {
		const { stockStatuses } = wcSettings;
		const { query } = this.props;
		const timeRelatedQuery = getTimeRelatedQuery( query );

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
			const ordersLink = getNewPath( timeRelatedQuery, 'orders', {
				filter: 'advanced',
				product_includes: product_id,
			} );

			return [
				{
					// @TODO Must link to the product detail report.
					display: name,
					value: name,
				},
				{
					display: sku,
					value: sku,
				},
				{
					display: items_sold,
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
					display: variations.length,
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
					display: stock_quantity,
					value: stock_quantity,
				},
			];
		} );
	}

	render() {
		const { isProductsError, isProductsRequesting, primaryData, products, tableQuery } = this.props;
		const isError = isProductsError || primaryData.isError;

		if ( isError ) {
			return <ReportError isError />;
		}

		const isRequesting = isProductsRequesting || primaryData.isRequesting;

		const headers = this.getHeadersContent();
		const orderedProducts = orderBy( products, tableQuery.orderby, tableQuery.order );
		const rows = this.getRowsContent( orderedProducts );
		const rowsPerPage = parseInt( tableQuery.per_page ) || QUERY_DEFAULTS.pageSize;
		const totalRows = get( primaryData, [ 'data', 'totals', 'products_count' ], products.length );

		const labels = {
			helpText: __( 'Select at least two products to compare', 'wc-admin' ),
			placeholder: __( 'Search by product name or SKU', 'wc-admin' ),
		};

		return (
			<TableCard
				title={ __( 'Products', 'wc-admin' ) }
				rows={ rows }
				totalRows={ totalRows }
				rowsPerPage={ rowsPerPage }
				headers={ headers }
				labels={ labels }
				ids={ orderedProducts.map( p => p.product_id ) }
				isLoading={ isRequesting }
				compareBy={ 'products' }
				onQueryChange={ onQueryChange }
				query={ tableQuery }
				summary={ null } // @TODO
				downloadable
			/>
		);
	}
}

export default compose(
	withSelect( ( select, props ) => {
		const { query } = props;
		const datesFromQuery = getCurrentDates( query );
		const primaryData = getReportChartData( 'products', 'primary', query, select );

		const { getProducts, isGetProductsError, isGetProductsRequesting } = select( 'wc-admin' );
		const filterQuery = getFilterQuery( 'products', query );
		const tableQuery = {
			orderby: query.orderby || 'items_sold',
			order: query.order || 'desc',
			page: query.page || 1,
			per_page: query.per_page || QUERY_DEFAULTS.pageSize,
			after: appendTimestamp( datesFromQuery.primary.after, 'start' ),
			before: appendTimestamp( datesFromQuery.primary.before, 'end' ),
			extended_product_info: true,
			...filterQuery,
		};
		const products = getProducts( tableQuery );
		const isProductsError = isGetProductsError( tableQuery );
		const isProductsRequesting = isGetProductsRequesting( tableQuery );

		return {
			isProductsError,
			isProductsRequesting,
			primaryData,
			products,
			tableQuery,
		};
	} )
)( ProductsReportTable );
