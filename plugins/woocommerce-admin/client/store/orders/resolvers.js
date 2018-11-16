/** @format */
/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * WooCommerce dependencies
 */
import { stringifyQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { NAMESPACE } from 'store/constants';

export default {
	// TODO: Use controls data plugin or fresh-data instead of async
	async getOrders( ...args ) {
		// This is interim code to work with either 2.x or 3.x version of @wordpress/data
		// TODO: Change to just `getNotes( query )` after Gutenberg plugin uses @wordpress/data 3+
		const query = args.length === 1 ? args[ 0 ] : args[ 1 ];

		try {
			const orders = await apiFetch( { path: NAMESPACE + 'orders' + stringifyQuery( query ) } );
			dispatch( 'wc-admin' ).setOrders( orders, query );
		} catch ( error ) {
			dispatch( 'wc-admin' ).setOrdersError( query );
		}
	},
};
