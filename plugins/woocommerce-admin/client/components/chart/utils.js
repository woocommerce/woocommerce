/** @format */

/**
 * External dependencies
 */

import { findIndex } from 'lodash';
import { max as d3Max } from 'd3-array';
import { axisBottom as d3AxisBottom, axisLeft as d3AxisLeft } from 'd3-axis';
import { format as d3Format } from 'd3-format';
import {
	scaleBand as d3ScaleBand,
	scaleLinear as d3ScaleLinear,
	scaleTime as d3ScaleTime,
} from 'd3-scale';
import { mouse as d3Mouse, select as d3Select } from 'd3-selection';
import { line as d3Line } from 'd3-shape';
/**
 * Internal dependencies
 */
import { formatCurrency } from 'lib/currency';

/**
 * Describes `getUniqueKeys`
 * @param {array} data - The chart component's `data` prop.
 * @returns {array} of unique category keys
 */
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

/**
 * Describes `getOrderedKeys`
 * @param {array} data - The chart component's `data` prop.
 * @param {array} uniqueKeys - from `getUniqueKeys`.
 * @returns {array} of unique category keys ordered by cumulative total value
 */
export const getOrderedKeys = ( data, uniqueKeys ) =>
	uniqueKeys
		.map( key => ( {
			key,
			focus: true,
			total: data.reduce( ( a, c ) => a + c[ key ], 0 ),
			visible: true,
		} ) )
		.sort( ( a, b ) => b.total - a.total );

/**
 * Describes `getLineData`
 * @param {array} data - The chart component's `data` prop.
 * @param {array} orderedKeys - from `getOrderedKeys`.
 * @returns {array} an array objects with a category `key` and an array of `values` with `date` and `value` properties
 */
export const getLineData = ( data, orderedKeys ) =>
	orderedKeys.map( row => ( {
		key: row.key,
		focus: row.focus,
		visible: row.visible,
		values: data.map( d => ( {
			date: d.date,
			focus: row.focus,
			value: d[ row.key ],
			visible: row.visible,
		} ) ),
	} ) );

/**
 * Describes `getUniqueDates`
 * @param {array} lineData - from `GetLineData`
 * @param {function} parseDate - D3 time format parser
 * @returns {array} an array of unique date values sorted from earliest to latest
 */
export const getUniqueDates = ( lineData, parseDate ) => {
	return [
		...new Set(
			lineData.reduce( ( accum, { values } ) => {
				values.forEach( ( { date } ) => accum.push( date ) );
				return accum;
			}, [] )
		),
	].sort( ( a, b ) => parseDate( a ) - parseDate( b ) );
};

export const getColor = ( key, params ) => {
	const smallColorScales = [
		[],
		[ 0.5 ],
		[ 0.333, 0.667 ],
		[ 0.2, 0.5, 0.8 ],
		[ 0.12, 0.375, 0.625, 0.88 ],
	];
	let keyValue = 0;
	const len = params.orderedKeys.length;
	const idx = findIndex( params.orderedKeys, d => d.key === key );
	if ( len < 5 ) {
		keyValue = smallColorScales[ len ][ idx ];
	} else {
		keyValue = idx / ( params.orderedKeys.length - 1 );
	}
	return params.colorScheme( keyValue );
};

/**
 * Describes getXScale
 * @param {array} uniqueDates - from `getUniqueDates`
 * @param {number} width - calculated width of the charting space
 * @returns {function} a D3 scale of the dates
 */
export const getXScale = ( uniqueDates, width ) =>
	d3ScaleBand()
		.domain( uniqueDates )
		.rangeRound( [ 0, width ] )
		.paddingInner( 0.1 );

/**
 * Describes getXGroupScale
 * @param {array} orderedKeys - from `getOrderedKeys`
 * @param {function} xScale - from `getXScale`
 * @returns {function} a D3 scale for each category within the xScale range
 */
export const getXGroupScale = ( orderedKeys, xScale ) =>
	d3ScaleBand()
		.domain( orderedKeys.filter( d => d.visible ).map( d => d.key ) )
		.rangeRound( [ 0, xScale.bandwidth() ] )
		.padding( 0.07 );

