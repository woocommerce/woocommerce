/**
 * External dependencies
 */
import { registerStore } from '@wordpress/data';
import { SelectFromMap, DispatchFromMap } from '@automattic/data-stores';
import { Reducer, AnyAction } from 'redux';
/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer, { State } from './reducer';
import controls from '../controls';
import { WPDataActions, WPDataSelectors } from '../types';
import { getItemsType } from './selectors';
import { PromiseifySelectors } from '../types/promiseify-selectors';
export * from './types';
export type { State };

// @ts-expect-error migrate store to createReduxStore function https://github.com/woocommerce/woocommerce/issues/55380
registerStore< State >( STORE_NAME, {
	reducer: reducer as Reducer< State, AnyAction >,
	actions,
	controls,
	selectors,
	resolvers,
} );

export const ITEMS_STORE_NAME = STORE_NAME;

export type ItemsSelector = Omit<
	SelectFromMap< typeof selectors >,
	'getItems'
> & {
	getItems: getItemsType;
} & WPDataSelectors;

declare module '@wordpress/data' {
	function dispatch(
		key: typeof STORE_NAME
	): DispatchFromMap< typeof actions & WPDataActions >;
	function select( key: typeof STORE_NAME ): ItemsSelector;
	function resolveSelect(
		key: typeof STORE_NAME
	): PromiseifySelectors< ItemsSelector >;
}
