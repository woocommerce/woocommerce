/**
 * Internal dependencies
 */
import { createCrudDataStore } from '../crud';
import { STORE_NAME, WC_TAX_CLASSES_NAMESPACE } from './constants';
import * as resolvers from './resolvers';
import { TaxClassActions, TaxClassSelectors } from './types';

export const store = createCrudDataStore< TaxClassActions, TaxClassSelectors >(
	{
		storeName: STORE_NAME,
		resourceName: 'TaxClass',
		pluralResourceName: 'TaxClasses',
		namespace: WC_TAX_CLASSES_NAMESPACE,
		storeConfig: {
			resolvers,
		},
	}
);

export const EXPERIMENTAL_TAX_CLASSES_STORE_NAME = STORE_NAME;
