/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { map } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import { Link } from '@woocommerce/components';
import { formatValue } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import CategoryBreacrumbs from './breadcrumbs';
import ReportTable from 'analytics/components/report-table';
import withSelect from 'wc-api/with-select';
import { CurrencyContext } from 'lib/currency-context';

class CategoriesReportTable extends Component {
	constructor( props ) {
		super( props );

		this.getRowsContent = this.getRowsContent.bind( this );
		this.getSummary = this.getSummary.bind( this );
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Category', 'woocommerce-admin' ),
				key: 'category',
				required: true,
				isSortable: true,
				isLeftAligned: true,
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
				key: 'net_revenue',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Products', 'woocommerce-admin' ),
				key: 'products_count',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Orders', 'woocommerce-admin' ),
				key: 'orders_count',
				isSortable: true,
				isNumeric: true,
			},
		];
	}

	getRowsContent( categoryStats ) {
		const {
			render: renderCurrency,
			formatDecimal: getCurrencyFormatDecimal,
			getCurrency,
		} = this.context;
		const currency = getCurrency();

		return map( categoryStats, ( categoryStat ) => {
			const {
				category_id: categoryId,
				items_sold: itemsSold,
				net_revenue: netRevenue,
				products_count: productsCount,
				orders_count: ordersCount,
			} = categoryStat;
			const { categories, query } = this.props;
			const category = categories.get( categoryId );
			const persistedQuery = getPersistedQuery( query );

			return [
				{
					display: (
						<CategoryBreacrumbs
							query={ query }
							category={ category }
							categories={ categories }
						/>
					),
					value: category && category.name,
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
					display: category && (
						<Link
							href={ getNewPath(
								persistedQuery,
								'/analytics/categories',
								{
									filter: 'single_category',
									categories: category.id,
								}
							) }
							type="wc-admin"
						>
							{ formatValue( currency, 'number', productsCount ) }
						</Link>
					),
					value: productsCount,
				},
				{
					display: formatValue( currency, 'number', ordersCount ),
					value: ordersCount,
				},
			];
		} );
	}

	getSummary( totals, totalResults = 0 ) {
		const {
			items_sold: itemsSold = 0,
			net_revenue: netRevenue = 0,
			orders_count: ordersCount = 0,
		} = totals;
		const { formatCurrency, getCurrency } = this.context;
		const currency = getCurrency();
		return [
			{
				label: _n(
					'category',
					'categories',
					totalResults,
					'woocommerce-admin'
				),
				value: formatValue( currency, 'number', totalResults ),
			},
			{
				label: _n(
					'item sold',
					'items sold',
					itemsSold,
					'woocommerce-admin'
				),
				value: formatValue( currency, 'number', itemsSold ),
			},
			{
				label: __( 'net sales', 'woocommerce-admin' ),
				value: formatCurrency( netRevenue ),
			},
			{
				label: _n(
					'order',
					'orders',
					ordersCount,
					'woocommerce-admin'
				),
				value: formatValue( currency, 'number', ordersCount ),
			},
		];
	}

	render() {
		const { advancedFilters, filters, isRequesting, query } = this.props;

		const labels = {
			helpText: __(
				'Check at least two categories below to compare',
				'woocommerce-admin'
			),
			placeholder: __( 'Search by category name', 'woocommerce-admin' ),
		};

		return (
			<ReportTable
				compareBy="categories"
				endpoint="categories"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				getSummary={ this.getSummary }
				summaryFields={ [
					'items_sold',
					'net_revenue',
					'orders_count',
				] }
				isRequesting={ isRequesting }
				itemIdField="category_id"
				query={ query }
				searchBy="categories"
				labels={ labels }
				tableQuery={ {
					orderby: query.orderby || 'items_sold',
					order: query.order || 'desc',
					extended_info: true,
				} }
				title={ __( 'Categories', 'woocommerce-admin' ) }
				columnPrefsKey="categories_report_columns"
				filters={ filters }
				advancedFilters={ advancedFilters }
			/>
		);
	}
}

CategoriesReportTable.contextType = CurrencyContext;

export default compose(
	withSelect( ( select, props ) => {
		const { isRequesting, query } = props;
		if (
			isRequesting ||
			( query.search &&
				! ( query.categories && query.categories.length ) )
		) {
			return {};
		}

		const { getItems, getItemsError, isGetItemsRequesting } = select(
			'wc-api'
		);
		const tableQuery = {
			per_page: -1,
		};

		const categories = getItems( 'categories', tableQuery );
		const isCategoriesError = Boolean(
			getItemsError( 'categories', tableQuery )
		);
		const isCategoriesRequesting = isGetItemsRequesting(
			'categories',
			tableQuery
		);

		return {
			categories,
			isError: isCategoriesError,
			isRequesting: isCategoriesRequesting,
		};
	} )
)( CategoriesReportTable );
