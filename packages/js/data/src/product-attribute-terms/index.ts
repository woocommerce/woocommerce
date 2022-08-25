/**
 * Internal dependencies
 */
import { STORE_NAME, WC_PRODUCT_ATTRIBUTE_TERMS_NAMESPACE } from './constants';
import { createCrudDataStore } from '../crud';

createCrudDataStore( {
	storeName: STORE_NAME,
	resourceName: 'ProductAttributeTerm',
	pluralResourceName: 'ProductAttributeTerms',
	namespace: WC_PRODUCT_ATTRIBUTE_TERMS_NAMESPACE,
} );

export const EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME = STORE_NAME;
