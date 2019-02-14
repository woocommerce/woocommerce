/** @format */

/**
 * External dependencies
 */
import { axisBottom as d3AxisBottom, axisLeft as d3AxisLeft } from 'd3-axis';
import { smallBreak, wideBreak } from './breakpoints';
import moment from 'moment';

const dayTicksThreshold = 63;
const weekTicksThreshold = 9;
const mediumBreak = 1130;
const smallPoints = 7;
const mediumPoints = 12;
const largePoints = 16;
const mostPoints = 31;

/**
* Describes `smallestFactor`
* @param {number} inputNum - any double or integer
* @returns {integer} smallest factor of num
*/
const getFactors = inputNum => {
	const numFactors = [];
	for ( let i = 1; i <= Math.floor( Math.sqrt( inputNum ) ); i += 1 ) {
		if ( inputNum % i === 0 ) {
			numFactors.push( i );
			inputNum / i !== i && numFactors.push( inputNum / i );
		}
	}
	numFactors.sort( ( x, y ) => x - y ); // numeric sort

	return numFactors;
};

/**
 * Calculate the maximum number of ticks allowed in the x-axis based on the width and mode of the chart
 * @param {integer} width - calculated page width
 * @param {string} mode - item-comparison or time-comparison
 * @returns {integer} number of x-axis ticks based on width and chart mode
 */
const calculateMaxXTicks = ( width, mode ) => {
	if ( width < smallBreak ) {
		return smallPoints;
	} else if ( width >= smallBreak && width <= mediumBreak ) {
		return mediumPoints;
	} else if ( width > mediumBreak && width <= wideBreak ) {
		if ( mode === 'time-comparison' ) {
			return largePoints;
		} else if ( mode === 'item-comparison' ) {
			return mediumPoints;
		}
	} else if ( width > wideBreak ) {
		if ( mode === 'time-comparison' ) {
			return mostPoints;
		} else if ( mode === 'item-comparison' ) {
			return largePoints;
		}
	}

	return largePoints;
};

/**
 * Get x-axis ticks given the unique dates and the increment factor.
 * @param {array} uniqueDates - all the unique dates from the input data for the chart
 * @param {integer} incrementFactor - increment factor for the visible ticks.
 * @returns {array} Ticks for the x-axis.
 */
const getXTicksFromIncrementFactor = ( uniqueDates, incrementFactor ) => {
	const ticks = [];

	for ( let idx = 0; idx < uniqueDates.length; idx = idx + incrementFactor ) {
		ticks.push( uniqueDates[ idx ] );
	}

	// If the first date is missing from the ticks array, add it back in.
	if ( ticks[ 0 ] !== uniqueDates[ 0 ] ) {
		ticks.unshift( uniqueDates[ 0 ] );
	}

	return ticks;
};

/**
 * Calculates the increment factor between ticks so there aren't more than maxTicks.
 * @param {array} uniqueDates - all the unique dates from the input data for the chart
 * @param {integer} maxTicks - maximum number of ticks that can be displayed in the x-axis
 * @returns {integer} x-axis ticks increment factor
 */
const calculateXTicksIncrementFactor = ( uniqueDates, maxTicks ) => {
	let factors = [];
	let i = 1;
	// First we get all the factors of the length of the uniqueDates array
	// if the number is a prime number or near prime (with 3 factors) then we
	// step down by 1 integer and try again.
	while ( factors.length <= 3 ) {
		factors = getFactors( uniqueDates.length - i );
		i += 1;
	}

	return factors.find( f => uniqueDates.length / f < maxTicks );
};

/**
 * Given an array of dates, returns true if the first and last one belong to the same day.
 * @param {array} dates - an array of dates
 * @returns {boolean} whether the first and last date are different hours from the same date.
 */
const areDatesInTheSameDay = dates => {
	const firstDate = moment( dates [ 0 ] ).toDate();
	const lastDate = moment( dates [ dates.length - 1 ] ).toDate();
	return (
		firstDate.getDate() === lastDate.getDate() &&
		firstDate.getMonth() === lastDate.getMonth() &&
		firstDate.getFullYear() === lastDate.getFullYear()
	);
};

/**
* Filter out irrelevant dates so only the first date of each month is kept.
* @param {array} dates - string dates.
* @returns {array} Filtered dates.
*/
const getFirstDatePerMonth = dates => {
	return dates.filter(
	( date, i ) => i === 0 || moment( date ).toDate().getMonth() !== moment( dates[ i - 1 ] ).toDate().getMonth()
	);
};

/**
 * Returns ticks for the x-axis.
 * @param {array} uniqueDates - all the unique dates from the input data for the chart
 * @param {integer} width - calculated page width
 * @param {string} mode - item-comparison or time-comparison
 * @param {string} interval - string of the interval used in the graph (hour, day, week...)
 * @returns {integer} number of x-axis ticks based on width and chart mode
 */
