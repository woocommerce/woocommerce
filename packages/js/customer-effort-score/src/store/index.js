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
import { QUEUE_OPTION_NAME, STORE_KEY } from './constants';

export { QUEUE_OPTION_NAME, STORE_KEY };

const store = createReduxStore( STORE_KEY, {
	actions,
	selectors,
	resolvers,
	controls,
	reducer,
} );

register( store );

export default store;
