/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { getQuery } from '@woocommerce/navigation';

const isProductEditor = () => {
	const query: { page?: string; path?: string } = getQuery();
	return (
		query?.page === 'wc-admin' &&
		[ '/add-product', '/product/' ].some( ( path ) =>
			query?.path?.startsWith( path )
		)
	);
};

export const productApiFetchMiddleware = () => {
	// This is needed to ensure that we use the correct namespace for the entity data store
	// without disturbing the rest_namespace outside of the product block editor.
	apiFetch.use( ( options, next ) => {
		const versionTwoRegex = new RegExp( '^/wp/v2/product' );
		if (
			options.path &&
			versionTwoRegex.test( options?.path ) &&
			isProductEditor()
		) {
			options.path = options.path.replace(
				versionTwoRegex,
				'/wc/v3/products'
			);
		}
		return next( options );
	} );
};
