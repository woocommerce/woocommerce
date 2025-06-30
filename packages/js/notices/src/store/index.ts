/**
 * External dependencies
 */
import { registerStore } from '@wordpress/data';
import { SelectFromMap, DispatchFromMap } from '@automattic/data-stores';
import { Reducer, AnyAction } from 'redux';

/**
 * Internal dependencies
 */
import reducer from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';
import { State } from './types';
export * from './types';

export const STORE_NAME = 'core/notices2';
// NOTE: This uses core/notices2, if this file is copied back upstream
// to Gutenberg this needs to be changed back to core/notices.
// @ts-expect-error - react-18-upgrade registerStore is deprecated - migrate to register()
export default registerStore< State >( STORE_NAME, {
	reducer: reducer as Reducer< State, AnyAction >,
	actions,
	selectors,
} );

declare module '@wordpress/data' {
	// TODO: convert action.js to TS
	function dispatch(
		key: typeof STORE_NAME
	): DispatchFromMap< typeof actions >;
	function select(
		key: typeof STORE_NAME
	): SelectFromMap< typeof selectors >;
}
