/** @format */
/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
// import apiFetch from '@wordpress/api-fetch';

/**
 * WooCommerce dependencies
 */
import { stringifyQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
// import { NAMESPACE } from 'store/constants';
import { SWAGGERNAMESPACE } from 'store/constants';

export default {
	async getCoupons( ...args ) {
		const query = args.length === 1 ? args[ 0 ] : args[ 1 ];

		try {
			// @TODO update the API endpoint once it's ready
			// const coupons = await apiFetch( { path: NAMESPACE + 'reports/coupons' + stringifyQuery( query ) } );
			const response = await fetch(
				SWAGGERNAMESPACE + 'reports/coupons' + stringifyQuery( query )
			);
			const coupons = await response.json();
			dispatch( 'wc-admin' ).setCoupons( coupons, query );
		} catch ( error ) {
			dispatch( 'wc-admin' ).setCouponsError( query );
		}
	},
};
