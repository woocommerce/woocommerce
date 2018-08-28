/** @format */
/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';
import { stringify } from 'qs';

export default {
	async getProducts( state, query ) {
		try {
			const params = query ? '?' + stringify( query ) : '';
			const products = await apiFetch( { path: '/wc/v3/products' + params } );
			dispatch( 'wc-admin' ).setProducts( products, query );
		} catch ( error ) {
			dispatch( 'wc-admin' ).setProductsError( query );
		}
	},
};
