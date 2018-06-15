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
import { drawAxis, drawBars } from './utils';

const D3Chart = ( { className, data, height, margin, timeseries, xFormat, yFormat, width } ) => {
	const drawChart = ( svg, params ) => {
		const g = svg
			.attr( 'id', 'barchart' )
			.append( 'g' )
			.attr( 'transform', `translate(${ margin.left },${ margin.top })` );

		const adjParams = Object.assign( {}, params, {
			height: params.height - margin.top - margin.bottom,
			width: params.width - margin.left - margin.right,
		} );
		drawAxis( g, data, adjParams );
		drawBars( g, data, adjParams );
		return svg;
	};

	const getParams = node => {
		const calculatedWidth = width || node.offsetWidth;
		const calculatedHeight = height || node.offsetHeight;
		return {
			base: 0,
			height: calculatedHeight,
			margin,
			width: calculatedWidth,
			scale: width / node.offsetWidth,
			xFormat: timeseries ? d3TimeFormat( xFormat ) : d3Format( xFormat ),
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
	width: 600,
	xFormat: '%Y-%m-%d',
	yFormat: ',.0f',
};

export default D3Chart;
