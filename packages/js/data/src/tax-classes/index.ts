/**
 * Internal dependencies
 */
import { STORE_NAME, WC_TAX_CLASSES_NAMESPACE } from './constants';
import { createCrudDataStore } from '../crud';

createCrudDataStore( {
	storeName: STORE_NAME,
	resourceName: 'TaxClass',
	pluralResourceName: 'TaxClasses',
	namespace: WC_TAX_CLASSES_NAMESPACE,
} );

export const EXPERIMENTAL_TAX_CLASSES_STORE_NAME = STORE_NAME;
