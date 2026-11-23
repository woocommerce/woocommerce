/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
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
				label: __( 'Sign-ups', 'woocommerce' ),
				key: 'signups',
				required: true,
				defaultSort: true,
				defaultOrder: 'desc',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Notifications sent', 'woocommerce' ),
				key: 'notifications_sent',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Customers', 'woocommerce' ),
				key: 'customers',
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
				signups,
				notifications_sent: notificationsSent,
				customers,
			} = row;
			const { edit_url: editUrl } = row.extended_info || {};
			const name = decodeEntities( row.extended_info?.name || '' );

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
					display: formatNumber( signups ),
					value: signups,
				},
				{
					display: formatNumber( notificationsSent ),
					value: notificationsSent,
				},
				{
					display: formatNumber( customers ),
					value: customers,
				},
			];
		} );
	}

	getSummary( totals ) {
		const {
			products_count: productsCount = 0,
			signups = 0,
			notifications_sent: notificationsSent = 0,
			customers = 0,
		} = totals;
		const currency = this.context.getCurrencyConfig();
		return [
			{
				label: _n(
					'Product',
					'Products',
					productsCount,
					'woocommerce'
				),
				value: formatValue( currency, 'number', productsCount ),
			},
			{
				label: _n( 'Sign-up', 'Sign-ups', signups, 'woocommerce' ),
				value: formatValue( currency, 'number', signups ),
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
				label: _n( 'Customer', 'Customers', customers, 'woocommerce' ),
				value: formatValue( currency, 'number', customers ),
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
					'products_count',
					'signups',
					'notifications_sent',
					'customers',
				] }
				isRequesting={ isRequesting }
				itemIdField="product_id"
				query={ query }
				tableQuery={ {
					orderby: query.orderby || 'signups',
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
