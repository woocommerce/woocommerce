/**
 * External dependencies
 */

import { createReduxStore, register } from '@wordpress/data';
import { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer, { OrdersState } from './reducer';
import controls from '../controls';

const store = createReduxStore( STORE_NAME, {
	reducer: reducer as Reducer< OrdersState >,
	actions,
	controls,
	selectors,
	resolvers,
} );
register( store );

export const ORDERS_STORE_NAME = STORE_NAME;
