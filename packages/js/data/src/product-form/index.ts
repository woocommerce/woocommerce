/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';
import { SelectFromMap, DispatchFromMap } from '@automattic/data-stores';
import { Reducer, AnyAction } from 'redux';
/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer, { State } from './reducer';
import { WPDataSelectors } from '../types';
import { PromiseifySelectors } from '../types/promiseify-selectors';
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

export const EXPERIMENTAL_PRODUCT_FORM_STORE_NAME = STORE_NAME;
