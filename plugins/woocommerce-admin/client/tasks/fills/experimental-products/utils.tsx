/**
 * Internal dependencies
 */
import {
	productTypes,
	ProductTypeKey,
	onboardingProductTypesToSurfaced,
} from './constants';

export const getProductTypes = ( exclude: ProductTypeKey[] = [] ) =>
	productTypes.filter(
		( productType ) => ! exclude.includes( productType.key )
	);

/**
 * Get key of surfaced product types by onboarding product types.
 * Return all product types if onboarding product types is empty.
 */
export const getSurfacedProductKeys = ( onboardingProductTypes: string[] ) => {
	const sortedKeyStr = onboardingProductTypes.sort().join( ',' );
	if ( ! onboardingProductTypesToSurfaced.hasOwnProperty( sortedKeyStr ) ) {
		return productTypes.map( ( p ) => p.key );
	}
	return onboardingProductTypesToSurfaced[ sortedKeyStr ];
};
