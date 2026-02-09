/**
 * Internal dependencies
 */
import { STORE_NAME, WC_PRODUCT_CATEGORIES_NAMESPACE } from './constants';
import { createCrudDataStore } from '../crud';
import { ProductCategoryActions, ProductCategorySelectors } from './types';

export const store = createCrudDataStore<
	ProductCategoryActions,
	ProductCategorySelectors
>( {
	storeName: STORE_NAME,
	resourceName: 'ProductCategory',
	pluralResourceName: 'ProductCategories',
	namespace: WC_PRODUCT_CATEGORIES_NAMESPACE,
} );

export const EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME = STORE_NAME;
