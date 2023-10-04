/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { getQuery } from '@woocommerce/navigation';

const routeMatchers = [
	{
		matcher: new RegExp( '^/wp/v2/product(?!_)' ),
		replacement: '/wc/v3/products',
	},
	{
		matcher: new RegExp( '^/wp/v2/product_variation' ),
		replacement: '/wc/v3/products/0/variations',
	},
];

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
		if ( options.path && isProductEditor() ) {
			for ( const { matcher, replacement } of routeMatchers ) {
				if ( matcher.test( options.path ) ) {
					options.path = options.path.replace( matcher, replacement );
					break;
				}
			}
		}
		return next( options );
	} );
};
