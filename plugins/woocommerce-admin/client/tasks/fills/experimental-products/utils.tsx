/**
 * Internal dependencies
 */
import { productTypes, ProductTypeKey } from './constants';

export const getProductTypes = ( exclude: ProductTypeKey[] = [] ) =>
	productTypes.filter(
		( productType ) => ! exclude.includes( productType.key )
	);
