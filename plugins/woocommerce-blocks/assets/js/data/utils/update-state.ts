/**
 * External dependencies
 */
import { klona } from 'klona/json';

/**
 * Utility for updating state and only cloning objects in the path that changed.
 */
export default function updateState< Type extends Record< string, unknown > >(
	// The state being updated
	state: Type,
	// The path being updated
	path: Array< keyof Type >,
	// The value to update for the path
	value: unknown
): Type {
	const newState = klona( state ) as Type;

	let current: Record< string, unknown > = newState;
	for ( let i = 0; i < path.length; i++ ) {
		const key = path[ i ] as string;

		if ( i === path.length - 1 ) {
			current[ key ] = value;
		} else {
			current[ key ] = current[ key ] || {};
		}

		current = current[ key ] as Record< string, unknown >;
	}

	return newState;
}
