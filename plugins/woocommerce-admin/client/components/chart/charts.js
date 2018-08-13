/** @format */

/**
 * External dependencies
 */
import { isEqual } from 'lodash';
import { Component, createRef } from '@wordpress/element';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { format as d3Format } from 'd3-format';
import { timeFormat as d3TimeFormat } from 'd3-time-format';
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

class D3Chart extends Component {
	constructor( props ) {
		super( props );
		this.getAllData = this.getAllData.bind( this );
		this.state = {
			allData: this.getAllData( props ),
			width: props.width,
		};
		this.tooltipRef = createRef();
	}

	componentDidUpdate( prevProps, prevState ) {
		const { width } = this.props;
		/* eslint-disable react/no-did-update-set-state */
		if ( width !== prevProps.width ) {
			this.setState( { width } );
		}
		const nextAllData = this.getAllData( this.props );
		if ( ! isEqual( [ ...nextAllData ].sort(), [ ...prevState.allData ].sort() ) ) {
			this.setState( { allData: nextAllData } );
		}
		/* eslint-enable react/no-did-update-set-state */
	}

	getAllData( props ) {
		const orderedKeys =
			props.orderedKeys || getOrderedKeys( props.data, getUniqueKeys( props.data ) );
		return [ ...props.data, ...orderedKeys ];
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
			tooltip: d3Select( this.tooltipRef.current ),
		} );
		drawAxis( g, adjParams );
		type === 'line' && drawLines( g, data, adjParams );
		type === 'bar' && drawBars( g, data, adjParams );

		return node;
	};

	getParams = node => {
		const { colorScheme, data, height, margin, orderedKeys, type, xFormat, yFormat } = this.props;
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
		const uniqueDates = getUniqueDates( lineData );
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
			<div
				className={ classNames( 'woocommerce-chart__container', this.props.className ) }
				style={ { height: this.props.height } }
			>
				<D3Base
					className={ classNames( this.props.className ) }
					data={ this.state.allData }
					drawChart={ this.drawChart }
					getParams={ this.getParams }
					width={ this.state.width }
				/>
				<div className="tooltip" ref={ this.tooltipRef } />
			</div>
		);
	}
}

D3Chart.propTypes = {
	colorScheme: PropTypes.func,
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

D3Chart.defaultProps = {
	data: [],
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

export default D3Chart;
