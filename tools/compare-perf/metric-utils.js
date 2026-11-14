/**
 * Computes the median number from an array of numbers.
 *
 * @param {number[]} array
 *
 * @return {number|undefined} Median value or undefined if array empty.
 */
function median( array ) {
	if ( ! array || ! array.length ) return undefined;

	const numbers = [ ...array ].sort( ( a, b ) => a - b );
	const middleIndex = Math.floor( numbers.length / 2 );

	if ( numbers.length % 2 === 0 ) {
		return ( numbers[ middleIndex - 1 ] + numbers[ middleIndex ] ) / 2;
	}
	return numbers[ middleIndex ];
}

function getMetricUnit( metric ) {
	if ( metric.endsWith( 'Size' ) ) {
		return 'KB';
	}

	if ( metric.endsWith( 'Count' ) ) {
		return 'count';
	}

	return 'ms';
}

function formatMetricValue( metric, value ) {
	return `${ value } ${ getMetricUnit( metric ) }`;
}

module.exports = {
	median,
	getMetricUnit,
	formatMetricValue,
};
