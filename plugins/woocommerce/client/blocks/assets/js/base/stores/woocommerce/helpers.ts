/**
 * Internal dependencies
 */
import { CartItem } from '../../../types';
import { OptimisticCartItem } from './cart';

function isPrimitive(
	val: unknown
): val is string | number | boolean | null | undefined {
	return (
		val === null || ( typeof val !== 'object' && typeof val !== 'function' )
	);
}

function deepEqual( a: unknown, b: unknown ): boolean {
	// Quick version; replace with fast-deep-equal if needed
	return JSON.stringify( a ) === JSON.stringify( b );
}

export function updateCartItemsByKey(
	currentItems: ( CartItem | OptimisticCartItem )[],
	newItems: ( CartItem | OptimisticCartItem )[]
) {
	const currentMap = new Map(
		currentItems.map( ( item ) => [ item.key, item ] )
	);

	for ( let i = 0; i < newItems.length; i++ ) {
		const newItem = newItems[ i ];
		const existing = currentMap.get( newItem.key );

		if ( existing ) {
			for ( const key of Object.keys(
				existing
			) as ( keyof CartItem )[] ) {
				if (
					// @ts-expect-error - TODO we need to improve the typing of cart items which is CartItem | OptimisticCartItem making it difficult to type this effectively.
					isPrimitive( existing[ key ] ) &&
					// @ts-expect-error - TODO we need to improve the typing of cart items which is CartItem | OptimisticCartItem making it difficult to type this effectively.
					existing[ key ] !== newItem[ key ]
				) {
					// @ts-expect-error - TODO we need to improve the typing of cart items which is CartItem | OptimisticCartItem making it difficult to type this effectively.
					existing[ key ] = newItem[ key ];
					// @ts-expect-error - TODO we need to improve the typing of cart items which is CartItem | OptimisticCartItem making it difficult to type this effectively.
				} else if ( ! deepEqual( existing[ key ], newItem[ key ] ) ) {
					// @ts-expect-error - TODO we need to improve the typing of cart items which is CartItem | OptimisticCartItem making it difficult to type this effectively.
					existing[ key ] = newItem[ key ];
				}
			}
		} else {
			currentItems.push( newItem );
		}
	}

	// Optionally prune removed items
	const newKeys = new Set( newItems.map( ( i ) => i.key ) );
	const filtered = currentItems.filter( ( i ) => newKeys.has( i.key ) );
	if ( filtered.length !== currentItems.length ) {
		currentItems.splice( 0, currentItems.length, ...filtered );
	}
}
