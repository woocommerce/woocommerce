/**
 * External dependencies
 */

import { registerStore } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';
import { SelectFromMap, DispatchFromMap } from '@automattic/data-stores';
import { Reducer, AnyAction } from 'redux';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer from './reducer';
import { WPDataSelectors } from '../types';

registerStore( STORE_NAME, {
	reducer: reducer as Reducer< ReturnType< Reducer >, AnyAction >,
	actions,
	controls,
	selectors,
	resolvers,
} );

export const ONBOARDING_STORE_NAME = STORE_NAME;

export type OnboardingSelector = SelectFromMap< typeof selectors >;

declare module '@wordpress/data' {
	// TODO: convert action.js to TS
	function dispatch( key: typeof STORE_NAME ): DispatchFromMap< AnyAction >;
	function select(
		key: typeof STORE_NAME
	): SelectFromMap< typeof selectors > & WPDataSelectors;
}
