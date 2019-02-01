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
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import { Link } from '@woocommerce/components';
import { numberFormat } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import Leaderboard from 'analytics/components/leaderboard';

export class TopCoupons extends Component {
	constructor( props ) {
		super( props );

		this.getRowsContent = this.getRowsContent.bind( this );
		this.getHeadersContent = this.getHeadersContent.bind( this );
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Coupon Code', 'wc-admin' ),
				key: 'code',
				required: true,
				isLeftAligned: true,
				isSortable: false,
			},
			{
				label: __( 'Orders', 'wc-admin' ),
				key: 'orders_count',
				required: true,
				defaultSort: true,
				isSortable: false,
				isNumeric: true,
			},
			{
				label: __( 'Amount Discounted', 'wc-admin' ),
				key: 'amount',
				isSortable: false,
				isNumeric: true,
			},
		];
	}

	getRowsContent( data ) {
		const { query } = this.props;
		const persistedQuery = getPersistedQuery( query );
		return map( data, row => {
			const { amount, coupon_id, extended_info, orders_count } = row;
			const { code } = extended_info;

			const couponUrl = getNewPath( persistedQuery, 'analytics/coupons', {
				filter: 'single_coupon',
				coupons: coupon_id,
			} );
			const couponLink = (
				<Link href={ couponUrl } type="wc-admin">
					{ code }
				</Link>
			);

			const ordersUrl = getNewPath( persistedQuery, 'analytics/orders', {
				filter: 'advanced',
				coupon_includes: coupon_id,
			} );
			const ordersLink = (
				<Link href={ ordersUrl } type="wc-admin">
					{ numberFormat( orders_count ) }
				</Link>
			);

			return [
				{
					display: couponLink,
					value: code,
				},
				{
					display: ordersLink,
					value: orders_count,
				},
				{
					display: formatCurrency( amount ),
					value: getCurrencyFormatDecimal( amount ),
				},
			];
		} );
	}

	render() {
		const { query, totalRows } = this.props;
		const tableQuery = {
			orderby: 'orders_count',
			order: 'desc',
			per_page: totalRows,
			extended_info: true,
		};

		return (
			<Leaderboard
				endpoint="coupons"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				query={ query }
				tableQuery={ tableQuery }
				title={ __( 'Top Coupons', 'wc-admin' ) }
			/>
		);
	}
}

export default TopCoupons;
