/** @format */
/**
 * External dependencies
 */
import { __, _n, _x, sprintf } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { map } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import { Link, Tag } from '@woocommerce/components';
import { numberFormat } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import CategoryBreacrumbs from '../categories/breadcrumbs';
import { isLowStock } from './utils';
import ReportTable from 'analytics/components/report-table';
import withSelect from 'wc-api/with-select';
import './style.scss';

class ProductsReportTable extends Component {
	constructor() {
		super();

		this.getHeadersContent = this.getHeadersContent.bind( this );
		this.getRowsContent = this.getRowsContent.bind( this );
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Product Title', 'wc-admin' ),
				key: 'product_name',
				required: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'SKU', 'wc-admin' ),
				key: 'sku',
				hiddenByDefault: true,
				isSortable: true,
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
				label: __( 'N. Revenue', 'wc-admin' ),
				screenReaderLabel: __( 'Net Revenue', 'wc-admin' ),
				key: 'net_revenue',
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
				extended_info,
				items_sold,
				net_revenue,
				orders_count,
				variations = [], // @TODO
			} = row;
			const {
				category_ids,
				low_stock_amount,
				name,
				sku,
				stock_status,
				stock_quantity,
			} = extended_info;
			const ordersLink = getNewPath( persistedQuery, 'orders', {
				filter: 'advanced',
				product_includes: product_id,
			} );
			const productDetailLink = getNewPath( persistedQuery, 'products', {
				filter: 'single_product',
				products: product_id,
			} );
			const categories = this.props.categories;

			const productCategories =
				( category_ids &&
					category_ids.map( category_id => categories[ category_id ] ).filter( Boolean ) ) ||
				[];

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
					display: formatCurrency( net_revenue ),
					value: getCurrencyFormatDecimal( net_revenue ),
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
					display: (
						<div className="woocommerce-table__product-categories">
							{ productCategories[ 0 ] && (
								<CategoryBreacrumbs category={ productCategories[ 0 ] } categories={ categories } />
							) }
							{ productCategories.length > 1 && (
								<Tag
									label={ sprintf(
										_x( '+%d more', 'categories', 'wc-admin' ),
										productCategories.length - 1
									) }
									popoverContents={ productCategories.map( category => (
										<CategoryBreacrumbs
											category={ category }
											categories={ categories }
											key={ category.id }
											query={ query }
										/>
									) ) }
								/>
							) }
						</div>
					),
					value: productCategories.map( category => category.name ).join( ', ' ),
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
				label: __( 'net revenue', 'wc-admin' ),
				value: formatCurrency( totals.net_revenue ),
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

export default compose(
	withSelect( select => {
		const { getCategories, getCategoriesError, isGetCategoriesRequesting } = select( 'wc-api' );
		const tableQuery = {
			per_page: -1,
		};

		const categories = getCategories( tableQuery );
		const isError = Boolean( getCategoriesError( tableQuery ) );
		const isRequesting = isGetCategoriesRequesting( tableQuery );

		return { categories, isError, isRequesting };
	} )
)( ProductsReportTable );
