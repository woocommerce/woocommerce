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
 * Internal dependencies
 */
import { Card, Table, TablePlaceholder } from '@woocommerce/components';
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

	getCardContents( title ) {
		const { data, isRequesting, isError } = this.props;

		const rows = isRequesting || isError ? [] : this.getRowsContent( data );
		const headers = this.getHeadersContent();

		if ( isRequesting ) {
			return <TablePlaceholder caption={ title } headers={ headers } />;
		}

		if ( isError ) {
			// @TODO An error notice should be displayed when there is an error
		}

		if ( rows.length === 0 ) {
			return (
				<div className="woocommerce-top-selling-products__empty-message">
					{ __( 'When new orders arrive, popular products will be listed here.', 'wc-admin' ) }
				</div>
			);
		}

		return <Table caption={ title } rows={ rows } headers={ headers } />;
	}

	render() {
		const title = __( 'Top Selling Products', 'wc-admin' );

		return (
			<Card title={ title } className="woocommerce-top-selling-products">
				{ this.getCardContents( title ) }
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
		const query = { orderby: 'items_sold', per_page: 5, extended_product_info: 1 };

		const stats = getReportStats( endpoint, query );
		const isRequesting = isReportStatsRequesting( endpoint, query );
		const isError = isReportStatsError( endpoint, query );

		return { data: get( stats, 'data', [] ), isRequesting, isError };
	} )
)( TopSellingProducts );
