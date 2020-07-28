/**
 * External dependencies
 */
import { axisBottom as d3AxisBottom } from 'd3-axis';
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
 * Calculate the maximum number of ticks allowed in the x-axis based on the width and mode of the chart
 *
 * @param {number} width - calculated page width
 * @param {string} mode - item-comparison or time-comparison
 * @return {number} number of x-axis ticks based on width and chart mode
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
 * Filter out irrelevant dates so only the first date of each month is kept.
 *
 * @param {Array} dates - string dates.
 * @return {Array} Filtered dates.
 */
const getFirstDatePerMonth = ( dates ) => {
	return dates.filter(
		( date, i ) =>
			i === 0 ||
			moment( date ).toDate().getMonth() !==
				moment( dates[ i - 1 ] )
					.toDate()
					.getMonth()
	);
};

/**
 * Given an array of dates, returns true if the first and last one belong to the same day.
 *
 * @param {Array} dates - an array of dates
 * @return {boolean} whether the first and last date are different hours from the same date.
 */
const areDatesInTheSameDay = ( dates ) => {
	const firstDate = moment( dates[ 0 ] ).toDate();
	const lastDate = moment( dates[ dates.length - 1 ] ).toDate();
	return (
		firstDate.getDate() === lastDate.getDate() &&
		firstDate.getMonth() === lastDate.getMonth() &&
		firstDate.getFullYear() === lastDate.getFullYear()
	);
};

/**
 * Describes `smallestFactor`
 *
 * @param {number} inputNum - any double or integer
 * @return {number} smallest factor of num
 */
const getFactors = ( inputNum ) => {
	const numFactors = [];
	for ( let i = 1; i <= Math.floor( Math.sqrt( inputNum ) ); i++ ) {
		if ( inputNum % i === 0 ) {
			numFactors.push( i );
			// eslint-disable-next-line no-unused-expressions
			inputNum / i !== i && numFactors.push( inputNum / i );
		}
	}
	numFactors.sort( ( x, y ) => x - y ); // numeric sort

	return numFactors;
};

/**
 * Calculates the increment factor between ticks so there aren't more than maxTicks.
 *
 * @param {Array} uniqueDates - all the unique dates from the input data for the chart
 * @param {number} maxTicks - maximum number of ticks that can be displayed in the x-axis
 * @return {number} x-axis ticks increment factor
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

	return factors.find( ( f ) => uniqueDates.length / f < maxTicks );
};

/**
 * Get x-axis ticks given the unique dates and the increment factor.
 *
 * @param {Array} uniqueDates - all the unique dates from the input data for the chart
 * @param {number} incrementFactor - increment factor for the visible ticks.
 * @return {Array} Ticks for the x-axis.
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
 * Returns ticks for the x-axis.
 *
 * @param {Array} uniqueDates - all the unique dates from the input data for the chart
 * @param {number} width - calculated page width
 * @param {string} mode - item-comparison or time-comparison
 * @param {string} interval - string of the interval used in the graph (hour, day, week...)
 * @return {number} number of x-axis ticks based on width and chart mode
 */
export const getXTicks = ( uniqueDates, width, mode, interval ) => {
	const maxTicks = calculateMaxXTicks( width, mode );

	if (
		( uniqueDates.length >= dayTicksThreshold && interval === 'day' ) ||
		( uniqueDates.length >= weekTicksThreshold && interval === 'week' )
	) {
		uniqueDates = getFirstDatePerMonth( uniqueDates );
	}
	if (
		uniqueDates.length <= maxTicks ||
		( interval === 'hour' &&
			areDatesInTheSameDay( uniqueDates ) &&
			width > smallBreak )
	) {
		return uniqueDates;
	}

	const incrementFactor = calculateXTicksIncrementFactor(
		uniqueDates,
		maxTicks
	);

	return getXTicksFromIncrementFactor( uniqueDates, incrementFactor );
};

/**
 * Compares 2 strings and returns a list of words that are unique from s2
 *
 * @param {string} s1 - base string to compare against
 * @param {string} s2 - string to compare against the base string
 * @param {string|Object} splitChar - character or RegExp to use to deliminate words
 * @return {Array} of unique words that appear in s2 but not in s1, the base string
 */
export const compareStrings = (
	s1,
	s2,
	splitChar = new RegExp( [ ' |,' ], 'g' )
) => {
	const string1 = s1.split( splitChar );
	const string2 = s2.split( splitChar );
	const diff = new Array();
	const long = s1.length > s2.length ? string1 : string2;
	for ( let x = 0; x < long.length; x++ ) {
		// eslint-disable-next-line no-unused-expressions
		string1[ x ] !== string2[ x ] && diff.push( string2[ x ] );
	}
	return diff;
};

const removeDuplicateDates = ( d, i, ticks, formatter ) => {
	const monthDate = moment( d ).toDate();
	let prevMonth = i !== 0 ? ticks[ i - 1 ] : ticks[ i ];
	prevMonth =
		prevMonth instanceof Date ? prevMonth : moment( prevMonth ).toDate();
	return i === 0
		? formatter( monthDate )
		: compareStrings( formatter( prevMonth ), formatter( monthDate ) ).join(
				' '
		  );
};

export const drawXAxis = ( node, params, scales, formats ) => {
	const height = scales.yScale.range()[ 0 ];
	let ticks = getXTicks(
		params.uniqueDates,
		scales.xScale.range()[ 1 ],
		params.mode,
		params.interval
	);
	if ( params.chartType === 'line' ) {
		ticks = ticks.map( ( d ) => moment( d ).toDate() );
	}

	node.append( 'g' )
		.attr( 'class', 'axis' )
		.attr( 'aria-hidden', 'true' )
		.attr( 'transform', `translate(0, ${ height })` )
		.call(
			d3AxisBottom( scales.xScale )
				.tickValues( ticks )
				.tickFormat( ( d, i ) =>
					params.interval === 'hour'
						? formats.xFormat(
								d instanceof Date ? d : moment( d ).toDate()
						  )
						: removeDuplicateDates( d, i, ticks, formats.xFormat )
				)
		);

	node.append( 'g' )
		.attr( 'class', 'axis axis-month' )
		.attr( 'aria-hidden', 'true' )
		.attr( 'transform', `translate(0, ${ height + 14 })` )
		.call(
			d3AxisBottom( scales.xScale )
				.tickValues( ticks )
				.tickFormat( ( d, i ) =>
					removeDuplicateDates( d, i, ticks, formats.x2Format )
				)
		);

	node.append( 'g' )
		.attr( 'class', 'pipes' )
		.attr( 'transform', `translate(0, ${ height })` )
		.call(
			d3AxisBottom( scales.xScale )
				.tickValues( ticks )
				.tickSize( 5 )
				.tickFormat( '' )
		);
};
