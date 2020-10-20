/**
 * External dependencies
 */
import { registerStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import reducer from './reducers';

registerStore( STORE_KEY, {
	reducer,
	actions,
	selectors,
} );

export const QUERY_STATE_STORE_KEY = STORE_KEY;
