/**
 * External dependencies
 */
import { controls } from '@wordpress/data-controls';
import { createReduxStore, register } from '@wordpress/data';
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import reducer, { State } from './reducer';
import * as resolvers from './resolvers';
import initDispatchers, { INTERNAL_CALL } from './dispatchers';
import {
	DispatchFromMap,
	SelectFromMap,
	WPDataActions,
	WPDataSelectors,
} from '../types';
import { PromiseifySelectors } from '../types/promiseify-selectors';

export { type State };

// Generic wrapper that applies deprecate() to all functions.
function wrapWithDeprecate< T extends Record< string, unknown > >( obj: T ): T {
	const wrapped = {} as T;
	for ( const key in obj ) {
		const value = obj[ key ];
		if ( typeof value === 'function' ) {
			wrapped[ key ] = function ( this: unknown, ...args: unknown[] ) {
				// Skip deprecation message for:
				// - onLoad (automatically called by initDispatchers)
				// - onHistoryChange when called internally with true flag
				const shouldSkipDeprecation =
					( key === 'onLoad' || key === 'onHistoryChange' ) &&
					args[ 0 ] === INTERNAL_CALL;

				if ( ! shouldSkipDeprecation ) {
					deprecated( 'Navigation store', {} );
				}
				return ( value as ( ...args: unknown[] ) => unknown ).apply(
					this,
					args
				);
			} as T[ Extract< keyof T, string > ];
		} else {
			wrapped[ key ] = value;
		}
	}
	return wrapped;
}

export const store = createReduxStore( STORE_NAME, {
	reducer,
	actions: wrapWithDeprecate( actions ),
	controls,
	selectors: wrapWithDeprecate( selectors ),
	resolvers,
} );

register( store );

initDispatchers();

export const NAVIGATION_STORE_NAME = STORE_NAME;

declare module '@wordpress/data' {
	function dispatch(
		key: typeof STORE_NAME
	): DispatchFromMap< typeof actions & WPDataActions >;
	function select(
		key: typeof STORE_NAME
	): SelectFromMap< typeof selectors > & WPDataSelectors;
	function resolveSelect(
		key: typeof STORE_NAME
	): PromiseifySelectors< SelectFromMap< typeof selectors > >;
}
