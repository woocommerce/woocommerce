import { CartItem } from '../../../types';

function isPrimitive(
	val: unknown
): val is string | number | boolean | null | undefined {
	return (
		val === null || ( typeof val !== 'object' && typeof val !== 'function' )
	);
}

function deepEqual( a: any, b: any ): boolean {
	// Quick version; replace with fast-deep-equal if needed
	return JSON.stringify( a ) === JSON.stringify( b );
}

export function updateCartItemsByKey(
	currentItems: CartItem[],
	newItems: CartItem[]
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
					isPrimitive( existing[ key ] ) &&
					existing[ key ] !== newItem[ key ]
				) {
					// @ts-ignore
					existing[ key ] = newItem[ key ];
				} else if ( ! deepEqual( existing[ key ], newItem[ key ] ) ) {
					// @ts-ignore
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
