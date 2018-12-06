/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const dateRangeFilter = {
	show: false,
};

export const filters = [
	{
		label: __( 'Show', 'wc-admin' ),
		staticParams: [],
		param: 'filter',
		showFilters: () => true,
		filters: [ { label: __( 'All Registered Customers', 'wc-admin' ), value: 'all' } ],
	},
];
