/** @format */
/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { map } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { Link } from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import ReportTable from 'analytics/components/report-table';
import { numberFormat } from 'lib/number';

export default class CategoriesReportTable extends Component {
	getHeadersContent() {
		return [
			{
				label: __( 'Category', 'wc-admin' ),
				key: 'category',
				required: true,
				isLeftAligned: true,
				isSortable: true,
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
				label: __( 'G. Revenue', 'wc-admin' ),
				screenReaderLabel: __( 'Gross Revenue', 'wc-admin' ),
				key: 'gross_revenue',
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

	getRowsContent( categories ) {
		return map( categories, category => {
			const { category_id, items_sold, gross_revenue, products_count, orders_count } = category;

			// @TODO it should link to the Products report filtered by category
			const productsLink = (
				<Link
					href={ '/analytics/orders?filter=advanced&code_includes=' + category_id }
					type="wc-admin"
				>
					{ numberFormat( products_count ) }
				</Link>
			);

			return [
				// @TODO it should be the category name, not the category ID
				{
					display: category_id,
					value: category_id,
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
					display: productsLink,
					value: products_count,
				},
				{
					display: numberFormat( orders_count ),
					value: orders_count,
				},
			];
		} );
	}

	getSummary( totals, totalCount ) {
		if ( ! totals ) {
			return [];
		}

		return [
			{
				label: _n( 'category', 'categories', totalCount, 'wc-admin' ),
				value: numberFormat( totalCount ),
			},
			{
				label: _n( 'item sold', 'items sold', totals.items_sold, 'wc-admin' ),
				value: numberFormat( totals.items_sold ),
			},
			{
				label: __( 'gross revenue', 'wc-admin' ),
				value: formatCurrency( totals.gross_revenue ),
			},
			{
				label: _n( 'orders', 'orders', totals.orders_count, 'wc-admin' ),
				value: numberFormat( totals.orders_count ),
			},
		];
	}

	render() {
		const { query } = this.props;

		return (
			<ReportTable
				compareBy="product_cats"
				endpoint="categories"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				getSummary={ this.getSummary }
				itemIdField="category_id"
				query={ query }
				title={ __( 'Categories', 'wc-admin' ) }
				columnPrefsKey="categories_report_columns"
			/>
		);
	}
}
