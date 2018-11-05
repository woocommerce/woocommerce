/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { get, map } from 'lodash';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { Card, EmptyTable, TableCard } from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { getAdminLink } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { numberFormat } from 'lib/number';
import ReportError from 'analytics/components/report-error';
import { NAMESPACE } from 'store/constants';
import './style.scss';

export class TopSellingProducts extends Component {
	getHeadersContent() {
		return [
			{
				label: __( 'Product', 'wc-admin' ),
				key: 'product',
				required: true,
				isLeftAligned: true,
				isSortable: false,
			},
			{
				label: __( 'Items Sold', 'wc-admin' ),
				key: 'items_sold',
				required: false,
				isSortable: false,
				isNumeric: true,
			},
			{
				label: __( 'Orders', 'wc-admin' ),
				key: 'orders_count',
				required: false,
				isSortable: false,
				isNumeric: true,
			},
			{
				label: __( 'Gross Revenue', 'wc-admin' ),
				key: 'gross_revenue',
				required: true,
				isSortable: false,
				isNumeric: true,
			},
		];
	}

	getRowsContent( data ) {
		return map( data, row => {
			const { product_id, items_sold, gross_revenue, orders_count, name } = row;

			const productLink = (
				<a href={ getAdminLink( `/post.php?post=${ product_id }&action=edit` ) }>{ name }</a>
			);
			return [
				{
					display: productLink,
					value: name,
				},
				{
					display: numberFormat( items_sold ),
					value: items_sold,
				},
				{
					display: numberFormat( orders_count ),
					value: orders_count,
				},
				{
					display: formatCurrency( gross_revenue ),
					value: getCurrencyFormatDecimal( gross_revenue ),
				},
			];
		} );
	}

	render() {
		const { data, isRequesting, isError } = this.props;

		const rows = isRequesting || isError ? [] : this.getRowsContent( data );
		const title = __( 'Top Selling Products', 'wc-admin' );

		if ( isError ) {
			return <ReportError className="woocommerce-top-selling-products" isError />;
		}

		if ( ! isRequesting && rows.length === 0 ) {
			return (
				<Card title={ title } className="woocommerce-top-selling-products">
					<EmptyTable>
						{ __( 'When new orders arrive, popular products will be listed here.', 'wc-admin' ) }
					</EmptyTable>
				</Card>
			);
		}

		const headers = this.getHeadersContent();

		return (
			<TableCard
				className="woocommerce-top-selling-products"
				headers={ headers }
				isLoading={ isRequesting }
				rows={ rows }
				rowsPerPage={ 5 }
				title={ title }
				totalRows={ 5 }
			/>
		);
	}
}

export default compose(
	withSelect( select => {
		const { getReportStats, isReportStatsRequesting, isReportStatsError } = select( 'wc-admin' );
		const endpoint = NAMESPACE + 'reports/products';
		// @TODO We will need to add the date parameters from the Date Picker
		// { after: '2018-04-22', before: '2018-05-06' }
		const query = { orderby: 'items_sold', per_page: 5, extended_product_info: 1 };

		const stats = getReportStats( endpoint, query );
		const isRequesting = isReportStatsRequesting( endpoint, query );
		const isError = isReportStatsError( endpoint, query );

		return { data: get( stats, 'data', [] ), isRequesting, isError };
	} )
)( TopSellingProducts );
