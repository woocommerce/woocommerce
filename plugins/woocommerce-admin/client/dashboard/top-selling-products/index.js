/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { map } from 'lodash';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { Card, Table } from '@woocommerce/components';
import { getAdminLink } from 'lib/nav-utils';
import { numberFormat } from 'lib/number';
import { formatCurrency, getCurrencyFormatDecimal } from 'lib/currency';
import { NAMESPACE } from 'store/constants';
import './style.scss';

export class TopSellingProducts extends Component {
	getHeadersContent() {
		return [
			{
				label: __( 'Product', 'wc-admin' ),
				key: 'product',
				required: true,
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
			const { product_id, items_sold, gross_revenue, orders_count } = row;

			// @TODO We also will need to have product data to properly display the product name here
			const productName = `Product ${ product_id }`;
			const productLink = (
				<a href={ getAdminLink( `/post.php?post=${ product_id }&action=edit` ) }>{ productName }</a>
			);
			return [
				{
					display: productLink,
					value: productName,
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

		// @TODO We will need to update it with a loading/empty data indicator
		const rows = isRequesting || isError ? [] : this.getRowsContent( data );
		const headers = this.getHeadersContent();
		const title = __( 'Top Selling Products', 'wc-admin' );

		return (
			<Card title={ title } className="woocommerce-top-selling-products">
				<Table caption={ title } rows={ rows } headers={ headers } />
			</Card>
		);
	}
}

export default compose(
	withSelect( select => {
		const { getReportStats, isReportStatsRequesting, isReportStatsError } = select( 'wc-admin' );
		const endpoint = NAMESPACE + 'reports/products';
		// @TODO We will need to add the date parameters from the Date Picker
		// { after: '2018-04-22', before: '2018-05-06' }
		const query = { orderby: 'items_sold' };

		const data = getReportStats( endpoint, query );
		const isRequesting = isReportStatsRequesting( endpoint, query );
		const isError = isReportStatsError( endpoint, query );

		return { data, isRequesting, isError };
	} )
)( TopSellingProducts );
