/**
 * External dependencies
 */
import { createReduxStore, register, subscribe } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer from './reducers';
import { pushChanges } from './push-changes';

export const config = {
	reducer,
	selectors,
	actions,
	resolvers,
	__experimentalUseThunks: true,
};

export const store = createReduxStore( STORE_KEY, config );
register( store );
export type CheckoutStoreDescriptor = typeof store;

export type CheckoutDispatchFromMap = typeof actions;

subscribe( pushChanges, store );

export const CHECKOUT_STORE_KEY = STORE_KEY;