export const getXTicks = ( uniqueDates, width, mode, interval ) => {
	const maxTicks = calculateMaxXTicks( width, mode );

	if (
		( uniqueDates.length >= dayTicksThreshold && interval === 'day' ) ||
		( uniqueDates.length >= weekTicksThreshold && interval === 'week' )
	) {
		uniqueDates = getFirstDatePerMonth( uniqueDates );
	}
	if ( uniqueDates.length <= maxTicks ||
			( interval === 'hour' && areDatesInTheSameDay( uniqueDates ) && width > smallBreak ) ) {
		return uniqueDates;
	}

	const incrementFactor = calculateXTicksIncrementFactor( uniqueDates, maxTicks );

	return getXTicksFromIncrementFactor( uniqueDates, incrementFactor );
};

/**
* Compares 2 strings and returns a list of words that are unique from s2
* @param {string} s1 - base string to compare against
* @param {string} s2 - string to compare against the base string
* @param {string|Object} splitChar - character or RegExp to use to deliminate words
* @returns {array} of unique words that appear in s2 but not in s1, the base string
*/
export const compareStrings = ( s1, s2, splitChar = new RegExp( [ ' |,' ], 'g' ) ) => {
	const string1 = s1.split( splitChar );
	const string2 = s2.split( splitChar );
	const diff = new Array();
	const long = s1.length > s2.length ? string1 : string2;
	for ( let x = 0; x < long.length; x++ ) {
		string1[ x ] !== string2[ x ] && diff.push( string2[ x ] );
	}
	return diff;
};

export const getYGrids = ( yMax ) => {
	const yGrids = [];

	// If all values are 0, yMax can become NaN.
	if ( isNaN( yMax ) ) {
		return null;
	}

	for ( let i = 0; i < 4; i++ ) {
		const value = yMax > 1 ? Math.round( i / 3 * yMax ) : i / 3 * yMax;
		if ( yGrids[ yGrids.length - 1 ] !== value ) {
			yGrids.push( value );
		}
	}

	return yGrids;
};

export const drawAxis = ( node, params, xOffset ) => {
	const xScale = params.chartType === 'line' ? params.xLineScale : params.xScale;
	const removeDuplicateDates = ( d, i, ticks, formatter ) => {
		const monthDate = moment( d ).toDate();
		let prevMonth = i !== 0 ? ticks[ i - 1 ] : ticks[ i ];
		prevMonth = prevMonth instanceof Date ? prevMonth : moment( prevMonth ).toDate();
		return i === 0
			? formatter( monthDate )
			: compareStrings( formatter( prevMonth ), formatter( monthDate ) ).join( ' ' );
	};

	const yGrids = getYGrids( params.yMax === 0 ? 1 : params.yMax );

	const ticks = params.xTicks.map( d => ( params.chartType === 'line' ? moment( d ).toDate() : d ) );

	node
		.append( 'g' )
		.attr( 'class', 'axis' )
		.attr( 'aria-hidden', 'true' )
		.attr( 'transform', `translate(${ xOffset }, ${ params.height })` )
		.call(
			d3AxisBottom( xScale )
				.tickValues( ticks )
				.tickFormat( ( d, i ) => params.interval === 'hour'
					? params.xFormat( d instanceof Date ? d : moment( d ).toDate() )
					: removeDuplicateDates( d, i, ticks, params.xFormat ) )
		);

	node
		.append( 'g' )
		.attr( 'class', 'axis axis-month' )
		.attr( 'aria-hidden', 'true' )
		.attr( 'transform', `translate(${ xOffset }, ${ params.height + 20 })` )
		.call(
			d3AxisBottom( xScale )
				.tickValues( ticks )
				.tickFormat( ( d, i ) => removeDuplicateDates( d, i, ticks, params.x2Format ) )
		)
		.call( g => g.select( '.domain' ).remove() );

	node
		.append( 'g' )
		.attr( 'class', 'pipes' )
		.attr( 'transform', `translate(${ xOffset }, ${ params.height })` )
		.call(
			d3AxisBottom( xScale )
				.tickValues( ticks )
				.tickSize( 5 )
				.tickFormat( '' )
		);

	if ( yGrids ) {
		node
			.append( 'g' )
			.attr( 'class', 'grid' )
			.attr( 'transform', `translate(-${ params.margin.left }, 0)` )
			.call(
				d3AxisLeft( params.yScale )
					.tickValues( yGrids )
					.tickSize( -params.width - params.margin.left - params.margin.right )
					.tickFormat( '' )
			)
			.call( g => g.select( '.domain' ).remove() );

		node
			.append( 'g' )
			.attr( 'class', 'axis y-axis' )
			.attr( 'aria-hidden', 'true' )
			.attr( 'transform', 'translate(-50, 0)' )
			.attr( 'text-anchor', 'start' )
			.call(
				d3AxisLeft( params.yTickOffset )
					.tickValues( params.yMax === 0 ? [ yGrids[ 0 ] ] : yGrids )
					.tickFormat( d => params.yFormat( d !== 0 ? d : 0 ) )
			);
	}

	node.selectAll( '.domain' ).remove();
	node
		.selectAll( '.axis' )
		.selectAll( '.tick' )
		.select( 'line' )
		.remove();
};
