/**
 * Calculate the mean value of an array of numbers.
 *
 * @param {Array} numbers Array of numbers
 * @return {number} Mean value
 */
export const calculateMean = ( numbers: Array< number > ) => {
	if ( numbers.length === 0 ) {
		return 0;
	}

	const sum = numbers.reduce( function ( a, b ) {
		return a + b;
	}, 0 );

	return sum / numbers.length;
};

/**
 * Calculate the median value of an array of numbers.
 *
 * @param {Array} numbers Array of numbers
 * @return {number} Median value
 */
export const calculateMedian = ( numbers: Array< number > ) => {
	if ( numbers.length === 0 ) {
		return 0;
	}

	// Sort the numbers in ascending order
	numbers.sort( function ( a, b ) {
		return a - b;
	} );

	const middleIndex = Math.floor( numbers.length / 2 );

	if ( numbers.length % 2 === 0 ) {
		// If the array length is even, return the average of the two middle values
		return ( numbers[ middleIndex - 1 ] + numbers[ middleIndex ] ) / 2;
	}
	// If the array length is odd, return the middle value
	return numbers[ middleIndex ];
};

/**
 * Get the 90th percentile value of an array of numbers.
 *
 * @param {Array} numbers Array of numbers
 * @return {number} 90th percentile value
 */
export const get90thPercentile = ( numbers: Array< number > ) => {
	// Sorting the numbers in ascending order
	const sortedNumbers = numbers.sort( ( a, b ) => a - b );

	// Calculating the index for the 90th percentile
	const index = Math.ceil( sortedNumbers.length * 0.9 ) - 1;

	// Returning the 90th percentile value
	return sortedNumbers[ index ];
};
