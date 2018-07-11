/** @format */

/**
 * External dependencies
 */

import React from 'react';
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { format as d3Format } from 'd3-format';
import { timeFormat as d3TimeFormat } from 'd3-time-format';

/**
 * Internal dependencies
 */
import './style.scss';
import D3Base from './d3-base';
import {
	drawAxis,
	drawBars,
	drawLines,
	getColorScale,
	getDateSpaces,
	getOrderedKeys,
	getLine,
	getLineData,
	getUniqueKeys,
	getUniqueDates,
	getXScale,
	getXGroupScale,
	getXLineScale,
	getYMax,
	getYScale,
	getYTickOffset,
} from './utils';

class D3Chart extends Component {
	static propTypes = {
		className: PropTypes.string,
		data: PropTypes.array.isRequired,
		height: PropTypes.number,
		legend: PropTypes.array,
		margin: PropTypes.shape( {
			bottom: PropTypes.number,
			left: PropTypes.number,
			right: PropTypes.number,
			top: PropTypes.number,
		} ),
		orderedKeys: PropTypes.array,
		type: PropTypes.oneOf( [ 'bar', 'line' ] ),
		width: PropTypes.number,
		xFormat: PropTypes.string,
		yFormat: PropTypes.string,
	};

	static defaultProps = {
		height: 200,
		margin: {
			bottom: 30,
			left: 40,
			right: 0,
			top: 20,
		},
		type: 'line',
		width: 600,
		xFormat: '%Y-%m-%d',
		yFormat: ',.0f',
	};

	state = {
		allData: null,
	};

	static getDerivedStateFromProps( nextProps, prevState ) {
		const nextAllData = [ ...nextProps.data, ...nextProps.orderedKeys ];

		if ( prevState.allData !== nextAllData ) {
			return { allData: nextAllData };
		}

		return null;
	}

	drawChart = ( node, params ) => {
		const { data, margin, type } = this.props;
		const g = node
			.attr( 'id', 'chart' )
			.append( 'g' )
			.attr( 'transform', `translate(${ margin.left },${ margin.top })` );

		const adjParams = Object.assign( {}, params, {
			height: params.height - margin.top - margin.bottom,
			width: params.width - margin.left - margin.right,
		} );

		drawAxis( g, data, adjParams );
		type === 'line' && drawLines( g, data, adjParams );
		type === 'bar' && drawBars( g, data, adjParams );

		return node;
	};

	getParams = node => {
		const { data, height, margin, orderedKeys, type, width, xFormat, yFormat } = this.props;
		const calculatedWidth = width || node.offsetWidth;
		const calculatedHeight = height || node.offsetHeight;
		const scale = width / node.offsetWidth;
		const adjHeight = calculatedHeight - margin.top - margin.bottom;
		const adjWidth = calculatedWidth - margin.left - margin.right;
		const uniqueKeys = getUniqueKeys( data );
		const newOrderedKeys = orderedKeys ? orderedKeys : getOrderedKeys( data, uniqueKeys );
		const lineData = getLineData( data, orderedKeys );
		const yMax = getYMax( lineData );
		const yScale = getYScale( adjHeight, yMax );
		const uniqueDates = getUniqueDates( lineData );
		const xLineScale = getXLineScale( uniqueDates, adjWidth );
		const xScale = getXScale( uniqueDates, adjWidth );
		return {
			colorScale: getColorScale( orderedKeys ),
			dateSpaces: getDateSpaces( uniqueDates, adjWidth, xLineScale ),
			height: calculatedHeight,
			line: getLine( data, xLineScale, yScale ),
			lineData,
			margin,
			orderedKeys: newOrderedKeys,
			scale,
			type,
			uniqueDates,
			uniqueKeys,
			width: calculatedWidth,
			xFormat: d3TimeFormat( xFormat ),
			xGroupScale: getXGroupScale( orderedKeys, xScale ),
			xLineScale,
			xScale,
			yMax,
			yScale,
			yTickOffset: getYTickOffset( adjHeight, scale, yMax ),
			yFormat: d3Format( yFormat ),
		};
	};

	render() {
		if ( ! this.props.data ) {
			return null; // TODO: improve messaging
		}

		return (
			<D3Base
				className={ classNames( this.props.className ) }
				data={ this.state.allData }
				drawChart={ this.drawChart }
				getParams={ this.getParams }
			/>
		);
	}
}

export default D3Chart;
