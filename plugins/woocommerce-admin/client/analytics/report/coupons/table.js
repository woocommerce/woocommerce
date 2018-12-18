/** @format */
/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { format as formatDate } from '@wordpress/date';
import { map } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { getIntervalForQuery, getDateFormatsForInterval } from '@woocommerce/date';
import { Link } from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import ReportTable from 'analytics/components/report-table';
import { numberFormat } from 'lib/number';

export default class CouponsReportTable extends Component {
	constructor() {
		super();

		this.getHeadersContent = this.getHeadersContent.bind( this );
		this.getRowsContent = this.getRowsContent.bind( this );
		this.getSummary = this.getSummary.bind( this );
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Coupon Code', 'wc-admin' ),
				// @TODO it should be the coupon code, not the coupon ID
				key: 'coupon_id',
				required: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'Orders', 'wc-admin' ),
				key: 'orders_count',
				required: true,
				defaultSort: true,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'G. Discounted', 'wc-admin' ),
				screenReaderLabel: __( 'Gross Discounted', 'wc-admin' ),
				key: 'gross_discount',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Created', 'wc-admin' ),
				key: 'created',
				isSortable: true,
			},
			{
				label: __( 'Expires', 'wc-admin' ),
				key: 'expires',
				isSortable: true,
			},
			{
				label: __( 'Type', 'wc-admin' ),
				key: 'type',
				isSortable: false,
			},
		];
	}

	getRowsContent( coupons ) {
		const { query } = this.props;
		const currentInterval = getIntervalForQuery( query );
		const { tableFormat } = getDateFormatsForInterval( currentInterval );

		return map( coupons, coupon => {
			const { coupon_id, gross_discount, orders_count } = coupon;

			// @TODO must link to the coupon detail report
			const couponLink = (
				<Link href="" type="wc-admin">
					{ coupon_id }
				</Link>
			);

			const ordersLink = (
				<Link
					href={ '/analytics/orders?filter=advanced&code_includes=' + coupon_id }
					type="wc-admin"
				>
					{ numberFormat( orders_count ) }
				</Link>
			);

			return [
				// @TODO it should be the coupon code, not the coupon ID
				{
					display: couponLink,
					value: coupon_id,
				},
				{
					display: ordersLink,
					value: orders_count,
				},
				{
					display: formatCurrency( gross_discount ),
					value: getCurrencyFormatDecimal( gross_discount ),
				},
				{
					// @TODO
					display: formatDate( tableFormat, '' ),
					value: '',
				},
				{
					// @TODO
					display: formatDate( tableFormat, '' ),
					value: '',
				},
				{
					// @TODO
					display: '',
					value: '',
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
				label: _n( 'coupon', 'coupons', totals.coupons_count, 'wc-admin' ),
				value: numberFormat( totals.coupons_count ),
			},
			{
				label: _n( 'order', 'orders', totals.orders_count, 'wc-admin' ),
				value: numberFormat( totals.orders_count ),
			},
			{
				label: __( 'gross discounted', 'wc-admin' ),
				value: formatCurrency( totals.gross_discount ),
			},
		];
	}

	render() {
		const { query } = this.props;

		return (
			<ReportTable
				compareBy="coupons"
				endpoint="coupons"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				getSummary={ this.getSummary }
				itemIdField="coupon_id"
				query={ query }
				title={ __( 'Coupons', 'wc-admin' ) }
				columnPrefsKey="coupons_report_columns"
			/>
		);
	}
}
