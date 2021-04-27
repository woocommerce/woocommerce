/**
 * External dependencies
 */
import { registerStore } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import * as actions from './actions';
import * as selectors from './selectors';
import reducer from './reducer';
import { STORE_KEY } from './constants';

export default registerStore( STORE_KEY, {
	actions,
	selectors,
	controls,
	reducer,
} );
