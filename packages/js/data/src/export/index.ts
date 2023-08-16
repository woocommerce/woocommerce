/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
import { Reducer, AnyAction } from 'redux';
/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import reducer, { State } from './reducer';
import controls from '../controls';
export * from './types';
export type { State };

const store = createReduxStore( STORE_NAME, {
	reducer: reducer as Reducer< State, AnyAction >,
	actions,
	controls,
	selectors,
} );

register( store );

export const EXPORT_STORE_NAME = STORE_NAME;
