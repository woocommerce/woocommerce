/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
import { SelectFromMap } from '@automattic/data-stores';
/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer, { State } from './reducer';
import controls from '../controls';
import { WPDataSelectors } from '../types';
import { getItemsType } from './selectors';
import { PromiseifySelectors } from '../types/promiseify-selectors';
export * from './types';
export type { State };

export const store = createReduxStore( STORE_NAME, {
	reducer,
	actions,
	controls,
	selectors,
	resolvers,
} );

register( store );

export const ITEMS_STORE_NAME = STORE_NAME;

// We need to provide those types to support type parameters in the selectors.
export type ItemsSelector = Omit<
	// SelectFromMap removes type parameters, so we need to explicitly provide the generic type.
	SelectFromMap< typeof selectors >,
	'getItems'
> & {
	getItems: getItemsType;
} & WPDataSelectors;

declare module '@wordpress/data' {
	function select( key: typeof STORE_NAME | typeof store ): ItemsSelector;
	function resolveSelect(
		key: typeof STORE_NAME | typeof store
	): PromiseifySelectors< ItemsSelector >;
}
