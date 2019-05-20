/** @format */
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

const TAXES_REPORT_CHART_FILTER = 'woocommerce_admin_taxes_report_chart_filter';

export const charts = applyFilters( TAXES_REPORT_CHART_FILTER, [
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
		label: __( 'Orders Count', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'orders_count',
		type: 'number',
	},
] );

export const filters = [
	{
		label: __( 'Show', 'woocommerce-admin' ),
		staticParams: [ 'chart' ],
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
					getLabels: getRequestByIdString( NAMESPACE + '/taxes', tax => ( {
						id: tax.id,
						label: getTaxCode( tax ),
					} ) ),
					labels: {
						helpText: __( 'Check at least two tax codes below to compare', 'woocommerce-admin' ),
						placeholder: __( 'Search for tax codes to compare', 'woocommerce-admin' ),
						title: __( 'Compare Tax Codes', 'woocommerce-admin' ),
						update: __( 'Compare', 'woocommerce-admin' ),
					},
				},
			},
		],
	},
];
