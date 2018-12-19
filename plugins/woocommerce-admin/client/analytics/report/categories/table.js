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

	getRowsContent( categories ) {
		return map( categories, category => {
			const {
				category_id,
				items_sold,
				net_revenue,
				products_count,
				orders_count,
				extended_info,
			} = category;
			const { name } = extended_info;

			return [
				{
					display: (
						<Link
							href={ 'term.php?taxonomy=product_cat&post_type=product&tag_ID=' + category_id }
							type="wp-admin"
						>
							{ name }
						</Link>
					),
					value: name,
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
					display: numberFormat( products_count ),
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
				label: __( 'gross revenue', 'wc-admin' ),
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

		return (
			<ReportTable
				compareBy="product_cats"
				endpoint="categories"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				getSummary={ this.getSummary }
				itemIdField="category_id"
				query={ query }
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
