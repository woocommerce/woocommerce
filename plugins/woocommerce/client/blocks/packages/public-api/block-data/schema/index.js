/**
 * External dependencies
 */
import { register, createReduxStore } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer from './reducers';

const config = {
	reducer,
	actions,
	controls,
	selectors,
	resolvers,
};
export const store = createReduxStore( STORE_KEY, config );

register( store );

export const SCHEMA_STORE_KEY = STORE_KEY;
