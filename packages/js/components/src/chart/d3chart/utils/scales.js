/**
 * External dependencies
 */
import {
	scaleBand as d3ScaleBand,
	scaleLinear as d3ScaleLinear,
	scaleTime as d3ScaleTime,
} from 'd3-scale';
import moment from 'moment';

/**
 * Describes getXScale
 *
 * @param {Array}   uniqueDates - from `getUniqueDates`
 * @param {number}  width       - calculated width of the charting space
 * @param {boolean} compact     - whether the chart must be compact (without padding
                                between days)
 * @return {Function} a D3 scale of the dates
 */
export const getXScale = ( uniqueDates, width, compact = false ) =>
	d3ScaleBand()
		.domain( uniqueDates )
		.range( [ 0, width ] )
		.paddingInner( compact ? 0 : 0.1 );

/**
 * Describes getXGroupScale
 *
 * @param {Array}    orderedKeys - from `getOrderedKeys`
 * @param {Function} xScale      - from `getXScale`
 * @param {boolean}  compact     - whether the chart must be compact (without padding
                                 between days)
 * @return {Function} a D3 scale for each category within the xScale range
 */
export const getXGroupScale = ( orderedKeys, xScale, compact = false ) =>
	d3ScaleBand()
		.domain(
			orderedKeys.filter( ( d ) => d.visible ).map( ( d ) => d.key )
		)
		.rangeRound( [ 0, xScale.bandwidth() ] )
		.padding( compact ? 0 : 0.07 );

/**
 * Describes getXLineScale
 *
 * @param {Array}  uniqueDates - from `getUniqueDates`
 * @param {number} width       - calculated width of the charting space
 * @return {Function} a D3 scaletime for each date
 */
export const getXLineScale = ( uniqueDates, width ) =>
	d3ScaleTime()
		.domain( [
			moment( uniqueDates[ 0 ], 'YYYY-MM-DD HH:mm' ).toDate(),
			moment(
				uniqueDates[ uniqueDates.length - 1 ],
				'YYYY-MM-DD HH:mm'
			).toDate(),
		] )
		.rangeRound( [ 0, width ] );

const getYValueLimits = ( data ) => {
	let maxYValue = Number.NEGATIVE_INFINITY;
	let minYValue = Number.POSITIVE_INFINITY;
	data.forEach( ( d ) => {
		for ( const [ key, item ] of Object.entries( d ) ) {
			if (
				key !== 'date' &&
				Number.isFinite( item.value ) &&
				item.value > maxYValue
			) {
				maxYValue = item.value;
			}
			if (
				key !== 'date' &&
				Number.isFinite( item.value ) &&
				item.value < minYValue
			) {
				minYValue = item.value;
			}
		}
	} );

	return { upper: maxYValue, lower: minYValue };
};

export const calculateStep = ( minValue, maxValue ) => {
	if ( ! Number.isFinite( minValue ) || ! Number.isFinite( maxValue ) ) {
		return 1;
	}

	if ( maxValue === 0 && minValue === 0 ) {
		return 1 / 3;
	}

	const maxAbsValue = Math.max( -minValue, maxValue );
	const maxLimit = ( 4 / 3 ) * maxAbsValue;
	const pow3Y =
		// eslint-disable-next-line no-bitwise
		Math.pow( 10, ( ( Math.log( maxLimit ) * Math.LOG10E + 1 ) | 0 ) - 2 ) *
		3;
	const step = ( Math.ceil( maxLimit / pow3Y ) * pow3Y ) / 3;

	if ( maxValue < 1 && minValue > -1 ) {
		return Math.round( step * 4 ) / 4;
	}

	return Math.ceil( step );
};

/**
 * Returns the lower and upper limits of the Y scale and the calculated step to use in the axis, rounding
 * them to the nearest thousand, ten-thousand, million etc. In case it is a decimal number, ceils it.
 *
 * @param {Array} data - The chart component's `data` prop.
 * @return {Object} Object containing the `lower` and `upper` limits and a `step` value.
 */
export const getYScaleLimits = ( data ) => {
	const { lower: minValue, upper: maxValue } = getYValueLimits( data );
	const step = calculateStep( minValue, maxValue );
	const limits = { lower: 0, upper: 0, step };

	if ( Number.isFinite( minValue ) || minValue < 0 ) {
		limits.lower = Math.floor( minValue / step ) * step;
		if ( limits.lower === minValue && minValue !== 0 ) {
			limits.lower -= step;
		}
	}
	if ( Number.isFinite( maxValue ) || maxValue > 0 ) {
		limits.upper = Math.ceil( maxValue / step ) * step;
		if ( limits.upper === maxValue && maxValue !== 0 ) {
			limits.upper += step;
		}
	}

	return limits;
};

/**
 * Describes getYScale
 *
 * @param {number} height - calculated height of the charting space
 * @param {number} yMin   - minimum y value
 * @param {number} yMax   - maximum y value
 * @return {Function} the D3 linear scale from 0 to the value from `getYMax`
 */
export const getYScale = ( height, yMin, yMax ) =>
	d3ScaleLinear()
		.domain( [
			Math.min( yMin, 0 ),
			yMax === 0 && yMin === 0 ? 1 : Math.max( yMax, 0 ),
		] )
		.rangeRound( [ height, 0 ] );
