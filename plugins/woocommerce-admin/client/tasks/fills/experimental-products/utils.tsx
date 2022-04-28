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
} from './constants';

export const getProductTypes = (
	exclude: ProductTypeKey[] = []
): ProductType[] =>
	productTypes.filter(
		( productType ) => ! exclude.includes( productType.key )
	);

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
		return productTypes.map( ( p ) => p.key );
	}
	return onboardingProductTypesToSurfaced[ sortedKeyStr ];
};
