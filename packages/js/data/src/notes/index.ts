/**
 * External dependencies
 */
import { registerStore } from '@wordpress/data';
import { SelectFromMap, DispatchFromMap } from '@automattic/data-stores';
import { Reducer, AnyAction } from 'redux';
import { controls } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { WPDataActions, WPDataSelectors } from '../types';
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer, { State } from './reducer';

export * from './types';
export type { State };

registerStore( STORE_NAME, {
	reducer: reducer as Reducer< State, AnyAction >,
	actions,
	controls,
	selectors,
	resolvers,
} );

export const NOTES_STORE_NAME = STORE_NAME;

declare module '@wordpress/data' {
	function dispatch(
		key: typeof STORE_NAME
	): DispatchFromMap< typeof actions & WPDataActions >;
	function select(
		key: typeof STORE_NAME
	): SelectFromMap< typeof selectors > & WPDataSelectors;
}
