/**
 * External dependencies
 */
import { controls } from '@wordpress/data-controls';
import { registerStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import reducer from './reducer';

registerStore( STORE_NAME, {
	reducer,
	actions,
	controls,
	selectors,
} );

export const NAVIGATION_STORE_NAME = STORE_NAME;
