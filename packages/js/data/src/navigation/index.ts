/**
 * External dependencies
 */
import { controls } from '@wordpress/data-controls';
import { SelectFromMap, DispatchFromMap } from '@automattic/data-stores';
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
import initDispatchers from './dispatchers';
import { WPDataActions, WPDataSelectors } from '../types';
import { PromiseifySelectors } from '../types/promiseify-selectors';

export { type State };

// Generic wrapper that applies deprecate() to all functions.
function wrapWithDeprecate< T extends Record< string, unknown > >( obj: T ): T {
	const wrapped = {} as T;
	for ( const key in obj ) {
		const value = obj[ key ];
		if ( typeof value === 'function' ) {
			wrapped[ key ] = function ( this: unknown, ...args: unknown[] ) {
				// onLoad action is automatically called when initDispatchers is called, skip deprecation message.
				if ( key !== 'onLoad' ) {
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
