/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { Tooltip } from '@wordpress/components';
import { Date, Link } from '@woocommerce/components';
import { formatValue } from '@woocommerce/number';
import { getAdminLink, getSetting } from '@woocommerce/wc-admin-settings';
import { defaultTableDateFormat } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import ReportTable from '../../components/report-table';
import { CurrencyContext } from '../../../lib/currency-context';

const { countries } = getSetting( 'dataEndpoints', { countries: {} } );

class CustomersReportTable extends Component {
	constructor() {
		super();

		this.getHeadersContent = this.getHeadersContent.bind( this );
		this.getRowsContent = this.getRowsContent.bind( this );
		this.getSummary = this.getSummary.bind( this );
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Name', 'woocommerce-admin' ),
				key: 'name',
				required: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'Username', 'woocommerce-admin' ),
				key: 'username',
				hiddenByDefault: true,
			},
			{
				label: __( 'Last active', 'woocommerce-admin' ),
				key: 'date_last_active',
				defaultSort: true,
				isSortable: true,
			},
			{
				label: __( 'Date registered', 'woocommerce-admin' ),
				key: 'date_registered',
				isSortable: true,
			},
			{
				label: __( 'Email', 'woocommerce-admin' ),
				key: 'email',
			},
			{
				label: __( 'Orders', 'woocommerce-admin' ),
				key: 'orders_count',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Total spend', 'woocommerce-admin' ),
				key: 'total_spend',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'AOV', 'woocommerce-admin' ),
				screenReaderLabel: __(
					'Average order value',
					'woocommerce-admin'
				),
				key: 'avg_order_value',
				isNumeric: true,
			},
			{
				label: __( 'Country / Region', 'woocommerce-admin' ),
				key: 'country',
				isSortable: true,
			},
			{
				label: __( 'City', 'woocommerce-admin' ),
				key: 'city',
				hiddenByDefault: true,
				isSortable: true,
			},
			{
				label: __( 'Region', 'woocommerce-admin' ),
				key: 'state',
				hiddenByDefault: true,
				isSortable: true,
			},
			{
				label: __( 'Postal code', 'woocommerce-admin' ),
				key: 'postcode',
				hiddenByDefault: true,
				isSortable: true,
			},
		];
	}

	getCountryName( code ) {
		return typeof countries[ code ] !== 'undefined'
			? countries[ code ]
			: null;
	}

	getRowsContent( customers ) {
		const dateFormat = getSetting( 'dateFormat', defaultTableDateFormat );
		const {
			formatAmount,
			formatDecimal: getCurrencyFormatDecimal,
			getCurrencyConfig,
		} = this.context;

		return customers?.map( ( customer ) => {
			const {
				avg_order_value: avgOrderValue,
				date_last_active: dateLastActive,
				date_registered: dateRegistered,
				email,
				name,
				user_id: userId,
				orders_count: ordersCount,
				username,
				total_spend: totalSpend,
				postcode,
				city,
				state,
				country,
			} = customer;
			const countryName = this.getCountryName( country );

			const customerNameLink = userId ? (
				<Link
					href={ getAdminLink( 'user-edit.php?user_id=' + userId ) }
					type="wp-admin"
				>
					{ name }
				</Link>
			) : (
				name
			);

			const dateLastActiveDisplay = dateLastActive ? (
				<Date date={ dateLastActive } visibleFormat={ dateFormat } />
			) : (
				'—'
			);

			const dateRegisteredDisplay = dateRegistered ? (
				<Date date={ dateRegistered } visibleFormat={ dateFormat } />
			) : (
				'—'
			);

			const countryDisplay = (
				<Fragment>
					<Tooltip text={ countryName }>
						<span aria-hidden="true">{ country }</span>
					</Tooltip>
					<span className="screen-reader-text">{ countryName }</span>
				</Fragment>
			);

			return [
				{
					display: customerNameLink,
					value: name,
				},
				{
					display: username,
					value: username,
				},
				{
					display: dateLastActiveDisplay,
					value: dateLastActive,
				},
				{
					display: dateRegisteredDisplay,
					value: dateRegistered,
				},
				{
					display: <a href={ 'mailto:' + email }>{ email }</a>,
					value: email,
				},
				{
					display: formatValue(
						getCurrencyConfig(),
						'number',
						ordersCount
					),
					value: ordersCount,
				},
				{
					display: formatAmount( totalSpend ),
					value: getCurrencyFormatDecimal( totalSpend ),
				},
				{
					display: formatAmount( avgOrderValue ),
					value: getCurrencyFormatDecimal( avgOrderValue ),
				},
				{
					display: countryDisplay,
					value: country,
				},
				{
					display: city,
					value: city,
				},
				{
					display: state,
					value: state,
				},
				{
					display: postcode,
					value: postcode,
				},
			];
		} );
	}

	getSummary( totals ) {
		const {
			customers_count: customersCount = 0,
			avg_orders_count: avgOrdersCount = 0,
			avg_total_spend: avgTotalSpend = 0,
			avg_avg_order_value: avgAvgOrderValue = 0,
		} = totals;
		const { formatAmount, getCurrencyConfig } = this.context;
		const currency = getCurrencyConfig();
		return [
			{
				label: _n(
					'customer',
					'customers',
					customersCount,
					'woocommerce-admin'
				),
				value: formatValue( currency, 'number', customersCount ),
			},
			{
				label: _n(
					'Average order',
					'Average orders',
					avgOrdersCount,
					'woocommerce-admin'
				),
				value: formatValue( currency, 'number', avgOrdersCount ),
			},
			{
				label: __( 'Average lifetime spend', 'woocommerce-admin' ),
				value: formatAmount( avgTotalSpend ),
			},
			{
				label: __( 'Average order value', 'woocommerce-admin' ),
				value: formatAmount( avgAvgOrderValue ),
			},
		];
	}

	render() {
		const { isRequesting, query, filters, advancedFilters } = this.props;

		return (
			<ReportTable
				endpoint="customers"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				getSummary={ this.getSummary }
				summaryFields={ [
					'customers_count',
					'avg_orders_count',
					'avg_total_spend',
					'avg_avg_order_value',
				] }
				isRequesting={ isRequesting }
				itemIdField="id"
				query={ query }
				labels={ {
					placeholder: __(
						'Search by customer name',
						'woocommerce-admin'
					),
				} }
				searchBy="customers"
				title={ __( 'Customers', 'woocommerce-admin' ) }
				columnPrefsKey="customers_report_columns"
				filters={ filters }
				advancedFilters={ advancedFilters }
			/>
		);
	}
}

CustomersReportTable.contextType = CurrencyContext;

export default CustomersReportTable;
