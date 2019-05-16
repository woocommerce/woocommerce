/** @format */
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
import { formatCurrency, getCurrencyFormatDecimal, renderCurrency } from '@woocommerce/currency';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import { Link } from '@woocommerce/components';
import { numberFormat } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import CategoryBreacrumbs from './breadcrumbs';
import ReportTable from 'analytics/components/report-table';
import withSelect from 'wc-api/with-select';

class CategoriesReportTable extends Component {
	constructor( props ) {
		super( props );

		this.getRowsContent = this.getRowsContent.bind( this );
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
				label: __( 'Items sold', 'woocommerce-admin' ),
				key: 'items_sold',
				required: true,
				defaultSort: true,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Net Revenue', 'woocommerce-admin' ),
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
		return map( categoryStats, categoryStat => {
			const { category_id, items_sold, net_revenue, products_count, orders_count } = categoryStat;
			const { categories, query } = this.props;
			const category = categories.get( category_id );
			const persistedQuery = getPersistedQuery( query );

			return [
				{
					display: (
						<CategoryBreacrumbs query={ query } category={ category } categories={ categories } />
					),
					value: category && category.name,
				},
				{
					display: numberFormat( items_sold ),
					value: items_sold,
				},
				{
					display: renderCurrency( net_revenue ),
					value: getCurrencyFormatDecimal( net_revenue ),
				},
				{
					display: category && (
						<Link
							href={ getNewPath( persistedQuery, '/analytics/categories', {
								filter: 'single_category',
								categories: category.id,
							} ) }
							type="wc-admin"
						>
							{ numberFormat( products_count ) }
						</Link>
					),
					value: products_count,
				},
				{
					display: numberFormat( orders_count ),
					value: orders_count,
				},
			];
		} );
	}

	getSummary( totals, totalResults = 0 ) {
		const { items_sold = 0, net_revenue = 0, orders_count = 0 } = totals;
		return [
			{
				label: _n( 'category', 'categories', totalResults, 'woocommerce-admin' ),
				value: numberFormat( totalResults ),
			},
			{
				label: _n( 'item sold', 'items sold', items_sold, 'woocommerce-admin' ),
				value: numberFormat( items_sold ),
			},
			{
				label: __( 'net revenue', 'woocommerce-admin' ),
				value: formatCurrency( net_revenue ),
			},
			{
				label: _n( 'order', 'orders', orders_count, 'woocommerce-admin' ),
				value: numberFormat( orders_count ),
			},
		];
	}

	render() {
		const { isRequesting, query, filters } = this.props;

		const labels = {
			helpText: __( 'Check at least two categories below to compare', 'woocommerce-admin' ),
			placeholder: __( 'Search by category name', 'woocommerce-admin' ),
		};

		return (
			<ReportTable
				compareBy="categories"
				endpoint="categories"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				getSummary={ this.getSummary }
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
			/>
		);
	}
}

export default compose(
	withSelect( ( select, props ) => {
		const { isRequesting, query } = props;
		if ( isRequesting || ( query.search && ! ( query.categories && query.categories.length ) ) ) {
			return {};
		}

		const { getItems, getItemsError, isGetItemsRequesting } = select( 'wc-api' );
		const tableQuery = {
			per_page: -1,
		};

		const categories = getItems( 'categories', tableQuery );
		const isCategoriesError = Boolean( getItemsError( 'categories', tableQuery ) );
		const isCategoriesRequesting = isGetItemsRequesting( 'categories', tableQuery );

		return { categories, isError: isCategoriesError, isRequesting: isCategoriesRequesting };
	} )
)( CategoriesReportTable );
