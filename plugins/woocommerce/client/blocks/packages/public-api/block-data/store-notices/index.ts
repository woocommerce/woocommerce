/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';

/**
 * Internal dependencies
 */
import * as actions from './actions';
import * as selectors from './selectors';
import reducer from './reducers';

const STORE_KEY = 'wc/store/store-notices';
const config = {
	reducer,
	actions,
	selectors,
};
export const store = createReduxStore( STORE_KEY, config );
export type StoreNoticesStoreDescriptor = typeof store;
register( store );

export const STORE_NOTICES_STORE_KEY = STORE_KEY;
