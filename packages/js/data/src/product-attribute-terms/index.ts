/**
 * External dependencies
 */
import { SelectFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { STORE_NAME, WC_PRODUCT_ATTRIBUTE_TERMS_NAMESPACE } from './constants';
import { createCrudDataStore } from '../crud';
import { ActionDispatchers, ProductAttributeTermsSelectors } from './types';
import { WPDataSelectors } from '../types';
import { PromiseifySelectors } from '../types/promiseify-selectors';

createCrudDataStore( {
	storeName: STORE_NAME,
	resourceName: 'ProductAttributeTerm',
	pluralResourceName: 'ProductAttributeTerms',
	namespace: WC_PRODUCT_ATTRIBUTE_TERMS_NAMESPACE,
} );

export const EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME = STORE_NAME;

declare module '@wordpress/data' {
	// TODO: convert action.js to TS
	function dispatch( key: typeof STORE_NAME ): ActionDispatchers;
	function select(
		key: typeof STORE_NAME
	): SelectFromMap< ProductAttributeTermsSelectors > & WPDataSelectors;
	function resolveSelect(
		key: typeof STORE_NAME
	): PromiseifySelectors< SelectFromMap< ProductAttributeTermsSelectors > >;
}
