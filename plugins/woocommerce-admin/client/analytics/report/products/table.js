/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { map, orderBy } from 'lodash';

/**
 * Internal dependencies
 */
import { Card, Link, TableCard, TablePlaceholder } from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal } from 'lib/currency';
import { getNewPath, getTimeRelatedQuery, onQueryChange } from 'lib/nav-utils';
import ReportError from 'analytics/components/report-error';
import { QUERY_DEFAULTS } from 'store/constants';

export default class ProductsReportTable extends Component {
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

	renderPlaceholderTable( tableQuery ) {
		const headers = this.getHeadersContent();

		return (
			<Card
				title={ __( 'Products', 'wc-admin' ) }
				className="woocommerce-analytics__table-placeholder"
			>
				<TablePlaceholder
					caption={ __( 'Products', 'wc-admin' ) }
					headers={ headers }
					query={ tableQuery }
				/>
			</Card>
		);
	}

	renderTable( tableQuery ) {
		const { products, totalRows } = this.props;

		const rowsPerPage = parseInt( tableQuery.per_page ) || QUERY_DEFAULTS.pageSize;
		const orderedProducts = orderBy( products, tableQuery.orderby, tableQuery.order );
		const rows = this.getRowsContent( orderedProducts );

		const headers = this.getHeadersContent();
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
				compareBy={ 'product' }
				onQueryChange={ onQueryChange }
				query={ tableQuery }
				summary={ null } // @TODO
				downloadable
			/>
		);
	}

	render() {
		const { isError, isRequesting, query } = this.props;

		if ( isError ) {
			return <ReportError isError />;
		}

		const tableQuery = {
			...query,
			orderby: query.orderby || 'items_sold',
			order: query.order || 'desc',
		};

		if ( isRequesting ) {
			return this.renderPlaceholderTable( tableQuery );
		}

		return this.renderTable( tableQuery );
	}
}
