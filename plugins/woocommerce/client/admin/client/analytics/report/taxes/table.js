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
export class TaxesReportTable extends Component {
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
				label: __( 'Taxable amount', 'woocommerce' ),
				key: 'taxable_amount',
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
				taxable_amount: taxableAmount,
			} = tax;
			const incomplete = Number( tax.reporting_missing_orders ) > 0;
			// A zero base under a non-zero tax marks a lookup row recorded before the
			// taxable amount existed (or a manual tax line) - unknown, not zero.
			const hasTaxableAmount =
				taxableAmount !== undefined &&
				! ( taxableAmount === 0 && totalTax !== 0 );
			// Taxable amount is money too: unavailable when the row's currency cannot be
			// qualified, and unknown (N/A) when no base was recorded for it.
			let taxableCell = {
				display: __( 'N/A', 'woocommerce' ),
				value: '',
			};
			if ( incomplete ) {
				taxableCell = {
					display: __( 'Unavailable', 'woocommerce' ),
					value: __( 'Unavailable', 'woocommerce' ),
				};
			} else if ( hasTaxableAmount ) {
				taxableCell = {
					display: renderCurrency( taxableAmount ),
					value: getCurrencyFormatDecimal( taxableAmount ),
				};
			}
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
					display: incomplete
						? __( 'Unavailable', 'woocommerce' )
						: renderCurrency( totalTax ),
					value: incomplete
						? __( 'Unavailable', 'woocommerce' )
						: getCurrencyFormatDecimal( totalTax ),
				},
				{
					display: incomplete
						? __( 'Unavailable', 'woocommerce' )
						: renderCurrency( orderTax ),
					value: incomplete
						? __( 'Unavailable', 'woocommerce' )
						: getCurrencyFormatDecimal( orderTax ),
				},
				{
					display: incomplete
						? __( 'Unavailable', 'woocommerce' )
						: renderCurrency( shippingTax ),
					value: incomplete
						? __( 'Unavailable', 'woocommerce' )
						: getCurrencyFormatDecimal( shippingTax ),
				},
				taxableCell,
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
				value:
					Number( totals.reporting_missing_orders ) > 0
						? __( 'Unavailable', 'woocommerce' )
						: formatAmount( totalTax ),
			},
			{
				label: __( 'order tax', 'woocommerce' ),
				value:
					Number( totals.reporting_missing_orders ) > 0
						? __( 'Unavailable', 'woocommerce' )
						: formatAmount( orderTax ),
			},
			{
				label: __( 'shipping tax', 'woocommerce' ),
				value:
					Number( totals.reporting_missing_orders ) > 0
						? __( 'Unavailable', 'woocommerce' )
						: formatAmount( shippingTax ),
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
