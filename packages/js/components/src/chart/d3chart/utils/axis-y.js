/**
 * External dependencies
 */
import { axisLeft as d3AxisLeft } from 'd3-axis';

const calculateYGridValues = ( numberOfTicks, limit, roundValues ) => {
	const grids = [];

	for ( let i = 0; i < numberOfTicks; i++ ) {
		const val = ( ( i + 1 ) / numberOfTicks ) * limit;
		const rVal = roundValues ? Math.round( val ) : val;
		if ( grids[ grids.length - 1 ] !== rVal ) {
			grids.push( rVal );
		}
	}

	return grids;
};

const getNegativeYGrids = ( yMin, step ) => {
	if ( yMin >= 0 ) {
		return [];
	}

	const numberOfTicks = Math.ceil( -yMin / step );
	return calculateYGridValues( numberOfTicks, yMin, yMin < -1 );
};

const getPositiveYGrids = ( yMax, step ) => {
	if ( yMax <= 0 ) {
		return [];
	}

	const numberOfTicks = Math.ceil( yMax / step );
	return calculateYGridValues( numberOfTicks, yMax, yMax > 1 );
};

export const getYGrids = ( yMin, yMax, step ) => {
	return [
		0,
		...getNegativeYGrids( yMin, step ),
		...getPositiveYGrids( yMax, step ),
	];
};

export const drawYAxis = ( node, scales, formats, margin, isRTL ) => {
	const yGrids = getYGrids(
		scales.yScale.domain()[ 0 ],
		scales.yScale.domain()[ 1 ],
		scales.step
	);
	const width = scales.xScale.range()[ 1 ];
	const xPosition = isRTL
		? width + margin.left + margin.right / 2 - 15
		: -margin.left / 2 - 15;

	const withPositiveValuesClass =
		scales.yMin >= 0 || scales.yMax > 0 ? ' with-positive-ticks' : '';
	node.append( 'g' )
		.attr( 'class', 'grid' + withPositiveValuesClass )
		.attr( 'transform', `translate(-${ margin.left }, 0)` )
		.call(
			d3AxisLeft( scales.yScale )
				.tickValues( yGrids )
				.tickSize( -width - margin.left - margin.right )
				.tickFormat( '' )
		);

	node.append( 'g' )
		.attr( 'class', 'axis y-axis' )
		.attr( 'aria-hidden', 'true' )
		.attr( 'transform', 'translate(' + xPosition + ', 12)' )
		.attr( 'text-anchor', 'start' )
		.call(
			d3AxisLeft( scales.yScale )
				.tickValues(
					scales.yMax === 0 && scales.yMin === 0
						? [ yGrids[ 0 ] ]
						: yGrids
				)
				.tickFormat( ( d ) => {
					if ( d > -1 && d < 1 && formats.yBelow1Format ) {
						return formats.yBelow1Format( d );
					}
					return formats.yFormat( d );
				} )
		);
};
