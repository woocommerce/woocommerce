/** @format */

/**
 * External dependencies
 */
import { isEqual } from 'lodash';
import { Component, createRef } from '@wordpress/element';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { timeFormat as d3TimeFormat, utcParse as d3UTCParse } from 'd3-time-format';
import { select as d3Select } from 'd3-selection';

/**
 * Internal dependencies
 */
import './style.scss';
import D3Base from './d3-base';
import {
	drawAxis,
	drawBars,
	drawLines,
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
			width: props.width,
		};
		this.tooltipRef = createRef();
	}

	componentDidUpdate( prevProps, prevState ) {
		const { type, width } = this.props;
		/* eslint-disable react/no-did-update-set-state */
		if ( width !== prevProps.width ) {
			this.setState( { width } );
		}
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

	drawChart( node, params ) {
		const { data, margin, type } = this.props;
		const g = node
			.attr( 'id', 'chart' )
			.append( 'g' )
			.attr( 'transform', `translate(${ margin.left },${ margin.top })` );

		const adjParams = Object.assign( {}, params, {
			height: params.height - margin.top - margin.bottom,
			width: params.width - margin.left - margin.right,
			tooltip: d3Select( this.tooltipRef.current ),
		} );
		drawAxis( g, adjParams );
		type === 'line' && drawLines( g, data, adjParams );
		type === 'bar' && drawBars( g, data, adjParams );

		return node;
	}

	getParams( node ) {
		const {
			colorScheme,
			data,
			dateParser,
			height,
			margin,
			orderedKeys,
			tooltipFormat,
			type,
			xFormat,
			x2Format,
			yFormat,
		} = this.props;
		const { width } = this.state;
		const calculatedWidth = width || node.offsetWidth;
		const calculatedHeight = height || node.offsetHeight;
		const scale = width / node.offsetWidth;
		const adjHeight = calculatedHeight - margin.top - margin.bottom;
		const adjWidth = calculatedWidth - margin.left - margin.right;
		const uniqueKeys = getUniqueKeys( data );
		const newOrderedKeys = orderedKeys || getOrderedKeys( data, uniqueKeys );
		const lineData = getLineData( data, newOrderedKeys );
		const yMax = getYMax( lineData );
		const yScale = getYScale( adjHeight, yMax );
		const parseDate = d3UTCParse( dateParser );
		const uniqueDates = getUniqueDates( lineData, parseDate );
		const xLineScale = getXLineScale( uniqueDates, adjWidth );
		const xScale = getXScale( uniqueDates, adjWidth );
		return {
			colorScheme,
			dateSpaces: getDateSpaces( uniqueDates, adjWidth, xLineScale ),
			height: calculatedHeight,
			line: getLine( xLineScale, yScale ),
			lineData,
			margin,
			orderedKeys: newOrderedKeys,
			parseDate,
			scale,
			tooltipFormat: d3TimeFormat( tooltipFormat ),
			type,
			uniqueDates,
			uniqueKeys,
			width: calculatedWidth,
			xFormat: d3TimeFormat( xFormat ),
			x2Format: d3TimeFormat( x2Format ),
			xGroupScale: getXGroupScale( orderedKeys, xScale ),
			xLineScale,
			xScale,
			yMax,
			yScale,
			yTickOffset: getYTickOffset( adjHeight, scale, yMax ),
			yFormat,
		};
	}

	render() {
		if ( ! this.props.data ) {
			return null; // TODO: improve messaging
		}
		return (
			<div
				className={ classNames( 'woocommerce-chart__container', this.props.className ) }
				style={ { height: this.props.height } }
			>
				<D3Base
					className={ classNames( this.props.className ) }
					data={ this.state.allData }
					drawChart={ this.drawChart }
					getParams={ this.getParams }
					type={ this.state.type }
					width={ this.state.width }
				/>
				<div className="tooltip" ref={ this.tooltipRef } />
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
	 * Relative viewpoirt height of the `svg`.
	 */
	height: PropTypes.number,
	/**
	 * Interval specification (hourly, daily, weekly etc.)
	 */
	interval: PropTypes.oneOf( [ 'hour', 'day', 'week', 'month', 'quater', 'year' ] ),
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
	 * The list of labels for this chart.
	 */
	orderedKeys: PropTypes.array,
	/**
	 * A datetime formatting string to format the title of the toolip, passed to d3TimeFormat.
	 */
	tooltipFormat: PropTypes.string,
	/**
	 * Chart type of either `line` or `bar`.
	 */
	type: PropTypes.oneOf( [ 'bar', 'line' ] ),
	/**
	 * Relative viewport width of the `svg`.
	 */
	width: PropTypes.number,
	/**
	 * A datetime formatting string, passed to d3TimeFormat.
	 */
	xFormat: PropTypes.string,
	/**
	 * A datetime formatting string, passed to d3TimeFormat.
	 */
	x2Format: PropTypes.string,
	/**
	 * A number formatting string, passed to d3Format.
	 */
	yFormat: PropTypes.string,
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
	tooltipFormat: '%Y-%m-%d',
	type: 'line',
	width: 600,
	xFormat: '%Y-%m-%d',
	x2Format: '',
	yFormat: '.3s',
};

export default D3Chart;
