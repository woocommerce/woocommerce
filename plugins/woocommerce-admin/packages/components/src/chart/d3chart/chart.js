/** @format */

/**
 * External dependencies
 */
import { isEmpty, isEqual } from 'lodash';
import { Component, createRef } from '@wordpress/element';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { timeFormat as d3TimeFormat, utcParse as d3UTCParse } from 'd3-time-format';
import { select as d3Select } from 'd3-selection';

/**
 * Internal dependencies
 */
import D3Base from './d3base';
import {
	drawAxis,
	drawBars,
	drawLines,
	getDateSpaces,
	getOrderedKeys,
	getLine,
	getLineData,
	getXTicks,
	getUniqueKeys,
	getUniqueDates,
	getXScale,
	getXGroupScale,
	getXLineScale,
	getYMax,
	getYScale,
	getYTickOffset,
	getFormatter,
} from './utils';

/**
 * A simple D3 line and bar chart component for timeseries data in React.
 */
class D3Chart extends Component {
	constructor( props ) {
		super( props );
		this.drawChart = this.drawChart.bind( this );
		this.getAllData = this.getAllData.bind( this );
		this.getParams = this.getParams.bind( this );
		this.state = {
			allData: this.getAllData( props ),
			type: props.type,
		};
		this.tooltipRef = createRef();
	}

	componentDidUpdate( prevProps, prevState ) {
		const { type } = this.props;
		/* eslint-disable react/no-did-update-set-state */
		const nextAllData = this.getAllData( this.props );
		if ( ! isEqual( [ ...nextAllData ].sort(), [ ...prevState.allData ].sort() ) ) {
			this.setState( { allData: nextAllData } );
		}
		if ( type !== prevProps.type ) {
			this.setState( { type } );
		}
		/* eslint-enable react/no-did-update-set-state */
	}

	getAllData( props ) {
		const orderedKeys =
			props.orderedKeys || getOrderedKeys( props.data, getUniqueKeys( props.data ) );
		return [ ...props.data, ...orderedKeys ];
	}

	drawChart( node ) {
		const { data, margin, type } = this.props;
		const params = this.getParams();
		const adjParams = Object.assign( {}, params, {
			height: params.adjHeight,
			width: params.adjWidth,
			tooltip: d3Select( this.tooltipRef.current ),
			valueType: params.valueType,
		} );

		const g = node
			.attr( 'id', 'chart' )
			.append( 'g' )
			.attr( 'transform', `translate(${ margin.left },${ margin.top })` );

		drawAxis( g, adjParams );
		type === 'line' && drawLines( g, data, adjParams );
		type === 'bar' && drawBars( g, data, adjParams );
	}

	getParams() {
		const {
			colorScheme,
			data,
			dateParser,
			height,
			interval,
			margin,
			mode,
			orderedKeys,
			tooltipPosition,
			tooltipLabelFormat,
			tooltipValueFormat,
			tooltipTitle,
			type,
			width,
			xFormat,
			x2Format,
			yFormat,
			valueType,
		} = this.props;
		const adjHeight = height - margin.top - margin.bottom;
		const adjWidth = width - margin.left - margin.right;
		const uniqueKeys = getUniqueKeys( data );
		const newOrderedKeys = orderedKeys || getOrderedKeys( data, uniqueKeys );
		const lineData = getLineData( data, newOrderedKeys );
		const yMax = getYMax( lineData );
		const yScale = getYScale( adjHeight, yMax );
		const parseDate = d3UTCParse( dateParser );
		const uniqueDates = getUniqueDates( lineData, parseDate );
		const xLineScale = getXLineScale( uniqueDates, adjWidth );
		const xScale = getXScale( uniqueDates, adjWidth );
		const xTicks = getXTicks( uniqueDates, adjWidth, mode, interval );
		return {
			adjHeight,
			adjWidth,
			colorScheme,
			dateSpaces: getDateSpaces( data, uniqueDates, adjWidth, xLineScale ),
			line: getLine( xLineScale, yScale ),
			lineData,
			margin,
			mode,
			orderedKeys: newOrderedKeys,
			parseDate,
			tooltipPosition,
			tooltipLabelFormat: getFormatter( tooltipLabelFormat, d3TimeFormat ),
			tooltipValueFormat: getFormatter( tooltipValueFormat ),
			tooltipTitle,
			type,
			uniqueDates,
			uniqueKeys,
			xFormat: getFormatter( xFormat, d3TimeFormat ),
			x2Format: getFormatter( x2Format, d3TimeFormat ),
			xGroupScale: getXGroupScale( orderedKeys, xScale ),
			xLineScale,
			xTicks,
			xScale,
			yMax,
			yScale,
			yTickOffset: getYTickOffset( adjHeight, yMax ),
			yFormat: getFormatter( yFormat ),
			valueType,
		};
	}

