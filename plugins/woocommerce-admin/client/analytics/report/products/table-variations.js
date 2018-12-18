/** @format */
/**
 * External dependencies
 */
import { __, _n, _x } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { map, get } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { Link } from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ReportTable from 'analytics/components/report-table';
import { numberFormat } from 'lib/number';
import { isLowStock } from './utils';

export default class VariationsReportTable extends Component {
	constructor() {
		super();

		this.getHeadersContent = this.getHeadersContent.bind( this );
		this.getRowsContent = this.getRowsContent.bind( this );
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Product / Variation Title', 'wc-admin' ),
				key: 'name',
				required: true,
				isLeftAligned: true,
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
				screenReaderLabel: __( 'Gross Revenue', 'wc-admin' ),
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
			const { items_sold, gross_revenue, orders_count, extended_info, product_id } = row;
			const { stock_status, stock_quantity, low_stock_amount } = extended_info;
			const name = get( row, [ 'extended_info', 'name' ], '' ).replace( ' - ', ' / ' );
			const ordersLink = getNewPath( persistedQuery, 'orders', {
				filter: 'advanced',
				product_includes: query.products,
			} );
			const editPostLink = `post.php?post=${ product_id }&action=edit`;

			return [
				{
					display: (
						<Link href={ editPostLink } type="wp-admin">
							{ name }
						</Link>
					),
					value: name,
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
					display: isLowStock( stock_status, stock_quantity, low_stock_amount ) ? (
						<Link href={ editPostLink } type="wp-admin">
							{ _x( 'Low', 'Indication of a low quantity', 'wc-admin' ) }
						</Link>
					) : (
						stockStatuses[ stock_status ]
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

	getSummary( totals ) {
		if ( ! totals ) {
			return [];
		}
		return [
			{
				// @TODO: When primaryData is segmented, fix this to reflect variations, not products.
				label: _n( 'variation sold', 'variations sold', totals.products_count, 'wc-admin' ),
				value: numberFormat( totals.products_count ),
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

		const labels = {
			helpText: __( 'Select at least two variations to compare', 'wc-admin' ),
			placeholder: __( 'Search by variation name', 'wc-admin' ),
		};

		return (
			<ReportTable
				compareBy={ 'variations' }
				compareParam={ 'filter-variations' }
				endpoint="variations"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				itemIdField="product_id"
				labels={ labels }
				query={ query }
				getSummary={ this.getSummary }
				tableQuery={ {
					orderby: query.orderby || 'items_sold',
					order: query.order || 'desc',
					extended_info: true,
				} }
				title={ __( 'Variations', 'wc-admin' ) }
				columnPrefsKey="variations_report_columns"
			/>
		);
	}
}
