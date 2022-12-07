/**
 * Internal dependencies
 */
import { STORE_NAME, WC_PRODUCT_VARIATIONS_NAMESPACE } from './constants';
import { createCrudDataStore } from '../crud';

createCrudDataStore( {
	storeName: STORE_NAME,
	resourceName: 'ProductVariation',
	pluralResourceName: 'ProductVariations',
	namespace: WC_PRODUCT_VARIATIONS_NAMESPACE,
} );

export const EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME = STORE_NAME;
