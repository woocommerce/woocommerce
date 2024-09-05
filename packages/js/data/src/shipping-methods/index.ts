/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import * as actions from './actions';
import * as resolvers from './resolvers';
import * as selectors from './selectors';
import reducer from './reducer';
import { STORE_KEY } from './constants';
export const SHIPPING_METHODS_STORE_NAME = STORE_KEY;

export const store = createReduxStore( SHIPPING_METHODS_STORE_NAME, {
	reducer,
	selectors,
	resolvers,
	controls,
	actions,
} );

register( store );
