/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { getRequestByIdString } from 'lib/async-requests';
import { getTaxCode } from './utils';
import { NAMESPACE } from 'wc-api/constants';

const TAXES_REPORT_CHARTS_FILTER = 'woocommerce_admin_taxes_report_charts';
const TAXES_REPORT_FILTERS_FILTER = 'woocommerce_admin_taxes_report_filters';
const TAXES_REPORT_ADVANCED_FILTERS_FILTER =
	'woocommerce_admin_taxes_report_advanced_filters';

export const charts = applyFilters( TAXES_REPORT_CHARTS_FILTER, [
	{
		key: 'total_tax',
		label: __( 'Total Tax', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'total_tax',
		type: 'currency',
	},
	{
		key: 'order_tax',
		label: __( 'Order Tax', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'order_tax',
		type: 'currency',
	},
	{
		key: 'shipping_tax',
		label: __( 'Shipping Tax', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'shipping_tax',
		type: 'currency',
	},
	{
		key: 'orders_count',
		label: __( 'Orders', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'orders_count',
		type: 'number',
	},
] );

export const filters = applyFilters( TAXES_REPORT_FILTERS_FILTER, [
	{
		label: __( 'Show', 'woocommerce-admin' ),
		staticParams: [ 'chartType', 'paged', 'per_page' ],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{ label: __( 'All Taxes', 'woocommerce-admin' ), value: 'all' },
			{
				label: __( 'Comparison', 'woocommerce-admin' ),
				value: 'compare-taxes',
				chartMode: 'item-comparison',
				settings: {
					type: 'taxes',
					param: 'taxes',
					getLabels: getRequestByIdString(
						NAMESPACE + '/taxes',
						( tax ) => ( {
							id: tax.id,
							label: getTaxCode( tax ),
						} )
					),
					labels: {
						helpText: __(
							'Check at least two tax codes below to compare',
							'woocommerce-admin'
						),
						placeholder: __(
							'Search for tax codes to compare',
							'woocommerce-admin'
						),
						title: __( 'Compare Tax Codes', 'woocommerce-admin' ),
						update: __( 'Compare', 'woocommerce-admin' ),
					},
				},
			},
		],
	},
] );

export const advancedFilters = applyFilters(
	TAXES_REPORT_ADVANCED_FILTERS_FILTER,
	{}
);
