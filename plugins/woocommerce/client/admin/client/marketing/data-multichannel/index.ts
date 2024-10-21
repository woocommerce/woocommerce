/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
import { Reducer, AnyAction } from 'redux';

/**
 * Internal dependencies
 */
import { State } from './types';
import { STORE_KEY } from './constants';
import { reducer } from './reducer';
import controls from './controls';
import * as actions from './actions';
import * as selectors from './selectors';
import * as resolvers from './resolvers';

const store = createReduxStore( STORE_KEY, {
	reducer: reducer as Reducer< State, AnyAction >,
	actions,
	selectors,
	resolvers,
	controls,
} );

register( store );
