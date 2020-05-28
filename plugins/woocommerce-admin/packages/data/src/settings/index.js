/**
 * External dependencies
 */

import { registerStore, select } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer from './reducer';

const storeSelectors = select( STORE_NAME );

// @todo This is used to prevent double registration of the store due to webpack chunks.
// The `storeSelectors` condition can be removed once this is fixed.
if ( ! storeSelectors ) {
	registerStore( STORE_NAME, {
		reducer,
		actions,
		controls,
		selectors,
		resolvers,
	} );
}

export const SETTINGS_STORE_NAME = STORE_NAME;
