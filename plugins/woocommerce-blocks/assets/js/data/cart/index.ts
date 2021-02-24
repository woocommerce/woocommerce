/**
 * External dependencies
 */
import { registerStore } from '@wordpress/data';
import { controls as dataControls } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer from './reducers';
import { controls } from '../shared-controls';

registerStore( STORE_KEY, {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore -- Can't figure out how to resolve this now
	reducer,
	actions,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore -- not sure how to resolve the type issues here.
	controls: { ...dataControls, ...controls },
	selectors,
	resolvers,
} );

export const CART_STORE_KEY = STORE_KEY;
