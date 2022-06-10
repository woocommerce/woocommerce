/**
 * External dependencies
 */
import { registerStore } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';
import { SelectFromMap, DispatchFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import * as actions from './actions';
import * as resolvers from './resolvers';
import * as selectors from './selectors';
import reducer from './reducer';
import { STORE_KEY } from './constants';
import { WPDataActions } from '../types';

export const PAYMENT_GATEWAYS_STORE_NAME = STORE_KEY;

registerStore( STORE_KEY, {
	actions,
	selectors,
	resolvers,
	controls,
	reducer,
} );

declare module '@wordpress/data' {
	// TODO: convert action.js to TS
	function dispatch(
		key: typeof STORE_KEY
	): DispatchFromMap< typeof actions >;
	function select(
		key: typeof STORE_KEY
	): SelectFromMap< typeof selectors > & WPDataActions;
}
