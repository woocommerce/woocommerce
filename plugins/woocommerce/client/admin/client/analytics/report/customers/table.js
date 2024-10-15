/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Fragment, useContext } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Tooltip } from '@wordpress/components';
import { Date, Link, Pill } from '@woocommerce/components';
import { formatValue } from '@woocommerce/number';
import { getAdminLink } from '@woocommerce/settings';
import { defaultTableDateFormat } from '@woocommerce/date';
import { COUNTRIES_STORE_NAME } from '@woocommerce/data';
import { CurrencyContext } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import ReportTable from '../../components/report-table';
import { getAdminSetting } from '~/utils/admin-settings';

function CustomersReportTable( {
	isRequesting,
	query,
	filters,
	advancedFilters,
} ) {
	const context = useContext( CurrencyContext );
	const { countries, loadingCountries } = useSelect( ( select ) => {
		const { getCountries, hasFinishedResolution } =
			select( COUNTRIES_STORE_NAME );
		return {
			countries: getCountries(),
			loadingCountries: ! hasFinishedResolution( 'getCountries' ),
		};
	} );

	const getHeadersContent = () => {
		return [
			{
				label: __( 'Name', 'woocommerce' ),
				key: 'name',
				required: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'Username', 'woocommerce' ),
				key: 'username',
				hiddenByDefault: true,
			},
			{
				label: __( 'Last active', 'woocommerce' ),
				key: 'date_last_active',
				defaultSort: true,
				isSortable: true,
			},
			{
				label: __( 'Date registered', 'woocommerce' ),
				key: 'date_registered',
				isSortable: true,
			},
			{
				label: __( 'Email', 'woocommerce' ),
				key: 'email',
			},
			{
				label: __( 'Orders', 'woocommerce' ),
				key: 'orders_count',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Total spend', 'woocommerce' ),
				key: 'total_spend',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'AOV', 'woocommerce' ),
				screenReaderLabel: __( 'Average order value', 'woocommerce' ),
				key: 'avg_order_value',
				isNumeric: true,
			},
			{
				label: __( 'Country / Region', 'woocommerce' ),
				key: 'country',
				isSortable: true,
			},
			{
				label: __( 'City', 'woocommerce' ),
				key: 'city',
				hiddenByDefault: true,
				isSortable: true,
			},
			{
				label: __( 'Region', 'woocommerce' ),
				key: 'state',
				hiddenByDefault: true,
				isSortable: true,
			},
			{
				label: __( 'Postal code', 'woocommerce' ),
				key: 'postcode',
				hiddenByDefault: true,
				isSortable: true,
			},
		];
	};

	const getCountryName = ( code ) => {
		return typeof countries[ code ] !== 'undefined'
			? countries[ code ]
			: null;
	};

	const getRowsContent = ( customers ) => {
		const dateFormat = getAdminSetting(
			'dateFormat',
			defaultTableDateFormat
		);
		const {
			formatAmount,
			formatDecimal: getCurrencyFormatDecimal,
			getCurrencyConfig,
		} = context;

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
			const countryName = getCountryName( country );
			const customerName =
				name?.trim() !== '' ? (
					name
				) : (
					<Pill>{ __( 'Guest', 'woocommerce' ) }</Pill>
				);

			const customerNameLink = userId ? (
				<Link
					href={ getAdminLink( 'user-edit.php?user_id=' + userId ) }
					type="wp-admin"
				>
					{ name }
				</Link>
			) : (
				customerName
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
	};

	const getSummary = ( totals ) => {
		const {
			customers_count: customersCount = 0,
			avg_orders_count: avgOrdersCount = 0,
			avg_total_spend: avgTotalSpend = 0,
			avg_avg_order_value: avgAvgOrderValue = 0,
		} = totals;
		const { formatAmount, getCurrencyConfig } = context;
		const currency = getCurrencyConfig();
		return [
			{
				label: _n(
					'customer',
					'customers',
					customersCount,
					'woocommerce'
				),
				value: formatValue( currency, 'number', customersCount ),
			},
			{
				label: _n(
					'Average order',
					'Average orders',
					avgOrdersCount,
					'woocommerce'
				),
				value: formatValue( currency, 'number', avgOrdersCount ),
			},
			{
				label: __( 'Average lifetime spend', 'woocommerce' ),
				value: formatAmount( avgTotalSpend ),
			},
			{
				label: __( 'Average order value', 'woocommerce' ),
				value: formatAmount( avgAvgOrderValue ),
			},
		];
	};

	return (
		<ReportTable
			endpoint="customers"
			getHeadersContent={ getHeadersContent }
			getRowsContent={ getRowsContent }
			getSummary={ getSummary }
			summaryFields={ [
				'customers_count',
				'avg_orders_count',
				'avg_total_spend',
				'avg_avg_order_value',
			] }
			isRequesting={ isRequesting || loadingCountries }
			itemIdField="id"
			query={ query }
			labels={ {
				placeholder: __( 'Search by customer name', 'woocommerce' ),
			} }
			searchBy="customers"
			title={ __( 'Customers', 'woocommerce' ) }
			columnPrefsKey="customers_report_columns"
			filters={ filters }
			advancedFilters={ advancedFilters }
		/>
	);
}

export default CustomersReportTable;
