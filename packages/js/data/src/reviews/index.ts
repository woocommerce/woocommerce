/**
 * External dependencies
 */

import { createReduxStore, register } from '@wordpress/data';
import { Reducer, AnyAction } from 'redux';
import { SelectFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import controls from '../controls';
import reducer, { State } from './reducer';

export * from './types';
export type { State };

const store = createReduxStore( STORE_NAME, {
	reducer: reducer as Reducer< State, AnyAction >,
	actions,
	controls,
	selectors,
	resolvers,
} );

register( store );

export const REVIEWS_STORE_NAME = STORE_NAME;

export type ReviewSelector = SelectFromMap< typeof selectors >;
