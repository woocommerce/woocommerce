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

export const config = {
	reducer,
	selectors,
	actions,
	__experimentalUseThunks: true,
};

registerStore( STORE_KEY, config );

export const CHECKOUT_STORE_KEY = STORE_KEY;
