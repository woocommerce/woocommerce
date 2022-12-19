/**
 * External dependencies
 */
import { PartialProduct, ProductVariation } from '@woocommerce/data';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { SimpleNavigationProps } from '../shared/simple-navigation';

export default function useProductVariationNavigation( {
	product,
	productVariation,
}: UseProductVariationNavigationInput ): UseProductVariationNavigationOutput {
	const { variations } = product;
	const variationIds = variations ?? [];

	const currentIndex = variationIds.indexOf( productVariation.id ?? -1 );
	const canNavigateBack = currentIndex > 0;
	const canNavigateNext = currentIndex < variationIds.length - 1;
	const prevVariationId = canNavigateBack
		? variationIds[ currentIndex - 1 ]
		: undefined;
	const nextVariationId = canNavigateNext
		? variationIds[ currentIndex + 1 ]
		: undefined;

	const persistedQuery = getPersistedQuery();

	return {
		actionHref: getNewPath( persistedQuery, `/product/${ product.id }` ),
		backHref: prevVariationId
			? getNewPath(
					persistedQuery,
					`/product/${ product.id }/variation/${ prevVariationId }`
			  )
			: undefined,
		nextHref: nextVariationId
			? getNewPath(
					persistedQuery,
					`/product/${ product.id }/variation/${ nextVariationId }`
			  )
			: undefined,
	};
}

export type UseProductVariationNavigationInput = {
	product: PartialProduct;
	productVariation: Partial< ProductVariation >;
};

export type UseProductVariationNavigationOutput = Pick<
	SimpleNavigationProps,
	'actionHref' | 'backHref' | 'nextHref'
>;
