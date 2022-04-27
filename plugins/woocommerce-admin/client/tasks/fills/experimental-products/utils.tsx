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

export const getSurfacedProductKeys = ( onboardingProductTypes: string[] ) => {
	const sortedKeyStr = onboardingProductTypes.sort().join( ',' );
	if ( ! onboardingProductTypesToSurfaced.hasOwnProperty( sortedKeyStr ) ) {
		return productTypes.map( ( p ) => p.key );
	}
	return onboardingProductTypesToSurfaced[ sortedKeyStr ];
};
