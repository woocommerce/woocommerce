/**
 * Internal dependencies
 */
import { STORE_NAME, WC_PRODUCT_ATTRIBUTES_NAMESPACE } from './constants';
import { createCrudDataStore } from '../crud';

createCrudDataStore( {
	storeName: STORE_NAME,
	resourceName: 'ProductAttribute',
	pluralResourceName: 'ProductAttributes',
	namespace: WC_PRODUCT_ATTRIBUTES_NAMESPACE,
} );

export const EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME = STORE_NAME;
