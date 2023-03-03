/**
 * Internal dependencies
 */
import { STORE_NAME, WC_PRODUCT_CATEGORIES_NAMESPACE } from './constants';
import { createCrudDataStore } from '../crud';

createCrudDataStore( {
	storeName: STORE_NAME,
	resourceName: 'ProductCategory',
	pluralResourceName: 'ProductCategories',
	namespace: WC_PRODUCT_CATEGORIES_NAMESPACE,
} );

export const EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME = STORE_NAME;
