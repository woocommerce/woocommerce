/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { getQuery } from '@woocommerce/navigation';

const routeMatchers = [
	{
		matcher: new RegExp( '^/wp/v2/product(?!_)' ),
		getReplaceString: (): string => '/wc/v3/products',
	},
	{
		matcher: new RegExp( '^/wp/v2/product_variation' ),
		replacement: '/wc/v3/products/0/variations',
		getReplaceString: (): string => {
			const query: { page?: string; path?: string } = getQuery();
			const variationMatcher = new RegExp(
				'/product/([0-9]+)/variation/([0-9]+)'
			);
			const matched = ( query.path || '' ).match( variationMatcher );
			if ( matched && matched.length === 3 ) {
				return '/wc/v3/products/' + matched[ 1 ] + '/variations';
			}
			return '/wc/v3/products/0/variations';
		},
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
		if ( options.path ) {
			for ( const { matcher, getReplaceString } of routeMatchers ) {
				if ( matcher.test( options.path ) ) {
					options.path = options.path.replace(
						matcher,
						getReplaceString()
					);
					break;
				}
			}
		}
		return next( options );
	} );
};
