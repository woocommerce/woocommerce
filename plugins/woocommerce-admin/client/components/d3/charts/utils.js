/** @format */

/**
 * External dependencies
 */

import { max as d3Max, range as d3Range } from 'd3-array';
import { axisBottom as d3AxisBottom, axisLeft as d3AxisLeft } from 'd3-axis';
import { format as d3Format } from 'd3-format';
import {
	line as d3Line,
	scaleBand as d3ScaleBand,
	scaleLinear as d3ScaleLinear,
	scaleOrdinal as d3ScaleOrdinal,
	scaleTime as d3ScaleTime,
} from 'd3-scale';
import { interpolateViridis as d3InterpolateViridis } from 'd3-scale-chromatic';
import { timeFormat as d3TimeFormat, utcParse as d3UTCParse } from 'd3-time-format';

export const parseDate = d3UTCParse( '%Y-%m-%d' );

export const getUniqueKeys = data => {
	return [
		...new Set(
			data.reduce( ( accum, curr ) => {
				Object.keys( curr ).forEach( key => key !== 'date' && accum.push( key ) );
				return accum;
			}, [] )
		),
	];
};

export const getOrderedKeys = data =>
	getUniqueKeys( data )
		.map( key => ( {
			key,
			total: data.reduce( ( a, c ) => a + c[ key ], 0 ),
		} ) )
		.sort( ( a, b ) => b.total - a.total )
		.map( d => d.key );

export const getLineData = data =>
	getOrderedKeys( data ).map( key => ( {
		key,
		values: data.map( d => ( {
			date: d.date,
			value: d[ key ],
		} ) ),
	} ) );

export const getUniqueDates = data => {
	return [
		...new Set(
			getLineData( data ).reduce( ( accum, { values } ) => {
				values.forEach( ( { date } ) => accum.push( date ) );
				return accum;
			}, [] )
		),
	].sort( ( a, b ) => parseDate( a ) - parseDate( b ) );
};

export const getXScale = ( data, { width } ) =>
	d3ScaleBand()
		.domain( getUniqueDates( data ) )
		.rangeRound( [ 0, width ] )
		.paddingInner( 0.1 );

export const getXGroupScale = ( data, { width } ) =>
	d3ScaleBand()
		.domain( getOrderedKeys( data ) )
		.rangeRound( [ 0, getXScale( data, { width } ).bandwidth() ] )
		.padding( 0.07 );

export const getXLineScale = ( data, { width } ) => {
	const uniqueDates = getUniqueDates( data );
	return d3ScaleTime()
		.domain( [ new Date( uniqueDates[ 0 ] ), new Date( uniqueDates[ uniqueDates.length - 1 ] ) ] )
		.rangeRound( [ 0, width ] );
};

export const getYMax = data =>
	Math.round(
		4 / 3 * d3Max( getLineData( data ), d => d3Max( d.values.map( date => date.value ) ) )
	);

export const getYScale = ( data, { height } ) =>
	d3ScaleLinear()
		.domain( [ 0, getYMax( data ) ] )
		.rangeRound( [ height, 0 ] );

export const getYTickOffset = ( data, { height, scale } ) =>
	d3ScaleLinear()
		.domain( [ 0, getYMax( data ) ] )
		.rangeRound( [ height + scale * 12, scale * 12 ] );

export const getColorScale = data =>
	d3ScaleOrdinal().range( d3Range( 0, 1.1, 100 / ( getOrderedKeys( data ).length - 1 ) / 100 ) );

export const getLine = ( data, params ) => {
	const scaleXLine = getXLineScale( data, params ),
		yScale = getYScale( data, params );
	return d3Line()
		.x( d => scaleXLine( new Date( d.date ) ) )
		.y( d => yScale( d.value ) );
};

export const drawAxis = ( node, data, params ) => {
	const scaleX = getXScale( data, params );
	const uniqueDates = getUniqueDates( data );

	const yGrids = [];
	for ( let i = 0; i < 4; i++ ) {
		yGrids.push( i / 3 * getYMax( data ) );
	}
	node
		.append( 'g' )
		.attr( 'class', 'axis' )
		.attr( 'transform', `translate(0,${ params.height })` )
		.call(
			d3AxisBottom( scaleX )
				.tickValues(
					uniqueDates.map(
						d => ( scaleX === getXLineScale( uniqueDates, params.width ) ? new Date( d ) : d )
					)
				)
				.tickFormat( d => d3TimeFormat( '%d' )( d instanceof Date ? d : new Date( d ) ) )
		);

	node
		.append( 'g' )
		.attr( 'class', 'grid' )
		.attr( 'transform', `translate(-${ params.margin.left },0)` )
		.call(
			d3AxisLeft( getYScale( data, params ) )
				.tickValues( yGrids )
				.tickSize( -params.width - params.margin.left )
				.tickFormat( '' )
		)
		.call( g => g.select( '.domain' ).remove() );

	node
		.append( 'g' )
		.attr( 'class', 'axis y-axis' )
		.call(
			d3AxisLeft( getYTickOffset( data, params ) )
				.tickValues( yGrids )
				.tickFormat( d => ( d !== 0 ? d3Format( '.3s' )( d ) : 0 ) )
		);

	node
		.selectAll( '.y-axis .tick text' )
		.style( 'font-size', `${ Math.round( params.scale * 10 ) }px` );

	node.selectAll( '.domain' ).remove();
	node
		.selectAll( '.axis' )
		.selectAll( '.tick' )
		.select( 'line' )
		.remove();
};

