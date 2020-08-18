/**
 * External dependencies
 */

import { select, registerStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import controls from '../controls';
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
	} );
}

export const EXPORT_STORE_NAME = STORE_NAME;
