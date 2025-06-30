/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
import { Reducer, AnyAction } from 'redux';
import { controls } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer, { State } from './reducer';

export * from './types';
export type { State };

export const store = createReduxStore( STORE_NAME, {
	reducer: reducer as Reducer< State, AnyAction >,
	actions,
	controls,
	selectors,
	resolvers,
} );

register( store );

export const NOTES_STORE_NAME = STORE_NAME;