export const drawLines = ( node, data, params ) => {
	const uniqueDates = getUniqueDates( data );
	const line = getLine( data, params );
	const xLineScale = getXLineScale( data, params );
	const dateSpaces = uniqueDates.map( ( d, i ) => {
		const xNow = xLineScale( new Date( d ) );
		const xPrev =
			i >= 1
				? xLineScale( new Date( uniqueDates[ i - 1 ] ) )
				: xLineScale( new Date( uniqueDates[ 0 ] ) );
		const xNext =
			i < uniqueDates.length - 1
				? xLineScale( new Date( uniqueDates[ i + 1 ] ) )
				: xLineScale( new Date( uniqueDates[ uniqueDates.length - 1 ] ) );
		let xWidth = i === 0 ? xNext - xNow : xNow - xPrev;
		const xStart = i === 0 ? 0 : xNow - xWidth / 2;
		xWidth = i === 0 || i === uniqueDates.length - 1 ? xWidth / 2 : xWidth;
		return {
			date: d,
			start: uniqueDates.length > 1 ? xStart : 0,
			width: uniqueDates.length > 1 ? xWidth : params.width,
		};
	} );

	const g = node.append( 'g' );

	const focus = g
		.selectAll( '.focus-space' )
		.data( dateSpaces )
		.enter()
		.append( 'g' )
		.attr( 'class', 'focus-space' );

	focus
		.append( 'line' )
		.attr( 'class', 'focus-grid' )
		.style( 'stroke', 'lightgray' )
		.style( 'stroke-width', 1 )
		.attr( 'x1', d => xLineScale( new Date( d.date ) ) )
		.attr( 'y1', 0 )
		.attr( 'x2', d => xLineScale( new Date( d.date ) ) )
		.attr( 'y2', params.height )
		.attr( 'opacity', '0' );

	focus
		.append( 'rect' )
		.attr( 'class', 'focus-g' )
		.attr( 'x', d => d.start )
		.attr( 'y', 0 )
		.attr( 'width', d => d.width )
		.attr( 'height', params.height )
		.attr( 'opacity', 0 );
	// .on( 'mouseover', handleMouseOverLineChart )
	// .on( 'mouseout', handleMouseOutLineChart );

	const series = g
		.selectAll( '.line-g' )
		.data( getLineData( data ) )
		.enter()
		.append( 'g' )
		.attr( 'class', 'line-g' );

	series
		.append( 'path' )
		.attr( 'fill', 'none' )
		.attr( 'stroke-width', 3 )
		.attr( 'stroke-linejoin', 'round' )
		.attr( 'stroke-linecap', 'round' )
		.attr( 'stroke', ( d, i ) => d3InterpolateViridis( getColorScale( i ) ) )
		.attr( 'd', d => line( d.values ) );

	series
		.selectAll( 'circle' )
		.data( ( d, i ) => d.values.map( row => ( { ...row, i } ) ) )
		.enter()
		.append( 'circle' )
		.attr( 'r', 3.5 )
		.attr( 'fill', d => d3InterpolateViridis( getColorScale( d.i ) ) )
		.attr( 'stroke', '#fff' )
		.attr( 'cx', d => xLineScale( new Date( d.date ) ) )
		.attr( 'cy', d => getYScale( d.value ) );
};

export const drawBars = ( node, data, params ) => {
	const colorScale = getColorScale( data ),
		xScale = getXScale( data, params ),
		yScale = getYScale( data, params ),
		xGroupScale = getXGroupScale( data, params );
	const barGroup = node
		.append( 'g' )
		.attr( 'class', 'bars' )
		.selectAll( 'g' )
		.data( data )
		.enter()
		.append( 'g' )
		.attr( 'transform', d => `translate(${ xScale( d.date ) },0)` )
		.attr( 'class', 'bargroup' );

	barGroup
		.append( 'rect' )
		.attr( 'class', 'barfocus' )
		.attr( 'x', 0 )
		.attr( 'y', 0 )
		.attr( 'width', xGroupScale.range()[ 1 ] )
		.attr( 'height', params.height )
		.attr( 'opacity', '0' );

	barGroup
		.selectAll( '.bar' )
		.data( d => getOrderedKeys( data ).map( key => ( { key: key, value: d[ key ] } ) ) )
		.enter()
		.append( 'rect' )
		.attr( 'class', 'bar' )
		.attr( 'x', d => xGroupScale( d.key ) )
		.attr( 'y', d => yScale( d.value ) )
		.attr( 'width', xGroupScale.bandwidth() )
		.attr( 'height', d => params.height - yScale( d.value ) )
		.attr( 'fill', ( d, i ) => d3InterpolateViridis( colorScale( i ) ) );

	barGroup
		.append( 'rect' )
		.attr( 'class', 'barmouse' )
		.attr( 'x', 0 )
		.attr( 'y', 0 )
		.attr( 'width', xGroupScale.range()[ 1 ] )
		.attr( 'height', params.height )
		.attr( 'opacity', '0' );
	// .on( 'mouseover', handleMouseOverBarChart )
	// .on( 'mouseout', handleMouseOutBarChart )

	// const tooltip = d3
	// 	.select( 'body' )
	// 	.append( 'div' )
	// 	.attr( 'class', 'tooltip' );
};
