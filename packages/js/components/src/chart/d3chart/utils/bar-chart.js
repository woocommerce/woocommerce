/**
 * External dependencies
 */
import { get } from 'lodash';
import { event as d3Event } from 'd3-selection';
import moment from 'moment';

export const drawBars = ( node, data, params, scales, formats, tooltip ) => {
	const height = scales.yScale.range()[ 0 ];
	const barGroup = node
		.append( 'g' )
		.attr( 'class', 'bars' )
		.selectAll( 'g' )
		.data( data )
		.enter()
		.append( 'g' )
		.attr(
			'transform',
			( d ) => `translate(${ scales.xScale( d.date ) }, 0)`
		)
		.attr( 'class', 'bargroup' )
		.attr( 'role', 'region' )
		.attr( 'aria-label', ( d ) =>
			params.mode === 'item-comparison'
				? formats.screenReaderFormat(
						d.date instanceof Date
							? d.date
							: moment( d.date ).toDate()
				  )
				: null
		);

	barGroup
		.append( 'rect' )
		.attr( 'class', 'barfocus' )
		.attr( 'x', 0 )
		.attr( 'y', 0 )
		.attr( 'width', scales.xGroupScale.range()[ 1 ] )
		.attr( 'height', height )
		.attr( 'opacity', '0' )
		.on( 'mouseover', ( d, i, nodes ) => {
			tooltip.show(
				data.find( ( e ) => e.date === d.date ),
				d3Event.target,
				nodes[ i ].parentNode
			);
		} )
		.on( 'mouseout', () => tooltip.hide() );

	const basePosition = scales.yScale( 0 );
	barGroup
		.selectAll( '.bar' )
		.data( ( d ) =>
			params.visibleKeys.map( ( row ) => ( {
				key: row.key,
				focus: row.focus,
				value: get( d, [ row.key, 'value' ], 0 ),
				label: row.label,
				visible: row.visible,
				date: d.date,
			} ) )
		)
		.enter()
		.append( 'rect' )
		.attr( 'class', 'bar' )
		.attr( 'x', ( d ) => scales.xGroupScale( d.key ) )
		.attr( 'y', ( d ) =>
			Math.min( basePosition, scales.yScale( d.value ) )
		)
		.attr( 'width', scales.xGroupScale.bandwidth() )
		.attr( 'height', ( d ) =>
			Math.abs( basePosition - scales.yScale( d.value ) )
		)
		.attr( 'fill', ( d ) => params.getColor( d.key ) )
		.attr( 'pointer-events', 'none' )
		.attr( 'tabindex', '0' )
		.attr( 'aria-label', ( d ) => {
			let label = d.label || d.key;
			if ( params.mode === 'time-comparison' ) {
				const dayData = data.find( ( e ) => e.date === d.date );
				label = formats.screenReaderFormat(
					moment( dayData[ d.key ].labelDate ).toDate()
				);
			}
			return `${ label } ${ tooltip.valueFormat( d.value ) }`;
		} )
		.style( 'opacity', ( d ) => {
			const opacity = d.focus ? 1 : 0.1;
			return d.visible ? opacity : 0;
		} )
		.on( 'focus', ( d, i, nodes ) => {
			const targetNode =
				d.value > 0 ? d3Event.target : d3Event.target.parentNode;
			tooltip.show(
				data.find( ( e ) => e.date === d.date ),
				targetNode,
				nodes[ i ].parentNode
			);
		} )
		.on( 'blur', () => tooltip.hide() );
};
