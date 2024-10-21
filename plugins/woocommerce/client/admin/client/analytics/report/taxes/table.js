/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { map } from 'lodash';
import { Link } from '@woocommerce/components';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import { formatValue } from '@woocommerce/number';
import { CurrencyContext } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import { getTaxCode } from './utils';
import ReportTable from '../../components/report-table';
class TaxesReportTable extends Component {
	constructor() {
		super();

		this.getHeadersContent = this.getHeadersContent.bind( this );
		this.getRowsContent = this.getRowsContent.bind( this );
		this.getSummary = this.getSummary.bind( this );
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Tax code', 'woocommerce' ),
				key: 'tax_code',
				required: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'Rate', 'woocommerce' ),
				key: 'rate',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Total tax', 'woocommerce' ),
				key: 'total_tax',
				isSortable: true,
			},
			{
				label: __( 'Order tax', 'woocommerce' ),
				key: 'order_tax',
				isSortable: true,
			},
			{
				label: __( 'Shipping tax', 'woocommerce' ),
				key: 'shipping_tax',
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
		];
	}

	getRowsContent( taxes ) {
		const {
			render: renderCurrency,
			formatDecimal: getCurrencyFormatDecimal,
			getCurrencyConfig,
		} = this.context;

		return map( taxes, ( tax ) => {
			const { query } = this.props;
			const {
				order_tax: orderTax,
				orders_count: ordersCount,
				tax_rate: taxRate,
				tax_rate_id: taxRateId,
				total_tax: totalTax,
				shipping_tax: shippingTax,
			} = tax;
			const taxCode = getTaxCode( tax );

			const persistedQuery = getPersistedQuery( query );
			const ordersTaxLink = getNewPath(
				persistedQuery,
				'/analytics/orders',
				{
					filter: 'advanced',
					tax_rate_includes: taxRateId,
				}
			);
			const taxLink = (
				<Link href={ ordersTaxLink } type="wc-admin">
					{ taxCode }
				</Link>
			);

			return [
				{
					display: taxLink,
					value: taxCode,
				},
				{
					display: taxRate.toFixed( 2 ) + '%',
					value: taxRate,
				},
				{
					display: renderCurrency( totalTax ),
					value: getCurrencyFormatDecimal( totalTax ),
				},
				{
					display: renderCurrency( orderTax ),
					value: getCurrencyFormatDecimal( orderTax ),
				},
				{
					display: renderCurrency( shippingTax ),
					value: getCurrencyFormatDecimal( shippingTax ),
				},
				{
					display: formatValue(
						getCurrencyConfig(),
						'number',
						ordersCount
					),
					value: ordersCount,
				},
			];
		} );
	}

	getSummary( totals, totalResults = 0 ) {
		const {
			tax_codes: taxesCodes = 0,
			total_tax: totalTax = 0,
			order_tax: orderTax = 0,
			shipping_tax: shippingTax = 0,
			orders_count: ordersCount = 0,
		} = totals;
		const { formatAmount, getCurrencyConfig } = this.context;
		const currency = getCurrencyConfig();
		return [
			{
				label: _n( 'tax', 'taxes', totalResults, 'woocommerce' ),
				value: formatValue( currency, 'number', totalResults ),
			},
			{
				label: _n(
					'distinct code',
					'distinct codes',
					taxesCodes,
					'woocommerce'
				),
				value: formatValue( currency, 'number', taxesCodes ),
			},
			{
				label: __( 'total tax', 'woocommerce' ),
				value: formatAmount( totalTax ),
			},
			{
				label: __( 'order tax', 'woocommerce' ),
				value: formatAmount( orderTax ),
			},
			{
				label: __( 'shipping tax', 'woocommerce' ),
				value: formatAmount( shippingTax ),
			},
			{
				label: _n( 'order', 'orders', ordersCount, 'woocommerce' ),
				value: formatValue( currency, 'number', ordersCount ),
			},
		];
	}

	render() {
		const { advancedFilters, filters, isRequesting, query } = this.props;

		return (
			<ReportTable
				compareBy="taxes"
				endpoint="taxes"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				getSummary={ this.getSummary }
				summaryFields={ [
					'tax_codes',
					'total_tax',
					'order_tax',
					'shipping_tax',
					'orders_count',
				] }
				isRequesting={ isRequesting }
				itemIdField="tax_rate_id"
				query={ query }
				searchBy="taxes"
				tableQuery={ {
					orderby: query.orderby || 'tax_rate_id',
				} }
				title={ __( 'Taxes', 'woocommerce' ) }
				columnPrefsKey="taxes_report_columns"
				filters={ filters }
				advancedFilters={ advancedFilters }
			/>
		);
	}
}

TaxesReportTable.contextType = CurrencyContext;

export default TaxesReportTable;
