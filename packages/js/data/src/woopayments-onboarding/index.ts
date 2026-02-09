/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import reducer from './reducer';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
export * from './types';
import { STORE_KEY } from './constants';

export const WOOPAYMENTS_ONBOARDING_STORE_NAME = STORE_KEY;

export const store = createReduxStore( STORE_KEY, {
	reducer,
	actions,
	controls,
	selectors,
	resolvers,
} );

register( store );
