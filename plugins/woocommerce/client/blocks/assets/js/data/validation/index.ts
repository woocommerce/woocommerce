/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';

/**
 * Internal dependencies
 */
import reducer from './reducers';
import { STORE_KEY } from './constants';
import * as actions from './actions';
import * as selectors from './selectors';

export const config = {
	reducer,
	selectors,
	actions,
};

export const store = createReduxStore( STORE_KEY, config );
register( store );
export type ValidationStoreDescriptor = typeof store;

export const VALIDATION_STORE_KEY = STORE_KEY;
