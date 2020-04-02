/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { map } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { Date, Link } from '@woocommerce/components';
import { defaultTableDateFormat } from 'lib/date';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import { formatValue } from '@woocommerce/number';
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import ReportTable from 'analytics/components/report-table';
import { CurrencyContext } from 'lib/currency-context';

class CouponsReportTable extends Component {
	constructor() {
		super();

		this.getHeadersContent = this.getHeadersContent.bind( this );
		this.getRowsContent = this.getRowsContent.bind( this );
		this.getSummary = this.getSummary.bind( this );
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Coupon Code', 'woocommerce-admin' ),
				key: 'code',
				required: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'Orders', 'woocommerce-admin' ),
				key: 'orders_count',
				required: true,
				defaultSort: true,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Amount Discounted', 'woocommerce-admin' ),
				key: 'amount',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Created', 'woocommerce-admin' ),
				key: 'created',
			},
			{
				label: __( 'Expires', 'woocommerce-admin' ),
				key: 'expires',
			},
			{
				label: __( 'Type', 'woocommerce-admin' ),
				key: 'type',
			},
		];
	}

	getRowsContent( coupons ) {
		const { query } = this.props;
		const persistedQuery = getPersistedQuery( query );
		const dateFormat = getSetting( 'dateFormat', defaultTableDateFormat );
		const {
			formatCurrency,
			formatDecimal: getCurrencyFormatDecimal,
			getCurrency,
		} = this.context;

		return map( coupons, ( coupon ) => {
			const {
				amount,
				coupon_id: couponId,
				orders_count: ordersCount,
			} = coupon;
			const extendedInfo = coupon.extended_info || {};
			const {
				code,
				date_created: dateCreated,
				date_expires: dateExpires,
				discount_type: discountType,
			} = extendedInfo;

			const couponUrl = getNewPath(
				persistedQuery,
				'/analytics/coupons',
				{
					filter: 'single_coupon',
					coupons: couponId,
				}
			);
			const couponLink = (
				<Link href={ couponUrl } type="wc-admin">
					{ code }
				</Link>
			);

			const ordersUrl = getNewPath( persistedQuery, '/analytics/orders', {
				filter: 'advanced',
				coupon_includes: couponId,
			} );
			const ordersLink = (
				<Link href={ ordersUrl } type="wc-admin">
					{ formatValue( getCurrency(), 'number', ordersCount ) }
				</Link>
			);

			return [
				{
					display: couponLink,
					value: code,
				},
				{
					display: ordersLink,
					value: ordersCount,
				},
				{
					display: formatCurrency( amount ),
					value: getCurrencyFormatDecimal( amount ),
				},
				{
					display: (
						<Date
							date={ dateCreated }
							visibleFormat={ dateFormat }
						/>
					),
					value: dateCreated,
				},
				{
					display: dateExpires ? (
						<Date
							date={ dateExpires }
							visibleFormat={ dateFormat }
						/>
					) : (
						__( 'N/A', 'woocommerce-admin' )
					),
					value: dateExpires,
				},
				{
					display: this.getCouponType( discountType ),
					value: discountType,
				},
			];
		} );
	}

	getSummary( totals ) {
		const {
			coupons_count: couponsCount = 0,
			orders_count: ordersCount = 0,
			amount = 0,
		} = totals;
		const { formatCurrency, getCurrency } = this.context;
		const currency = getCurrency();
		return [
			{
				label: _n(
					'coupon',
					'coupons',
					couponsCount,
					'woocommerce-admin'
				),
				value: formatValue( currency, 'number', couponsCount ),
			},
			{
				label: _n(
					'order',
					'orders',
					ordersCount,
					'woocommerce-admin'
				),
				value: formatValue( currency, 'number', ordersCount ),
			},
			{
				label: __( 'amount discounted', 'woocommerce-admin' ),
				value: formatCurrency( amount ),
			},
		];
	}

	getCouponType( discountType ) {
		const couponTypes = {
			percent: __( 'Percentage', 'woocommerce-admin' ),
			fixed_cart: __( 'Fixed cart', 'woocommerce-admin' ),
			fixed_product: __( 'Fixed product', 'woocommerce-admin' ),
		};
		return couponTypes[ discountType ];
	}

	render() {
		const { advancedFilters, filters, isRequesting, query } = this.props;

		return (
			<ReportTable
				compareBy="coupons"
				endpoint="coupons"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				getSummary={ this.getSummary }
				summaryFields={ [
					'coupons_count',
					'orders_count',
					'amount',
				] }
				isRequesting={ isRequesting }
				itemIdField="coupon_id"
				query={ query }
				searchBy="coupons"
				tableQuery={ {
					orderby: query.orderby || 'orders_count',
					order: query.order || 'desc',
					extended_info: true,
				} }
				title={ __( 'Coupons', 'woocommerce-admin' ) }
				columnPrefsKey="coupons_report_columns"
				filters={ filters }
				advancedFilters={ advancedFilters }
			/>
		);
	}
}

CouponsReportTable.contextType = CurrencyContext;

export default CouponsReportTable;
