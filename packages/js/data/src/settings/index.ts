/**
 * External dependencies
 */

import { registerStore } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';
import { Reducer } from 'redux';
import { SelectFromMap, DispatchFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { WPDataSelectors } from '../types';
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer, { State } from './reducer';
import { SettingsState } from './types';
export * from './types';
export type { State };

registerStore< State >( STORE_NAME, {
	reducer: reducer as Reducer< SettingsState >,
	actions,
	controls,
	selectors,
	resolvers,
} );

export const SETTINGS_STORE_NAME = STORE_NAME;

declare module '@wordpress/data' {
	function dispatch(
		key: typeof STORE_NAME
	): DispatchFromMap< typeof actions >;
	function select(
		key: typeof STORE_NAME
	): SelectFromMap< typeof selectors > & WPDataSelectors;
}
