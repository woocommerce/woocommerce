/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getRequestByIdString } from 'lib/async-requests';
import { getTaxCode } from './utils';
import { NAMESPACE } from 'store/constants';

export const charts = [
	{
		key: 'order_tax',
		label: __( 'Order Tax', 'wc-admin' ),
		type: 'currency',
	},
	{
		key: 'total_tax',
		label: __( 'Total Tax', 'wc-admin' ),
		type: 'currency',
	},
	{
		key: 'shipping_tax',
		label: __( 'Shipping Tax', 'wc-admin' ),
		type: 'currency',
	},
	{
		key: 'orders_count',
		label: __( 'Orders Count', 'wc-admin' ),
		type: 'number',
	},
];

export const filters = [
	{
		label: __( 'Show', 'wc-admin' ),
		staticParams: [ 'chart' ],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{ label: __( 'All Taxes', 'wc-admin' ), value: 'all' },
			{
				label: __( 'Comparison', 'wc-admin' ),
				value: 'compare-tax-codes',
				chartMode: 'item-comparison',
				settings: {
					type: 'taxes',
					param: 'taxes',
					getLabels: getRequestByIdString( NAMESPACE + 'taxes', tax => ( {
						id: tax.id,
						label: getTaxCode( tax ),
					} ) ),
					labels: {
						helpText: __( 'Select at least two tax codes to compare', 'wc-admin' ),
						placeholder: __( 'Search for tax codes to compare', 'wc-admin' ),
						title: __( 'Compare Tax Codes', 'wc-admin' ),
						update: __( 'Compare', 'wc-admin' ),
					},
				},
			},
		],
	},
];
