/**
 * External dependencies
 */
import { intersection } from 'lodash';

/**
 * Internal dependencies
 */
import {
	productTypes,
	ProductType,
	ProductTypeKey,
	onboardingProductTypesToSurfaced,
	supportedOnboardingProductTypes,
	defaultSurfacedProductTypes,
} from './constants';

export const getProductTypes = ( {
	exclude,
}: {
	exclude?: ProductTypeKey[];
} = {} ): ProductType[] => {
	if ( exclude && exclude?.length > 0 ) {
		return productTypes.filter(
			( productType ) => ! exclude.includes( productType.key )
		);
	}

	return [ ...productTypes ];
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
