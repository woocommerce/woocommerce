/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
import type { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import reducer from './reducer';
import * as selectors from './selectors';
import * as actions from './actions';
import { STORE_NAME } from './constants';
import type { ProductFieldState } from './types';

export const store = createReduxStore( STORE_NAME, {
	reducer: reducer as Reducer< ProductFieldState >,
	selectors,
	actions,
} );

register( store );
