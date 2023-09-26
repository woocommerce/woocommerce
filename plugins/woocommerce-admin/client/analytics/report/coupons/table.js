/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { map } from 'lodash';
import { Date, Link } from '@woocommerce/components';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import { formatValue } from '@woocommerce/number';
import { defaultTableDateFormat } from '@woocommerce/date';
import { CurrencyContext } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import ReportTable from '../../components/report-table';
import { getAdminSetting } from '~/utils/admin-settings';

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
				label: __( 'Coupon code', 'woocommerce' ),
				key: 'code',
				required: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'Orders', 'woocommerce' ),
				key: 'orders_count',
				required: true,
				defaultSort: true,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Amount discounted', 'woocommerce' ),
				key: 'amount',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Created', 'woocommerce' ),
				key: 'created',
			},
			{
				label: __( 'Expires', 'woocommerce' ),
				key: 'expires',
			},
			{
				label: __( 'Type', 'woocommerce' ),
				key: 'type',
			},
		];
	}

	getRowsContent( coupons ) {
		const { query } = this.props;
		const persistedQuery = getPersistedQuery( query );
		const dateFormat = getAdminSetting(
			'dateFormat',
			defaultTableDateFormat
		);
		const {
			formatAmount,
			formatDecimal: getCurrencyFormatDecimal,
			getCurrencyConfig,
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

			const couponUrl =
				couponId > 0
					? getNewPath( persistedQuery, '/analytics/coupons', {
							filter: 'single_coupon',
							coupons: couponId,
					  } )
					: null;

			const couponLink =
				couponUrl === null ? (
					code
				) : (
					<Link href={ couponUrl } type="wc-admin">
						{ code }
					</Link>
				);

			const ordersUrl =
				couponId > 0
					? getNewPath( persistedQuery, '/analytics/orders', {
							filter: 'advanced',
							coupon_includes: couponId,
					  } )
					: null;
			const ordersLink =
				ordersUrl === null ? (
					ordersCount
				) : (
					<Link href={ ordersUrl } type="wc-admin">
						{ formatValue(
							getCurrencyConfig(),
							'number',
							ordersCount
						) }
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
					display: formatAmount( amount ),
					value: getCurrencyFormatDecimal( amount ),
				},
				{
					display: dateCreated ? (
						<Date
							date={ dateCreated }
							visibleFormat={ dateFormat }
						/>
					) : (
						__( 'N/A', 'woocommerce' )
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
						__( 'N/A', 'woocommerce' )
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
		const { formatAmount, getCurrencyConfig } = this.context;
		const currency = getCurrencyConfig();
		return [
			{
				label: _n( 'Coupon', 'Coupons', couponsCount, 'woocommerce' ),
				value: formatValue( currency, 'number', couponsCount ),
			},
			{
				label: _n( 'Order', 'Orders', ordersCount, 'woocommerce' ),
				value: formatValue( currency, 'number', ordersCount ),
			},
			{
				label: __( 'Amount discounted', 'woocommerce' ),
				value: formatAmount( amount ),
			},
		];
	}

	getCouponType( discountType ) {
		const couponTypes = {
			percent: __( 'Percentage', 'woocommerce' ),
			fixed_cart: __( 'Fixed cart', 'woocommerce' ),
			fixed_product: __( 'Fixed product', 'woocommerce' ),
		};
		return couponTypes[ discountType ] || __( 'N/A', 'woocommerce' );
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
				summaryFields={ [ 'coupons_count', 'orders_count', 'amount' ] }
				isRequesting={ isRequesting }
				itemIdField="coupon_id"
				query={ query }
				searchBy="coupons"
				tableQuery={ {
					orderby: query.orderby || 'orders_count',
					order: query.order || 'desc',
					extended_info: true,
				} }
				title={ __( 'Coupons', 'woocommerce' ) }
				columnPrefsKey="coupons_report_columns"
				filters={ filters }
				advancedFilters={ advancedFilters }
			/>
		);
	}
}

CouponsReportTable.contextType = CurrencyContext;

export default CouponsReportTable;
