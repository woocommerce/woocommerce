/** @format */
/**
 * External dependencies
 */
// import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';
import { stringify } from 'qs';
import { isEmpty } from 'lodash';

export default {
	async getVariations( ...args ) {
		const query = args.length === 1 ? args[ 0 ] : args[ 1 ];

		try {
			const params = isEmpty( query ) ? '' : '?' + stringify( query );
			// @TODO: Use /reports/variations when it becomes available
			// const variations = await apiFetch( {
			// 	path: '/wc/v3/reports/variations' + params,
			// } );
			const variations = await fetch(
				'https://virtserver.swaggerhub.com/peterfabian/wc-v3-api/1.0.0/reports/variations' + params
			);
			const data = await variations.json();
			dispatch( 'wc-admin' ).setVariations( data, query );
		} catch ( error ) {
			dispatch( 'wc-admin' ).setVariationsError( query );
		}
	},
};
