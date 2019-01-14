/** @format */

/**
 * External dependencies
 */
import { get } from 'lodash';
import { event as d3Event, select as d3Select } from 'd3-selection';
import moment from 'moment';

/**
 * Internal dependencies
 */
import { getColor } from './color';
import { calculateTooltipPosition, hideTooltip, showTooltip } from './tooltip';

const handleMouseOverBarChart = ( date, parentNode, node, data, params, position ) => {
	d3Select( parentNode )
		.select( '.barfocus' )
		.attr( 'opacity', '0.1' );
	showTooltip( params, data.find( e => e.date === date ), position );
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
		.attr( 'class', 'bargroup' )
		.attr( 'role', 'region' )
		.attr(
			'aria-label',
			d =>
				params.mode === 'item-comparison'
					? params.tooltipLabelFormat( d.date instanceof Date ? d.date : moment( d.date ).toDate() )
					: null
		);

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
				value: get( d, [ row.key, 'value' ], 0 ),
				label: get( d, [ row.key, 'label' ], '' ),
				visible: row.visible,
				date: d.date,
			} ) )
		)
		.enter()
		.append( 'rect' )
		.attr( 'class', 'bar' )
		.attr( 'x', d => params.xGroupScale( d.key ) )
		.attr( 'y', d => params.yScale( d.value ) )
		.attr( 'width', params.xGroupScale.bandwidth() )
		.attr( 'height', d => params.height - params.yScale( d.value ) )
		.attr( 'fill', d => getColor( d.key, params.orderedKeys, params.colorScheme ) )
		.attr( 'tabindex', '0' )
		.attr( 'aria-label', d => {
			const label = params.mode === 'time-comparison' && d.label ? d.label : d.key;
			return `${ label } ${ params.tooltipValueFormat( d.value ) }`;
		} )
		.style( 'opacity', d => {
			const opacity = d.focus ? 1 : 0.1;
			return d.visible ? opacity : 0;
		} )
		.on( 'focus', ( d, i, nodes ) => {
			const targetNode = d.value > 0 ? d3Event.target : d3Event.target.parentNode;
			const position = calculateTooltipPosition( targetNode, node.node(), params.tooltipPosition );
			handleMouseOverBarChart( d.date, nodes[ i ].parentNode, node, data, params, position );
		} )
		.on( 'blur', ( d, i, nodes ) => hideTooltip( nodes[ i ].parentNode, params.tooltip ) );

	barGroup
		.append( 'rect' )
		.attr( 'class', 'barmouse' )
		.attr( 'x', 0 )
		.attr( 'y', 0 )
		.attr( 'width', params.xGroupScale.range()[ 1 ] )
		.attr( 'height', params.height )
		.attr( 'opacity', '0' )
		.on( 'mouseover', ( d, i, nodes ) => {
			const position = calculateTooltipPosition(
				d3Event.target,
				node.node(),
				params.tooltipPosition
			);
			handleMouseOverBarChart( d.date, nodes[ i ].parentNode, node, data, params, position );
		} )
		.on( 'mouseout', ( d, i, nodes ) => hideTooltip( nodes[ i ].parentNode, params.tooltip ) );
};
