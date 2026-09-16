/**
 * External dependencies
 */
import { intersection } from 'lodash';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import {
	productTypes as baseProductTypes,
	ProductType,
	ProductTypeKey,
	onboardingProductTypesToSurfaced,
	supportedOnboardingProductTypes,
	defaultSurfacedProductTypes,
	SETUP_TASKLIST_PRODUCT_TYPES_FILTER,
} from './constants';

export const getProductTypes = ( {
	exclude,
}: {
	exclude?: ProductTypeKey[];
} = {} ): ProductType[] => {
	/**
	 * Experimental: Filter for adding custom product types to tasklist.
	 *
	 * @filter woocommerce_tasklist_experimental_product_types
	 * @param {Object} productTypes Array of product types.
	 */
	const injectedProductTypes = applyFilters(
		SETUP_TASKLIST_PRODUCT_TYPES_FILTER,
		[]
	) as ProductType[];

	let productTypes = [ ...baseProductTypes, ...injectedProductTypes ];

	if ( exclude && exclude?.length > 0 ) {
		productTypes = productTypes.filter(
			( productType ) => ! exclude.includes( productType.key )
		);
	}

	return productTypes;
};

/**
 * Get key of surfaced product types by onboarding product types.
 * Return all product types if onboarding product types is empty.
 */
export const getSurfacedProductTypeKeys = (
	onboardingProductTypes: string[]
) => {
	const validOnboardingProductTypes = intersection(
		onboardingProductTypes,
		supportedOnboardingProductTypes
	);
	const sortedKeyStr = validOnboardingProductTypes.sort().join( ',' );
	if ( ! onboardingProductTypesToSurfaced.hasOwnProperty( sortedKeyStr ) ) {
		return defaultSurfacedProductTypes;
	}
	return onboardingProductTypesToSurfaced[ sortedKeyStr ];
};
