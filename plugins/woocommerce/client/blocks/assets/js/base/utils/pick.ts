/**
 * Creates an object composed of the picked object properties.
 */
export const pick = < Type >( object: Type, keys: string[] ): Type => {
	return keys.reduce( ( obj, key ) => {
		if ( object && object.hasOwnProperty( key ) ) {
			obj[ key as keyof Type ] = object[ key as keyof Type ];
		}
		return obj;
	}, {} as Type );
};
