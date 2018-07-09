/** @format */

/**
 * External dependencies
 */

import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { format as d3Format } from 'd3-format';
import { timeFormat as d3TimeFormat } from 'd3-time-format';

/**
 * Internal dependencies
 */
import './style.scss';
import D3Base from '../base';
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

const D3Chart = ( {
	className,
	data,
	height,
	margin,
	timeseries,
	type,
	xFormat,
	yFormat,
	width,
} ) => {
	const drawChart = ( node, params ) => {
		const g = node
			.select( 'svg' )
			.select( 'g' )
			.attr( 'id', 'chart' )
			.append( 'g' )
			.attr( 'transform', `translate(${ margin.left },${ margin.top })` );

		const adjParams = Object.assign( {}, params, {
			height: params.height - margin.top - margin.bottom,
			width: params.width - margin.left - margin.right,
		} );
		drawAxis( g, data, adjParams );
		type === 'line' && drawLines( node, data, adjParams );
		type === 'bar' && drawBars( node, data, adjParams );
		return node;
	};

	const getParams = node => {
		const calculatedWidth = width || node.offsetWidth;
		const calculatedHeight = height || node.offsetHeight;
		const scale = width / node.offsetWidth;
		const adjHeight = calculatedHeight - margin.top - margin.bottom;
		const adjWidth = calculatedWidth - margin.left - margin.right;
		const uniqueKeys = getUniqueKeys( data );
		const orderedKeys = getOrderedKeys( data, uniqueKeys );
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
			orderedKeys,
			scale,
			type,
			uniqueDates,
			uniqueKeys,
			width: calculatedWidth,
			xFormat: timeseries ? d3TimeFormat( xFormat ) : d3Format( xFormat ),
			xGroupScale: getXGroupScale( orderedKeys, xScale ),
			xLineScale,
			xScale,
			yMax,
			yScale,
			yTickOffset: getYTickOffset( adjHeight, scale, yMax ),
			yFormat: d3Format( yFormat ),
		};
	};

	return (
		<D3Base className={ classNames( className ) } drawChart={ drawChart } getParams={ getParams } />
	);
};

D3Chart.propTypes = {
	className: PropTypes.string,
	data: PropTypes.array,
	height: PropTypes.number,
	margin: PropTypes.shape( {
		bottom: PropTypes.number,
		left: PropTypes.number,
		right: PropTypes.number,
		top: PropTypes.number,
	} ),
	timeseries: PropTypes.bool,
	type: PropTypes.oneOf( [ 'bar', 'line' ] ),
	width: PropTypes.number,
	xFormat: PropTypes.string,
	yFormat: PropTypes.string,
};

D3Chart.defaultProps = {
	height: 200,
	margin: {
		bottom: 30,
		left: 40,
		right: 0,
		top: 20,
	},
	timeseries: true,
	type: 'line',
	width: 600,
	xFormat: '%Y-%m-%d',
	yFormat: ',.0f',
};

export default D3Chart;
