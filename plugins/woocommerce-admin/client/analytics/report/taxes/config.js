/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { find } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { getIdsFromQuery, stringifyQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { getTaxCode } from './utils';
import { NAMESPACE } from 'wc-api/constants';

export const charts = [
	{
		key: 'total_tax',
		label: __( 'Total Tax', 'wc-admin' ),
		order: 'desc',
		orderby: 'total_tax',
		type: 'currency',
	},
	{
		key: 'order_tax',
		label: __( 'Order Tax', 'wc-admin' ),
		order: 'desc',
		orderby: 'order_tax',
		type: 'currency',
	},
	{
		key: 'shipping_tax',
		label: __( 'Shipping Tax', 'wc-admin' ),
		order: 'desc',
		orderby: 'shipping_tax',
		type: 'currency',
	},
	{
		key: 'orders_count',
		label: __( 'Orders Count', 'wc-admin' ),
		order: 'desc',
		orderby: 'orders_count',
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
				value: 'compare-taxes',
				chartMode: 'item-comparison',
				settings: {
					type: 'taxes',
					param: 'taxes',
					// @TODO: Core /taxes endpoint should support a collection param 'include'.
					getLabels: function( queryString = '' ) {
						const idList = getIdsFromQuery( queryString );
						if ( idList.length < 1 ) {
							return Promise.resolve( [] );
						}
						const payload = stringifyQuery( {
							per_page: -1,
						} );
						return apiFetch( { path: NAMESPACE + '/taxes' + payload } ).then( taxes => {
							return idList.map( id => {
								const tax = find( taxes, { id } );
								return {
									id: tax.id,
									label: getTaxCode( tax ),
								};
							} );
						} );
					},
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
