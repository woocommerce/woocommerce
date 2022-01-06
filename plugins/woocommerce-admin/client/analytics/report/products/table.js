/**
 * External dependencies
 */
import { __, _n, _x, sprintf } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { decodeEntities } from '@wordpress/html-entities';
import { withSelect } from '@wordpress/data';
import { map } from 'lodash';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import { Link, Tag } from '@woocommerce/components';
import { formatValue } from '@woocommerce/number';
import { getAdminLink } from '@woocommerce/settings';
import { ITEMS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import CategoryBreacrumbs from '../categories/breadcrumbs';
import { isLowStock } from './utils';
import ReportTable from '../../components/report-table';
import { CurrencyContext } from '../../../lib/currency-context';
import { getAdminSetting } from '~/utils/admin-settings';

import './style.scss';

const manageStock = getAdminSetting( 'manageStock', 'no' );
const stockStatuses = getAdminSetting( 'stockStatuses', {} );

class ProductsReportTable extends Component {
	constructor() {
		super();

		this.getHeadersContent = this.getHeadersContent.bind( this );
		this.getRowsContent = this.getRowsContent.bind( this );
		this.getSummary = this.getSummary.bind( this );
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Product title', 'woocommerce-admin' ),
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
				label: __( 'Items sold', 'woocommerce-admin' ),
				key: 'items_sold',
				required: true,
				defaultSort: true,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Net sales', 'woocommerce-admin' ),
				screenReaderLabel: __( 'Net sales', 'woocommerce-admin' ),
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
			manageStock === 'yes'
				? {
						label: __( 'Status', 'woocommerce-admin' ),
						key: 'stock_status',
				  }
				: null,
			manageStock === 'yes'
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
		const {
			render: renderCurrency,
			formatDecimal: getCurrencyFormatDecimal,
			getCurrencyConfig,
		} = this.context;
		const currency = getCurrencyConfig();

		return map( data, ( row ) => {
			const {
				product_id: productId,
				items_sold: itemsSold,
				net_revenue: netRevenue,
				orders_count: ordersCount,
			} = row;
			const extendedInfo = row.extended_info || {};
			const {
				category_ids: categoryIds,
				low_stock_amount: lowStockAmount,
				manage_stock: extendedInfoManageStock,
				sku,
				stock_status: extendedInfoStockStatus,
				stock_quantity: stockQuantity,
				variations = [],
			} = extendedInfo;

			const name = decodeEntities( extendedInfo.name );
			const ordersLink = getNewPath(
				persistedQuery,
				'/analytics/orders',
				{
					filter: 'advanced',
					product_includes: productId,
				}
			);
			const productDetailLink = getNewPath(
				persistedQuery,
				'/analytics/products',
				{
					filter: 'single_product',
					products: productId,
				}
			);
			const { categories } = this.props;

			const productCategories =
				( categoryIds &&
					categories &&
					categoryIds
						.map( ( categoryId ) => categories.get( categoryId ) )
						.filter( Boolean ) ) ||
				[];

			const stockStatus = isLowStock(
				extendedInfoStockStatus,
				stockQuantity,
				lowStockAmount
			) ? (
				<Link
					href={ getAdminLink(
						'post.php?action=edit&post=' + productId
					) }
					type="wp-admin"
				>
					{ _x(
						'Low',
						'Indication of a low quantity',
						'woocommerce-admin'
					) }
				</Link>
			) : (
				stockStatuses[ extendedInfoStockStatus ]
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
					display: formatValue( currency, 'number', itemsSold ),
					value: itemsSold,
				},
				{
					display: renderCurrency( netRevenue ),
					value: getCurrencyFormatDecimal( netRevenue ),
				},
				{
					display: (
						<Link href={ ordersLink } type="wc-admin">
							{ ordersCount }
						</Link>
					),
					value: ordersCount,
				},
				{
					display: (
						<div className="woocommerce-table__product-categories">
							{ productCategories[ 0 ] && (
								<CategoryBreacrumbs
									category={ productCategories[ 0 ] }
									categories={ categories }
								/>
							) }
							{ productCategories.length > 1 && (
								<Tag
									label={ sprintf(
										_x(
											'+%d more',
											'categories',
											'woocommerce-admin'
										),
										productCategories.length - 1
									) }
									popoverContents={ productCategories.map(
										( category ) => (
											<CategoryBreacrumbs
												category={ category }
												categories={ categories }
												key={ category.id }
												query={ query }
											/>
										)
									) }
								/>
							) }
						</div>
					),
					value: productCategories
						.map( ( category ) => category.name )
						.join( ', ' ),
				},
				{
					display: formatValue(
						currency,
						'number',
						variations.length
					),
					value: variations.length,
				},
				manageStock === 'yes'
					? {
							display: extendedInfoManageStock
								? stockStatus
								: __( 'N/A', 'woocommerce-admin' ),
							value: extendedInfoManageStock
								? stockStatuses[ extendedInfoStockStatus ]
								: null,
					  }
					: null,
				manageStock === 'yes'
					? {
							display: extendedInfoManageStock
								? formatValue(
										currency,
										'number',
										stockQuantity
								  )
								: __( 'N/A', 'woocommerce-admin' ),
							value: stockQuantity,
					  }
					: null,
			].filter( Boolean );
		} );
	}

	getSummary( totals ) {
		const {
			products_count: productsCount = 0,
			items_sold: itemsSold = 0,
			net_revenue: netRevenue = 0,
			orders_count: ordersCount = 0,
		} = totals;
		const { formatAmount, getCurrencyConfig } = this.context;
		const currency = getCurrencyConfig();
		return [
			{
				label: _n(
					'Product',
					'Products',
					productsCount,
					'woocommerce-admin'
				),
				value: formatValue( currency, 'number', productsCount ),
			},
			{
				label: _n(
					'Item sold',
					'Items sold',
					itemsSold,
					'woocommerce-admin'
				),
				value: formatValue( currency, 'number', itemsSold ),
			},
			{
				label: __( 'Net sales', 'woocommerce-admin' ),
				value: formatAmount( netRevenue ),
			},
			{
				label: _n(
					'Orders',
					'Orders',
					ordersCount,
					'woocommerce-admin'
				),
				value: formatValue( currency, 'number', ordersCount ),
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
			helpText: __(
				'Check at least two products below to compare',
				'woocommerce-admin'
			),
			placeholder: __(
				'Search by product name or SKU',
				'woocommerce-admin'
			),
		};

		return (
			<ReportTable
				compareBy={ hideCompare ? undefined : 'products' }
				endpoint="products"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				getSummary={ this.getSummary }
				summaryFields={ [
					'products_count',
					'items_sold',
					'net_revenue',
					'orders_count',
				] }
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

ProductsReportTable.contextType = CurrencyContext;

export default compose(
	withSelect( ( select, props ) => {
		const { query, isRequesting } = props;

		const { getItems, getItemsError, isResolving } = select(
			ITEMS_STORE_NAME
		);

		if (
			isRequesting ||
			( query.search && ! ( query.products && query.products.length ) )
		) {
			return {};
		}

		const tableQuery = {
			per_page: -1,
		};

		const categories = getItems( 'categories', tableQuery );
		const isError = Boolean( getItemsError( 'categories', tableQuery ) );
		const isLoading = isResolving( 'getItems', [
			'categories',
			tableQuery,
		] );

		return { categories, isError, isRequesting: isLoading };
	} )
)( ProductsReportTable );
