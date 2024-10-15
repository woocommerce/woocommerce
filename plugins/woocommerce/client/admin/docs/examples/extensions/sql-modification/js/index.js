/**
 * External dependencies
 */

import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Add a dropdown to a report.
 *
 * @param {Array} filters - set of filters in a report.
 * @return {Array} amended set of filters.
 */
const addCurrencyFilters = ( filters ) => {
	return [
		{
			label: __( 'Currency', 'plugin-domain' ),
			staticParams: [],
			param: 'currency',
			showFilters: () => true,
			defaultValue: 'USD',
			filters: [ ...( wcSettings.multiCurrency || [] ) ],
		},
		...filters,
	];
};

addFilter(
	'woocommerce_admin_revenue_report_filters',
	'plugin-domain',
	addCurrencyFilters
);
addFilter(
	'woocommerce_admin_orders_report_filters',
	'plugin-domain',
	addCurrencyFilters
);
addFilter(
	'woocommerce_admin_products_report_filters',
	'plugin-domain',
	addCurrencyFilters
);
addFilter(
	'woocommerce_admin_categories_report_filters',
	'plugin-domain',
	addCurrencyFilters
);
addFilter(
	'woocommerce_admin_coupons_report_filters',
	'plugin-domain',
	addCurrencyFilters
);
addFilter(
	'woocommerce_admin_taxes_report_filters',
	'plugin-domain',
	addCurrencyFilters
);
addFilter(
	'woocommerce_admin_dashboard_filters',
	'plugin-domain',
	addCurrencyFilters
);

/**
 * Add a column to a report table. Include a header and
 * manipulate each row to handle the added parameter.
 *
 * @param {Object} reportTableData - table data.
 * @return {Object} - table data.
 */
const addTableColumn = ( reportTableData ) => {
	const includedReports = [
		'revenue',
		'products',
		'orders',
		'categories',
		'coupons',
		'taxes',
	];
	if ( ! includedReports.includes( reportTableData.endpoint ) ) {
		return reportTableData;
	}

	const newHeaders = [
		{
			label: 'Currency',
			key: 'currency',
		},
		...reportTableData.headers,
	];
	const newRows = reportTableData.rows.map( ( row, index ) => {
		const item = reportTableData.items.data[ index ];
		const currency =
			reportTableData.endpoint === 'revenue'
				? item.subtotals.currency
				: item.currency;
		const newRow = [
			{
				display: currency,
				value: currency,
			},
			...row,
		];
		return newRow;
	} );

	reportTableData.headers = newHeaders;
	reportTableData.rows = newRows;

	return reportTableData;
};

addFilter( 'woocommerce_admin_report_table', 'plugin-domain', addTableColumn );

/**
 * Add 'currency' to the list of persisted queries so that the parameter remains
 * when navigating from report to report.
 *
 * @param {Array} params - array of report slugs.
 * @return {Array} - array of report slugs including 'currency'.
 */
const persistQueries = ( params ) => {
	params.push( 'currency' );
	return params;
};

addFilter(
	'woocommerce_admin_persisted_queries',
	'plugin-domain',
	persistQueries
);

const currencies = {
	ZAR: {
		code: 'ZAR',
		symbol: 'R',
		symbolPosition: 'left',
		thousandSeparator: ' ',
		decimalSeparator: ',',
		precision: 2,
	},
	NZD: {
		code: 'NZD',
		symbol: '$NZ',
		symbolPosition: 'left',
		thousandSeparator: ',',
		decimalSeparator: '.',
		precision: 2,
	},
};

const updateReportCurrencies = ( config, { currency } ) => {
	if ( currency && currencies[ currency ] ) {
		return currencies[ currency ];
	}
	return config;
};

addFilter(
	'woocommerce_admin_report_currency',
	'plugin-domain',
	updateReportCurrencies
);
