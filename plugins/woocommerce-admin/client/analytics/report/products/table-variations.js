/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { map } from 'lodash';

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

export default class VariationsReportTable extends Component {
	constructor() {
		super();

		this.getHeadersContent = this.getHeadersContent.bind( this );
		this.getRowsContent = this.getRowsContent.bind( this );
	}

	getVariationName( variation ) {
		return variation.attributes.reduce( ( desc, attribute, index, arr ) => {
			desc += `${ attribute.option }${ arr.length === index + 1 ? '' : ', ' }`;
			return desc;
		}, variation.product_name + ' / ' );
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
			const {
				items_sold,
				gross_revenue,
				orders_count,
				stock_status = 'outofstock',
				stock_quantity = '0',
				product_id,
			} = row;
			const name = this.getVariationName( row );
			const ordersLink = getNewPath( persistedQuery, 'orders', {
				filter: 'advanced',
				product_includes: query.products,
			} );

			return [
				{
					display: name,
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
				tableQuery={ {
					orderby: query.orderby || 'items_sold',
					order: query.order || 'desc',
				} }
				title={ __( 'Variations', 'wc-admin' ) }
			/>
		);
	}
}
