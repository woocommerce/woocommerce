/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

export const isProductEditor = () => {
	const query: { page?: string; path?: string } = getQuery();
	return (
		query?.page === 'wc-admin' &&
		[ '/add-product', '/product/' ].some( ( path ) =>
			query?.path?.startsWith( path )
		)
	);
};
