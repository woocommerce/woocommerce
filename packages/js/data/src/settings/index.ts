/**
 * External dependencies
 */

import { register, createReduxStore } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';
import { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer, { State } from './reducer';
import { SettingsState } from './types';
export * from './types';
export type { State };

const store = createReduxStore( STORE_NAME, {
	reducer: reducer as Reducer< SettingsState >,
	actions,
	controls,
	selectors,
	resolvers,
} );

register( store );

export const SETTINGS_STORE_NAME = STORE_NAME;
