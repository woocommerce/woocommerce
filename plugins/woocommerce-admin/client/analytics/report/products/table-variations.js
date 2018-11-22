/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { map, orderBy } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { Link, TableCard } from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { getNewPath, getPersistedQuery, onQueryChange } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ReportError from 'analytics/components/report-error';
import { getReportChartData, getReportTableData } from 'store/reports/utils';

class VariationsReportTable extends Component {
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
		const { tableData } = this.props;
		const { isError, isRequesting, items, query } = tableData;

		if ( isError ) {
			return <ReportError isError />;
		}

		const headers = this.getHeadersContent();
		const orderedVariations = orderBy( items, query.orderby, query.order );
		const rows = this.getRowsContent( orderedVariations );

		const labels = {
			helpText: __( 'Select at least two variations to compare', 'wc-admin' ),
			placeholder: __( 'Search by variation name', 'wc-admin' ),
		};

		return (
			<TableCard
				title={ __( 'Variations', 'wc-admin' ) }
				rows={ rows }
				totalRows={ items.length }
				rowsPerPage={ query.per_page }
				headers={ headers }
				labels={ labels }
				ids={ orderedVariations.map( p => p.product_id ) }
				isLoading={ isRequesting }
				compareBy={ 'variations' }
				compareParam={ 'filter-variations' }
				onQueryChange={ onQueryChange }
				query={ query }
				summary={ null } // @TODO
				downloadable
			/>
		);
	}
}

export default compose(
	withSelect( ( select, props ) => {
		const { query } = props;
		const primaryData = getReportChartData( 'products', 'primary', query, select );
		const tableData = getReportTableData( 'variations', query, select, {
			orderby: query.orderby || 'items_sold',
			order: query.order || 'desc',
		} );

		return {
			primaryData,
			tableData,
		};
	} )
)( VariationsReportTable );
