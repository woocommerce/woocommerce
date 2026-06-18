/**
 * External dependencies
 */
import { combineReducers, createReduxStore, register } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import reducer from './reducer';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import { STORE_NAME } from './store-name';

export { STORE_NAME };

export const store = createReduxStore( STORE_NAME, {
	reducer: combineReducers( { settings: reducer } ),
	actions,
	selectors,
	resolvers,
	controls,
} );

register( store );
