/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { map } from 'lodash';
import { Link } from '@woocommerce/components';
import { formatValue } from '@woocommerce/number';
import { CurrencyContext } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import ReportTable from '../../components/report-table';

class StockNotificationsReportTable extends Component {
	constructor() {
		super();

		this.getHeadersContent = this.getHeadersContent.bind( this );
		this.getRowsContent = this.getRowsContent.bind( this );
		this.getSummary = this.getSummary.bind( this );
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Product', 'woocommerce' ),
				key: 'product_id',
				required: true,
				isLeftAligned: true,
				isSortable: false,
			},
			{
				label: __( 'Waiting', 'woocommerce' ),
				key: 'active_signups',
				required: true,
				defaultSort: true,
				defaultOrder: 'desc',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Sign-ups', 'woocommerce' ),
				key: 'signups',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Customers', 'woocommerce' ),
				key: 'customers',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Days waiting', 'woocommerce' ),
				key: 'days_waiting',
				isSortable: true,
				isNumeric: true,
			},
		];
	}

	getRowsContent( rows ) {
		const currency = this.context.getCurrencyConfig();
		const formatNumber = ( value ) =>
			formatValue( currency, 'number', value );

		return map( rows, ( row ) => {
			const {
				active_signups: activeSignups,
				customers,
				days_waiting: daysWaiting,
				signups,
			} = row;
			const { name, edit_url: editUrl } = row.extended_info || {};

			return [
				{
					display: editUrl ? (
						<Link href={ editUrl } type="wp-admin">
							{ name }
						</Link>
					) : (
						name
					),
					value: name,
				},
				{
					display: formatNumber( activeSignups ),
					value: activeSignups,
				},
				{
					display: formatNumber( signups ),
					value: signups,
				},
				{
					display: formatNumber( customers ),
					value: customers,
				},
				{
					display: formatNumber( daysWaiting ),
					value: daysWaiting,
				},
			];
		} );
	}

	getSummary( totals ) {
		const {
			signups = 0,
			customers = 0,
			notifications_sent: notificationsSent = 0,
			active_signups: activeSignups = 0,
		} = totals;
		const currency = this.context.getCurrencyConfig();
		return [
			{
				label: _n( 'Sign-up', 'Sign-ups', signups, 'woocommerce' ),
				value: formatValue( currency, 'number', signups ),
			},
			{
				label: _n( 'Customer', 'Customers', customers, 'woocommerce' ),
				value: formatValue( currency, 'number', customers ),
			},
			{
				label: _n(
					'Notification sent',
					'Notifications sent',
					notificationsSent,
					'woocommerce'
				),
				value: formatValue( currency, 'number', notificationsSent ),
			},
			{
				label: __( 'Waiting', 'woocommerce' ),
				value: formatValue( currency, 'number', activeSignups ),
			},
		];
	}

	render() {
		const { advancedFilters, filters, isRequesting, query } = this.props;

		return (
			<ReportTable
				endpoint="stock-notifications"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				getSummary={ this.getSummary }
				summaryFields={ [
					'signups',
					'customers',
					'notifications_sent',
					'active_signups',
				] }
				isRequesting={ isRequesting }
				itemIdField="product_id"
				query={ query }
				tableQuery={ {
					orderby: query.orderby || 'active_signups',
					order: query.order || 'desc',
					extended_info: true,
				} }
				title={ __( 'Stock notifications', 'woocommerce' ) }
				columnPrefsKey="stock_notifications_report_columns"
				filters={ filters }
				advancedFilters={ advancedFilters }
			/>
		);
	}
}

StockNotificationsReportTable.contextType = CurrencyContext;

export default StockNotificationsReportTable;
