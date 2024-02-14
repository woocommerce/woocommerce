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
} from './constants';
import './dummyplugin';

export const getProductTypes = ( {
	exclude,
}: {
	exclude?: ProductTypeKey[];
} = {} ): ProductType[] => {
	let productTypes = [ ...baseProductTypes ] as ProductType[];
	if ( exclude && exclude?.length > 0 ) {
		productTypes = productTypes.filter(
			( productType ) => ! exclude.includes( productType.key )
		);
	}

	/**
	 * Filter for adding custom product types to tasklist.
	 *
	 * @filter woocommerce_tasklist_experimental_product_types
	 * @param {Object} productTypes Array of product types.
	 */
	productTypes = applyFilters(
		'woocommerce_tasklist_experimental_product_types',
		productTypes
	) as ProductType[];

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
