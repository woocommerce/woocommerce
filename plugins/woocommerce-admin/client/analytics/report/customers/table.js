/** @format */
/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { Tooltip } from '@wordpress/components';

/**
 * WooCommerce dependencies
 */
import { defaultTableDateFormat } from '@woocommerce/date';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { Date, Link } from '@woocommerce/components';
import { numberFormat } from '@woocommerce/number';
import { COUNTRIES as countries } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import ReportTable from 'analytics/components/report-table';

export default class CustomersReportTable extends Component {
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
				label: __( 'Last Active', 'woocommerce-admin' ),
				key: 'date_last_active',
				defaultSort: true,
				isSortable: true,
			},
			{
				label: __( 'Sign Up', 'woocommerce-admin' ),
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
				label: __( 'Total Spend', 'woocommerce-admin' ),
				key: 'total_spend',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'AOV', 'woocommerce-admin' ),
				screenReaderLabel: __( 'Average Order Value', 'woocommerce-admin' ),
				key: 'avg_order_value',
				isNumeric: true,
			},
			{
				label: __( 'Country', 'woocommerce-admin' ),
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
				label: __( 'Postal Code', 'woocommerce-admin' ),
				key: 'postcode',
				hiddenByDefault: true,
				isSortable: true,
			},
		];
	}

	getCountryName( code ) {
		return typeof countries[ code ] !== 'undefined' ? countries[ code ] : null;
	}

	getRowsContent( customers ) {
		return customers.map( customer => {
			const {
				avg_order_value,
				date_last_active,
				date_registered,
				email,
				name,
				user_id,
				orders_count,
				username,
				total_spend,
				postcode,
				city,
				state,
				country,
			} = customer;
			const countryName = this.getCountryName( country );

			const customerNameLink = user_id ? (
				<Link href={ 'user-edit.php?user_id=' + user_id } type="wp-admin">
					{ name }
				</Link>
			) : (
				name
			);

			const dateLastActive = date_last_active ? (
				<Date date={ date_last_active } visibleFormat={ defaultTableDateFormat } />
			) : (
				'—'
			);

			const dateRegistered = date_registered ? (
				<Date date={ date_registered } visibleFormat={ defaultTableDateFormat } />
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
					display: dateLastActive,
					value: date_last_active,
				},
				{
					display: dateRegistered,
					value: date_registered,
				},
				{
					display: <a href={ 'mailto:' + email }>{ email }</a>,
					value: email,
				},
				{
					display: numberFormat( orders_count ),
					value: orders_count,
				},
				{
					display: formatCurrency( total_spend ),
					value: getCurrencyFormatDecimal( total_spend ),
				},
				{
					display: formatCurrency( avg_order_value ),
					value: getCurrencyFormatDecimal( avg_order_value ),
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
			customers_count = 0,
			avg_orders_count = 0,
			avg_total_spend = 0,
			avg_avg_order_value = 0,
		} = totals;
		return [
			{
				label: _n( 'customer', 'customers', customers_count, 'woocommerce-admin' ),
				value: numberFormat( customers_count ),
			},
			{
				label: _n( 'average order', 'average orders', avg_orders_count, 'woocommerce-admin' ),
				value: numberFormat( avg_orders_count ),
			},
			{
				label: __( 'average lifetime spend', 'woocommerce-admin' ),
				value: formatCurrency( avg_total_spend ),
			},
			{
				label: __( 'average order value', 'woocommerce-admin' ),
				value: formatCurrency( avg_avg_order_value ),
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
				isRequesting={ isRequesting }
				itemIdField="id"
				query={ query }
				labels={ { placeholder: __( 'Search by customer name', 'woocommerce-admin' ) } }
				searchBy="customers"
				title={ __( 'Customers', 'woocommerce-admin' ) }
				columnPrefsKey="customers_report_columns"
				filters={ filters }
				advancedFilters={ advancedFilters }
			/>
		);
	}
}
