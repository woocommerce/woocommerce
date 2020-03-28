/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import {
	handleFetchError,
	receiveRecommendedPlugins,
	receiveBlogPosts,
} from './actions';
import { API_NAMESPACE } from './constants';

export function* getRecommendedPlugins() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/overview/recommended?per_page=6`
		} );

		if ( response ) {
			yield receiveRecommendedPlugins( response );
		} else {
			throw new Error();
		}
	} catch ( error ) {
		yield handleFetchError( error, __( 'There was an error loading recommended extensions.', 'woocommerce-admin' ) );
	}
}

export function* getBlogPosts() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/overview/knowledge-base`,
			method: 'GET',
		} );

		if ( response ) {
			yield receiveBlogPosts( response );
		} else {
			throw new Error();
		}
	} catch ( error ) {
		yield handleFetchError( error, __( 'There was an error loading knowledge base posts.', 'woocommerce-admin' ) );
	}
}
