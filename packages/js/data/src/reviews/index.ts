/**
 * External dependencies
 */

import { registerStore } from '@wordpress/data';
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

registerStore< State >( STORE_NAME, {
	reducer: reducer as Reducer< State, AnyAction >,
	actions,
	controls,
	selectors,
	resolvers,
} );

export const REVIEWS_STORE_NAME = STORE_NAME;

export type ReviewSelector = SelectFromMap< typeof selectors >;
