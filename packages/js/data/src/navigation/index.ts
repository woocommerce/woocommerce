/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';
import { Reducer, AnyAction } from 'redux';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import reducer, { State } from './reducer';
import * as resolvers from './resolvers';
import initDispatchers from './dispatchers';

const store = createReduxStore( STORE_NAME, {
	reducer: reducer as Reducer< State, AnyAction >,
	actions,
	controls,
	resolvers,
	selectors,
} );
register( store );

initDispatchers();
export const NAVIGATION_STORE_NAME = STORE_NAME;
