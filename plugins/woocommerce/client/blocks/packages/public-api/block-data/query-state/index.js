/**
 * External dependencies
 */
import { register, createReduxStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import reducer from './reducers';

const config = {
	reducer,
	actions,
	selectors,
};
export const store = createReduxStore( STORE_KEY, config );

register( store );

export const QUERY_STATE_STORE_KEY = STORE_KEY;