	render() {
		if ( isEmpty( this.props.data ) ) {
			return null; // TODO: improve messaging
		}
		return (
			<div
				className={ classNames( 'd3-chart__container', this.props.className ) }
				style={ { height: this.props.height } }
			>
				<D3Base
					className={ classNames( this.props.className ) }
					data={ this.state.allData }
					drawChart={ this.drawChart }
					height={ this.props.height }
					type={ this.state.type }
					width={ this.props.width }
				/>
				<div className="d3-chart__tooltip" ref={ this.tooltipRef } />
			</div>
		);
	}
}

D3Chart.propTypes = {
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
	/**
	 * A chromatic color function to be passed down to d3.
	 */
	colorScheme: PropTypes.func,
	/**
	 * An array of data.
	 */
	data: PropTypes.array.isRequired,
	/**
	 * Format to parse dates into d3 time format
	 */
	dateParser: PropTypes.string.isRequired,
	/**
	 * Height of the `svg`.
	 */
	height: PropTypes.number,
	/**
	 * Interval specification (hourly, daily, weekly etc.)
	 */
	interval: PropTypes.oneOf( [ 'hour', 'day', 'week', 'month', 'quarter', 'year' ] ),
	/**
	 * Margins for axis and chart padding.
	 */
	margin: PropTypes.shape( {
		bottom: PropTypes.number,
		left: PropTypes.number,
		right: PropTypes.number,
		top: PropTypes.number,
	} ),
	/**
	 * `items-comparison` (default) or `time-comparison`, this is used to generate correct
	 * ARIA properties.
	 */
	mode: PropTypes.oneOf( [ 'item-comparison', 'time-comparison' ] ),
	/**
	 * The list of labels for this chart.
	 */
	orderedKeys: PropTypes.array,
	/**
	 * A datetime formatting string or overriding function to format the tooltip label.
	 */
	tooltipLabelFormat: PropTypes.oneOfType( [ PropTypes.string, PropTypes.func ] ),
	/**
	 * A number formatting string or function to format the value displayed in the tooltips.
	 */
	tooltipValueFormat: PropTypes.oneOfType( [ PropTypes.string, PropTypes.func ] ),
	/**
	 * The position where to render the tooltip can be `over` the chart or `below` the chart.
	 */
	tooltipPosition: PropTypes.oneOf( [ 'below', 'over' ] ),
	/**
	 * A string to use as a title for the tooltip. Takes preference over `tooltipFormat`.
	 */
	tooltipTitle: PropTypes.string,
	/**
	 * Chart type of either `line` or `bar`.
	 */
	type: PropTypes.oneOf( [ 'bar', 'line' ] ),
	/**
	 * Width of the `svg`.
	 */
	width: PropTypes.number,
	/**
	 * A datetime formatting string or function, passed to d3TimeFormat.
	 */
	xFormat: PropTypes.oneOfType( [ PropTypes.string, PropTypes.func ] ),
	/**
	 * A datetime formatting string or function, passed to d3TimeFormat.
	 */
	x2Format: PropTypes.oneOfType( [ PropTypes.string, PropTypes.func ] ),
	/**
	 * A number formatting string or function, passed to d3Format.
	 */
	yFormat: PropTypes.oneOfType( [ PropTypes.string, PropTypes.func ] ),
};

D3Chart.defaultProps = {
	data: [],
	dateParser: '%Y-%m-%dT%H:%M:%S',
	height: 200,
	margin: {
		bottom: 30,
		left: 40,
		right: 0,
		top: 20,
	},
	mode: 'time-comparison',
	tooltipPosition: 'over',
	tooltipLabelFormat: '%B %d, %Y',
	tooltipValueFormat: ',',
	type: 'line',
	width: 600,
	xFormat: '%Y-%m-%d',
	x2Format: '',
	yFormat: '.3s',
};

export default D3Chart;
