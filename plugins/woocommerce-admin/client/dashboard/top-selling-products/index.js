/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { map } from 'lodash';

/**
 * Internal dependencies
 */
import Card from 'components/card';
import { getAdminLink } from 'lib/nav-utils';
import { numberFormat } from 'lib/number';
import { formatCurrency, getCurrencyFormatDecimal } from 'lib/currency';
import { Table } from 'components/table';
import './style.scss';

// Mock data until we fetch from an API
import mockData from './mock-data';

class TopSellingProducts extends Component {
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
			},
			{
				label: __( 'Orders', 'wc-admin' ),
				key: 'orders_count',
				required: false,
				isSortable: false,
			},
			{
				label: __( 'Gross Revenue', 'wc-admin' ),
				key: 'gross_revenue',
				required: true,
				isSortable: false,
			},
		];
	}

	getRowsContent( data = [] ) {
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

	onQueryChange() {}

	render() {
		const rows = this.getRowsContent( mockData ) || [];
		const headers = this.getHeadersContent();
		const title = __( 'Top Selling Products', 'wc-admin' );

		return (
			<Card title={ title } className="woocommerce-top-selling-products">
				<Table caption={ title } rows={ rows } headers={ headers } />
			</Card>
		);
	}
}

export default TopSellingProducts;
