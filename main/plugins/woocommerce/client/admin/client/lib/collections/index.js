/**
 * Returns an object with items grouped by the sent key.
 *
 * @param {Array}  array      array of objects.
 * @param {string} key        the object prop that will be used to group elements.
 * @param {string} defaultKey if the key is not found in the object, it will use this value.
 * @return {Object} Object that contains the grouped elements.
 */
export const groupListOfObjectsBy = (
	array,
	key,
	defaultKey = 'undefined'
) => {
	if ( array && Array.isArray( array ) && array.length ) {
		if ( ! key ) {
			return array;
		}
		return array.reduce( ( result, currentValue ) => {
			if ( ! currentValue[ key ] ) {
				currentValue[ key ] = defaultKey;
			}
			( result[ currentValue[ key ] ] =
				result[ currentValue[ key ] ] || [] ).push( currentValue );
			return result;
		}, {} );
	}
	return {};
};

/**
 * Returns a (shallow) copy of an object with all its props set to the specified value
 *
 * @param {*} obj   the Object to copy.
 * @param {*} value the value to set all props on the object to.
 */
export const setAllPropsToValue = ( obj, value ) => {
	return Object.entries( obj ).reduce( ( acc, [ key ] ) => {
		return {
			...acc,
			[ key ]: value,
		};
	}, {} );
};
