/**
 * External dependencies
 */
import { productsStore } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';

const PUBLISHED_PRODUCTS_QUERY_PARAMS = {
	status: 'publish' as const,
	_fields: [ 'id' as const ],
};

export const usePublishedProductsCount = () => {
	return useSelect( ( select ) => {
		const { getProductsTotalCount, hasFinishedResolution } =
			select( productsStore );

		const publishedProductsCount = getProductsTotalCount(
			PUBLISHED_PRODUCTS_QUERY_PARAMS,
			// @ts-expect-error Todo: type of default value is not inferred correctly.
			0
		) as number;

		const loadingPublishedProductsCount = ! hasFinishedResolution(
			'getProductsTotalCount',
			[ PUBLISHED_PRODUCTS_QUERY_PARAMS, 0 ]
		);

		return {
			publishedProductsCount,
			loadingPublishedProductsCount,
			// we consider a user new if they have no published products
			isNewUser: publishedProductsCount < 1,
		};
	}, [] );
};
