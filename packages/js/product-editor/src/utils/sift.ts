type SiftResult< T > = [ T[], T[] ];

/**
 * Similar to filter, but return two arrays separated by a partitioner function
 *
 * @param {Array}    arr         - Original array of values.
 * @param {Function} partitioner - Function to return truthy/falsy values to separate items in array.
 *
 * @return {Array} - Array of two arrays, first including truthy values, and second including falsy.
 */
export const sift = < T >(
	arr: Array< T >,
	partitioner: ( item: T ) => boolean
): SiftResult< T > =>
	arr.reduce< SiftResult< T > >(
		( all, item ) => {
			all[ !! partitioner( item ) ? 0 : 1 ].push( item );
			return all;
		},
		[ [], [] ]
	);
