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
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
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
				label: __( 'Category', 'wc-admin' ),
				key: 'category',
				required: true,
				isSortable: true,
				isLeftAligned: true,
			},
			{
				label: __( 'Items sold', 'wc-admin' ),
				key: 'items_sold',
				required: true,
				defaultSort: true,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Net Revenue', 'wc-admin' ),
				key: 'net_revenue',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Products', 'wc-admin' ),
				key: 'products_count',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Orders', 'wc-admin' ),
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
			const category = categories[ category_id ];
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
					display: formatCurrency( net_revenue ),
					value: getCurrencyFormatDecimal( net_revenue ),
				},
				{
					display: category && (
						<Link
							href={ getNewPath( persistedQuery, 'categories', {
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

	getSummary( totals, totalResults ) {
		if ( ! totals ) {
			return [];
		}

		return [
			{
				label: _n( 'category', 'categories', totalResults, 'wc-admin' ),
				value: numberFormat( totalResults ),
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
			helpText: __( 'Select at least two categories to compare', 'wc-admin' ),
			placeholder: __( 'Search by category name', 'wc-admin' ),
		};

		return (
			<ReportTable
				compareBy="categories"
				endpoint="categories"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				getSummary={ this.getSummary }
				itemIdField="category_id"
				query={ query }
				labels={ labels }
				tableQuery={ {
					orderby: query.orderby || 'items_sold',
					order: query.order || 'desc',
					extended_info: true,
				} }
				title={ __( 'Categories', 'wc-admin' ) }
				columnPrefsKey="categories_report_columns"
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
)( CategoriesReportTable );
