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
import { formatCurrency, getCurrencyFormatDecimal, renderCurrency } from 'lib/currency-format';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import { Link, Tag } from '@woocommerce/components';
import { formatValue } from 'lib/number-format';
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import CategoryBreacrumbs from '../categories/breadcrumbs';
import { isLowStock } from './utils';
import ReportTable from 'analytics/components/report-table';
import withSelect from 'wc-api/with-select';
import './style.scss';

const manageStock = getSetting( 'manageStock', 'no' );
const stockStatuses = getSetting( 'stockStatuses', {} );

class ProductsReportTable extends Component {
	constructor() {
		super();

		this.getHeadersContent = this.getHeadersContent.bind( this );
		this.getRowsContent = this.getRowsContent.bind( this );
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Product Title', 'woocommerce-admin' ),
				key: 'product_name',
				required: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'SKU', 'woocommerce-admin' ),
				key: 'sku',
				hiddenByDefault: true,
				isSortable: true,
			},
			{
				label: __( 'Items Sold', 'woocommerce-admin' ),
				key: 'items_sold',
				required: true,
				defaultSort: true,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Net Sales', 'woocommerce-admin' ),
				screenReaderLabel: __( 'Net Sales', 'woocommerce-admin' ),
				key: 'net_revenue',
				required: true,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Orders', 'woocommerce-admin' ),
				key: 'orders_count',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Category', 'woocommerce-admin' ),
				key: 'product_cat',
			},
			{
				label: __( 'Variations', 'woocommerce-admin' ),
				key: 'variations',
				isSortable: true,
			},
			'yes' === manageStock
				? {
						label: __( 'Status', 'woocommerce-admin' ),
						key: 'stock_status',
					}
				: null,
			'yes' === manageStock
				? {
						label: __( 'Stock', 'woocommerce-admin' ),
						key: 'stock',
						isNumeric: true,
					}
				: null,
		].filter( Boolean );
	}

	getRowsContent( data = [] ) {
		const { query } = this.props;
		const persistedQuery = getPersistedQuery( query );

		return map( data, row => {
			const { product_id, items_sold, net_revenue, orders_count } = row;
			const extended_info = row.extended_info || {};
			const {
				category_ids,
				low_stock_amount,
				manage_stock,
				name,
				sku,
				stock_status,
				stock_quantity,
				variations = [],
			} = extended_info;
			const ordersLink = getNewPath( persistedQuery, '/analytics/orders', {
				filter: 'advanced',
				product_includes: product_id,
			} );
			const productDetailLink = getNewPath( persistedQuery, '/analytics/products', {
				filter: 'single_product',
				products: product_id,
			} );
			const { categories } = this.props;

			const productCategories =
				( category_ids &&
					category_ids.map( category_id => categories.get( category_id ) ).filter( Boolean ) ) ||
				[];

			const stockStatus = isLowStock( stock_status, stock_quantity, low_stock_amount ) ? (
				<Link href={ 'post.php?action=edit&post=' + product_id } type="wp-admin">
					{ _x( 'Low', 'Indication of a low quantity', 'woocommerce-admin' ) }
				</Link>
			) : (
				stockStatuses[ stock_status ]
			);

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
					display: formatValue( 'number', items_sold ),
					value: items_sold,
				},
				{
					display: renderCurrency( net_revenue ),
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
										_x( '+%d more', 'categories', 'woocommerce-admin' ),
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
					display: formatValue( 'number', variations.length ),
					value: variations.length,
				},
				'yes' === manageStock
					? {
							display: manage_stock ? stockStatus : __( 'N/A', 'woocommerce-admin' ),
							value: manage_stock ? stockStatuses[ stock_status ] : null,
						}
					: null,
				'yes' === manageStock
					? {
							display: manage_stock
								? formatValue( 'number', stock_quantity )
								: __( 'N/A', 'woocommerce-admin' ),
							value: stock_quantity,
						}
					: null,
			].filter( Boolean );
		} );
	}

	getSummary( totals ) {
		const { products_count = 0, items_sold = 0, net_revenue = 0, orders_count = 0 } = totals;
		return [
			{
				label: _n( 'product', 'products', products_count, 'woocommerce-admin' ),
				value: formatValue( 'number', products_count ),
			},
			{
				label: _n( 'item sold', 'items sold', items_sold, 'woocommerce-admin' ),
				value: formatValue( 'number', items_sold ),
			},
			{
				label: __( 'net sales', 'woocommerce-admin' ),
				value: formatCurrency( net_revenue ),
			},
			{
				label: _n( 'orders', 'orders', orders_count, 'woocommerce-admin' ),
				value: formatValue( 'number', orders_count ),
			},
		];
	}

	render() {
		const {
			advancedFilters,
			baseSearchQuery,
			filters,
			hideCompare,
			isRequesting,
			query,
		} = this.props;

		const labels = {
			helpText: __( 'Check at least two products below to compare', 'woocommerce-admin' ),
			placeholder: __( 'Search by product name or SKU', 'woocommerce-admin' ),
		};

		return (
			<ReportTable
				compareBy={ hideCompare ? undefined : 'products' }
				endpoint="products"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				getSummary={ this.getSummary }
				itemIdField="product_id"
				isRequesting={ isRequesting }
				labels={ labels }
				query={ query }
				searchBy="products"
				baseSearchQuery={ baseSearchQuery }
				tableQuery={ {
					orderby: query.orderby || 'items_sold',
					order: query.order || 'desc',
					extended_info: true,
					segmentby: query.segmentby,
				} }
				title={ __( 'Products', 'woocommerce-admin' ) }
				columnPrefsKey="products_report_columns"
				filters={ filters }
				advancedFilters={ advancedFilters }
			/>
		);
	}
}

export default compose(
	withSelect( ( select, props ) => {
		const { query, isRequesting } = props;
		if ( isRequesting || ( query.search && ! ( query.products && query.products.length ) ) ) {
			return {};
		}

		const { getItems, getItemsError, isGetItemsRequesting } = select( 'wc-api' );
		const tableQuery = {
			per_page: -1,
		};

		const categories = getItems( 'categories', tableQuery );
		const isError = Boolean( getItemsError( 'categories', tableQuery ) );
		const isLoading = isGetItemsRequesting( 'categories', tableQuery );

		return { categories, isError, isRequesting: isLoading };
	} )
)( ProductsReportTable );
