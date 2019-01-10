/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { format as formatDate } from '@wordpress/date';

/**
 * WooCommerce dependencies
 */
import { defaultTableDateFormat } from '@woocommerce/date';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import ReportTable from 'analytics/components/report-table';
import { numberFormat } from 'lib/number';

export default class CustomersReportTable extends Component {
	constructor() {
		super();

		this.getHeadersContent = this.getHeadersContent.bind( this );
		this.getRowsContent = this.getRowsContent.bind( this );
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Name', 'wc-admin' ),
				key: 'name',
				required: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'Username', 'wc-admin' ),
				key: 'username',
				hiddenByDefault: true,
			},
			{
				label: __( 'Sign Up', 'wc-admin' ),
				key: 'date_registered',
				defaultSort: true,
				isSortable: true,
			},
			{
				label: __( 'Email', 'wc-admin' ),
				key: 'email',
			},
			{
				label: __( 'Orders', 'wc-admin' ),
				key: 'orders_count',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Lifetime Spend', 'wc-admin' ),
				key: 'total_spend',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'AOV', 'wc-admin' ),
				screenReaderLabel: __( 'Average Order Value', 'wc-admin' ),
				key: 'avg_order_value',
				isNumeric: true,
			},
			{
				label: __( 'Last Active', 'wc-admin' ),
				key: 'date_last_active',
				isSortable: true,
			},
			{
				label: __( 'Country', 'wc-admin' ),
				key: 'country',
			},
			{
				label: __( 'City', 'wc-admin' ),
				key: 'city',
				hiddenByDefault: true,
			},
			{
				label: __( 'Postal Code', 'wc-admin' ),
				key: 'postal_code',
				hiddenByDefault: true,
			},
		];
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
				country,
			} = customer;

			const customerNameLink = user_id ? (
				<Link href={ 'user-edit.php?user_id=' + user_id } type="wp-admin">
					{ name }
				</Link>
			) : (
				name
			);

			const dateRegistered = date_registered
				? formatDate( defaultTableDateFormat, date_registered )
				: '—';

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
					display: formatDate( defaultTableDateFormat, date_last_active ),
					value: date_last_active,
				},
				{
					display: country,
					value: country,
				},
				{
					display: city,
					value: city,
				},
				{
					display: postcode,
					value: postcode,
				},
			];
		} );
	}

	render() {
		const { query } = this.props;

		return (
			<ReportTable
				endpoint="customers"
				extendItemsMethodNames={ {
					load: 'getCustomers',
					getError: 'getCustomersError',
					isRequesting: 'isGetCustomersRequesting',
				} }
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				itemIdField="id"
				query={ query }
				labels={ { placeholder: __( 'Search by customer name', 'wc-admin' ) } }
				searchBy="customers"
				searchParam="name_includes"
				title={ __( 'Customers', 'wc-admin' ) }
				columnPrefsKey="customers_report_columns"
			/>
		);
	}
}
