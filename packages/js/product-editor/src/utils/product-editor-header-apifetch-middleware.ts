/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { isProductEditor } from './is-product-editor';

export const productEditorHeaderApiFetchMiddleware = () => {
	// This is needed to ensure that we use the correct namespace for the entity data store
	// without disturbing the rest_namespace outside of the product block editor.
	apiFetch.use( ( options, next ) => {
		if ( isProductEditor() ) {
			options.headers = options.headers || {};
			options.headers[ 'X-Wc-From-Product-Editor' ] = '1';
		}
		return next( options );
	} );
};