/**
 * Describes getXLineScale
 * @param {array} uniqueDates - from `getUniqueDates`
 * @param {number} width - calculated width of the charting space
 * @returns {function} a D3 scaletime for each date
 */
export const getXLineScale = ( uniqueDates, width ) =>
	d3ScaleTime()
		.domain( [ new Date( uniqueDates[ 0 ] ), new Date( uniqueDates[ uniqueDates.length - 1 ] ) ] )
		.rangeRound( [ 0, width ] );

/**
 * Describes and rounds the maximum y value to the nearest thousadn, ten-thousand, million etc.
 * @param {array} lineData - from `getLineData`
 * @returns {number} the maximum value in the timeseries multiplied by 4/3
 */
export const getYMax = lineData => {
	const yMax = 4 / 3 * d3Max( lineData, d => d3Max( d.values.map( date => date.value ) ) );
	const pow3Y = Math.pow( 10, ( ( Math.log( yMax ) * Math.LOG10E + 1 ) | 0 ) - 2 ) * 3;
	return Math.ceil( yMax / pow3Y ) * pow3Y;
};

/**
 * Describes getYScale
 * @param {number} height - calculated height of the charting space
 * @param {number} yMax - from `getYMax`
 * @returns {function} the D3 linear scale from 0 to the value from `getYMax`
 */
export const getYScale = ( height, yMax ) =>
	d3ScaleLinear()
		.domain( [ 0, yMax ] )
		.rangeRound( [ height, 0 ] );

/**
 * Describes getyTickOffset
 * @param {number} height - calculated height of the charting space
 * @param {number} scale - ratio of the expected width to calculated width (given the viewbox)
 * @param {number} yMax - from `getYMax`
 * @returns {function} the D3 linear scale from 0 to the value from `getYMax`, offset by 12 pixels down
 */
export const getYTickOffset = ( height, scale, yMax ) =>
	d3ScaleLinear()
		.domain( [ 0, yMax ] )
		.rangeRound( [ height + scale * 12, scale * 12 ] );

/**
 * Describes getyTickOffset
 * @param {function} xLineScale - from `getXLineScale`.
 * @param {function} yScale - from `getYScale`.
 * @returns {function} the D3 line function for plotting all category values
 */
export const getLine = ( xLineScale, yScale ) =>
	d3Line()
		.x( d => xLineScale( new Date( d.date ) ) )
		.y( d => yScale( d.value ) );

/**
 * Describes getDateSpaces
 * @param {array} uniqueDates - from `getUniqueDates`
 * @param {number} width - calculated width of the charting space
 * @param {function} xLineScale - from `getXLineScale`
 * @returns {array} that icnludes the date, start (x position) and width to layout the mouseover rectangles
 */
export const getDateSpaces = ( uniqueDates, width, xLineScale ) =>
	uniqueDates.map( ( d, i ) => {
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
			width: uniqueDates.length > 1 ? xWidth : width,
		};
	} );

