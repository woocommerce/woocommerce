/**
 * External dependencies
 */
import { registerStore } from '@wordpress/data';
import { controls as dataControls } from '@wordpress/data-controls';
import type { SelectFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer, { State } from './reducers';
import { controls } from '../shared-controls';
import { DispatchFromMap } from '../../mapped-types';

registerStore< State >( STORE_KEY, {
	reducer,
	actions,
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	controls: { ...dataControls, ...controls } as any,
	selectors,
	resolvers,
} );

export const CART_STORE_KEY = STORE_KEY;

declare module '@wordpress/data' {
	function dispatch(
		key: typeof CART_STORE_KEY
	): DispatchFromMap< typeof actions >;
	function select(
		key: typeof CART_STORE_KEY
	): SelectFromMap< typeof selectors >;
}
