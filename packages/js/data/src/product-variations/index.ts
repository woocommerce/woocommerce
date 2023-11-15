/**
 * External dependencies
 */
import { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import { STORE_NAME, WC_PRODUCT_VARIATIONS_NAMESPACE } from './constants';
import { createCrudDataStore } from '../crud';
import * as actions from './actions';
import * as selectors from './selectors';
import { reducer } from './reducer';
import { ResourceState } from '../crud/reducer';

createCrudDataStore( {
	storeName: STORE_NAME,
	resourceName: 'ProductVariation',
	pluralResourceName: 'ProductVariations',
	namespace: WC_PRODUCT_VARIATIONS_NAMESPACE,
	storeConfig: {
		reducer: reducer as Reducer< ResourceState >,
		actions,
		selectors,
	},
} );

export const EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME = STORE_NAME;