export const drawAxis = ( node, params ) => {
	const xScale = params.type === 'line' ? params.xLineScale : params.xScale;

	const yGrids = [];
	for ( let i = 0; i < 4; i++ ) {
		yGrids.push( i / 3 * params.yMax );
	}

	node
		.append( 'g' )
		.attr( 'class', 'axis' )
		.attr( 'transform', `translate(0,${ params.height })` )
		.call(
			d3AxisBottom( xScale )
				.tickValues( params.uniqueDates.map( d => ( params.type === 'line' ? new Date( d ) : d ) ) )
				.tickFormat( d => params.xFormat( d instanceof Date ? d : new Date( d ) ) )
		);

	node
		.append( 'g' )
		.attr( 'class', 'axis axis-month' )
		.attr( 'transform', `translate(3, ${ params.height + 20 })` )
		.call(
			d3AxisBottom( xScale )
				.tickValues( params.uniqueDates.map( d => ( params.type === 'line' ? new Date( d ) : d ) ) )
				.tickFormat( d => params.x2Format( d instanceof Date ? d : new Date( d ) ) )
		)
		.call( g => g.select( '.domain' ).remove() );

	node
		.selectAll( '.axis-month .tick text' )
		.style( 'font-size', `${ Math.round( params.scale * 10 ) }px` );

	node
		.append( 'g' )
		.attr( 'class', 'pipes' )
		.attr( 'transform', `translate(0, ${ params.height })` )
		.call(
			d3AxisBottom( xScale )
				.tickValues( params.uniqueDates.map( d => ( params.type === 'line' ? new Date( d ) : d ) ) )
				.tickSize( 5 )
				.tickFormat( '' )
		);

	node
		.append( 'g' )
		.attr( 'class', 'grid' )
		.attr( 'transform', `translate(-${ params.margin.left },0)` )
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
		.attr( 'transform', 'translate(-50, 0)' )
		.attr( 'text-anchor', 'start' )
		.call(
			d3AxisLeft( params.yTickOffset )
				.tickValues( yGrids )
				.tickFormat( d => d3Format( params.yFormat )( d !== 0 ? d : 0 ) )
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

const showTooltip = ( node, params, d ) => {
	const chartCoords = node.node().getBoundingClientRect();
	let [ xPosition, yPosition ] = d3Mouse( node.node() );
	xPosition = xPosition > chartCoords.width - 340 ? xPosition - 340 : xPosition + 100;
	yPosition = yPosition > chartCoords.height - 150 ? yPosition - 200 : yPosition + 20;
	const keys = params.orderedKeys.filter( row => row.visible ).map(
		row => `
				<li class="key-row">
					<div class="key-container">
						<span class="key-color" style="background-color:${ getColor( row.key, params ) }"></span>
						<span class="key-key">${ row.key }:</span>
					</div>
					<span class="key-value">${ formatCurrency( d[ row.key ] ) }</span>
				</li>
			`
	);

	params.tooltip
		.style( 'left', xPosition + 'px' )
		.style( 'top', yPosition + 'px' )
		.style( 'display', 'flex' ).html( `
			<div>
				<h4>${ params.tooltipFormat( d.date instanceof Date ? d.date : new Date( d.date ) ) }</h4>
				<ul>
				${ keys.join( '' ) }
				</ul>
			</div>
		` );
};

const handleMouseOverBarChart = ( d, i, nodes, node, data, params ) => {
	d3Select( nodes[ i ].parentNode )
		.select( '.barfocus' )
		.attr( 'opacity', '0.1' );
	showTooltip( node, params, d );
};

const handleMouseOutBarChart = ( d, i, nodes, params ) => {
	d3Select( nodes[ i ].parentNode )
		.select( '.barfocus' )
		.attr( 'opacity', '0' );
	params.tooltip.style( 'display', 'flex' );
};

const handleMouseOverLineChart = ( d, i, nodes, node, data, params ) => {
	d3Select( nodes[ i ].parentNode )
		.select( '.focus-grid' )
		.attr( 'opacity', '1' );
	showTooltip( node, params, data.find( e => e.date === d.date ) );
};

const handleMouseOutLineChart = ( d, i, nodes, params ) => {
	d3Select( nodes[ i ].parentNode )
		.select( '.focus-grid' )
		.attr( 'opacity', '0' );
	params.tooltip.style( 'display', 'none' );
};

export const drawLines = ( node, data, params ) => {
	const series = node
		.append( 'g' )
		.attr( 'class', 'lines' )
		.selectAll( '.line-g' )
		.data( params.lineData.filter( d => d.visible ) )
		.enter()
		.append( 'g' )
		.attr( 'class', 'line-g' );

	series
		.append( 'path' )
		.attr( 'fill', 'none' )
		.attr( 'stroke-width', 3 )
		.attr( 'stroke-linejoin', 'round' )
		.attr( 'stroke-linecap', 'round' )
		.attr( 'stroke', d => getColor( d.key, params ) )
		.style( 'opacity', d => {
			const opacity = d.focus ? 1 : 0.1;
			return d.visible ? opacity : 0;
		} )
		.attr( 'd', d => params.line( d.values ) );

	series
		.selectAll( 'circle' )
		.data( ( d, i ) => d.values.map( row => ( { ...row, i, visible: d.visible, key: d.key } ) ) )
		.enter()
		.append( 'circle' )
		.attr( 'r', 6 )
		.attr( 'fill', d => getColor( d.key, params ) )
		.attr( 'stroke', '#fff' )
		.attr( 'stroke-width', 3 )
		.style( 'opacity', d => {
			const opacity = d.focus ? 1 : 0.1;
			return d.visible ? opacity : 0;
		} )
		.attr( 'cx', d => params.xLineScale( new Date( d.date ) ) )
		.attr( 'cy', d => params.yScale( d.value ) )
		.on( 'mouseover', ( d, i, nodes ) =>
			handleMouseOverLineChart( d, i, nodes, node, data, params )
		)
		.on( 'mouseout', ( d, i, nodes ) => handleMouseOutLineChart( d, i, nodes, params ) );

	const focus = node
		.append( 'g' )
		.attr( 'class', 'focusspaces' )
		.selectAll( '.focus' )
		.data( params.dateSpaces )
		.enter()
		.append( 'g' )
		.attr( 'class', 'focus' );

	focus
		.append( 'line' )
		.attr( 'class', 'focus-grid' )
		.attr( 'x1', d => params.xLineScale( new Date( d.date ) ) )
		.attr( 'y1', 0 )
		.attr( 'x2', d => params.xLineScale( new Date( d.date ) ) )
		.attr( 'y2', params.height )
		.attr( 'opacity', '0' );

	focus
		.append( 'rect' )
		.attr( 'class', 'focus-g' )
		.attr( 'x', d => d.start )
		.attr( 'y', 0 )
		.attr( 'width', d => d.width )
		.attr( 'height', params.height )
		.attr( 'opacity', 0 )
		.on( 'mouseover', ( d, i, nodes ) =>
			handleMouseOverLineChart( d, i, nodes, node, data, params )
		)
		.on( 'mouseout', ( d, i, nodes ) => handleMouseOutLineChart( d, i, nodes, params ) );
};

export const drawBars = ( node, data, params ) => {
	const barGroup = node
		.append( 'g' )
		.attr( 'class', 'bars' )
		.selectAll( 'g' )
		.data( data )
		.enter()
		.append( 'g' )
		.attr( 'transform', d => `translate(${ params.xScale( d.date ) },0)` )
		.attr( 'class', 'bargroup' );

	barGroup
		.append( 'rect' )
		.attr( 'class', 'barfocus' )
		.attr( 'x', 0 )
		.attr( 'y', 0 )
		.attr( 'width', params.xGroupScale.range()[ 1 ] )
		.attr( 'height', params.height )
		.attr( 'opacity', '0' );

	barGroup
		.selectAll( '.bar' )
		.data( d =>
			params.orderedKeys.filter( row => row.visible ).map( row => ( {
				key: row.key,
				focus: row.focus,
				value: d[ row.key ],
				visible: row.visible,
			} ) )
		)
		.enter()
		.append( 'rect' )
		.attr( 'class', 'bar' )
		.attr( 'x', d => params.xGroupScale( d.key ) )
		.attr( 'y', d => params.yScale( d.value ) )
		.attr( 'width', params.xGroupScale.bandwidth() )
		.attr( 'height', d => params.height - params.yScale( d.value ) )
		.attr( 'fill', d => getColor( d.key, params ) )
		.style( 'opacity', d => {
			const opacity = d.focus ? 1 : 0.1;
			return d.visible ? opacity : 0;
		} )
		.on( 'mouseover', ( d, i, nodes ) =>
			handleMouseOverBarChart( d, i, nodes, node, data, params )
		)
		.on( 'mouseout', ( d, i, nodes ) => handleMouseOutBarChart( d, i, nodes, params ) );

	barGroup
		.append( 'rect' )
		.attr( 'class', 'barmouse' )
		.attr( 'x', 0 )
		.attr( 'y', 0 )
		.attr( 'width', params.xGroupScale.range()[ 1 ] )
		.attr( 'height', params.height )
		.attr( 'opacity', '0' )
		.on( 'mouseover', ( d, i, nodes ) =>
			handleMouseOverBarChart( d, i, nodes, node, data, params )
		)
		.on( 'mouseout', ( d, i, nodes ) => handleMouseOutBarChart( d, i, nodes, params ) );
};
