/**
 * Does a depth-first search of a meta object to find the first instance of a component.
 *
 * @template T - The type of the component meta object
 */
export function findComponentMeta< T >(
	obj: Record< string, unknown >,
	visited = new Set< Record< string, unknown > >()
): T | undefined {
	if ( visited.has( obj ) ) {
		return undefined;
	}

	visited.add( obj );

	for ( const key in obj ) {
		if ( obj.hasOwnProperty( key ) ) {
			if ( key === 'component' ) {
				return obj as T;
			} else if (
				typeof obj[ key ] === 'object' &&
				obj[ key ] !== null
			) {
				const found = findComponentMeta< T >(
					obj[ key ] as Record< string, unknown >,
					visited
				);
				if ( found !== undefined ) {
					return found;
				}
			}
		}
	}

	return undefined;
}
