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
	async getTaxes( ...args ) {
		const query = args.length === 1 ? args[ 0 ] : args[ 1 ];

		try {
			// @TODO update the API endpoint once it's ready
			// const taxes = await apiFetch( { path: NAMESPACE + 'reports/taxes' + stringifyQuery( query ) } );
			const response = await fetch( SWAGGERNAMESPACE + 'reports/taxes' + stringifyQuery( query ) );
			const taxes = await response.json();
			dispatch( 'wc-admin' ).setTaxes( taxes, query );
		} catch ( error ) {
			dispatch( 'wc-admin' ).setTaxesError( query );
		}
	},
};
