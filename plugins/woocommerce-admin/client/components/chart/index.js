/** @format */
/**
 * External dependencies
 */
import classNames from 'classnames';
import { isEqual, partial } from 'lodash';
import { Component, createRef } from '@wordpress/element';
import { IconButton } from '@wordpress/components';
import PropTypes from 'prop-types';
import { interpolateViridis as d3InterpolateViridis } from 'd3-scale-chromatic';
import Gridicon from 'gridicons';

/**
 * Internal dependencies
 */
import D3Chart from './charts';
import Legend from './legend';
import { gap, gaplarge } from 'stylesheets/abstracts/_variables.scss';

const WIDE_BREAKPOINT = 1100;

function getOrderedKeys( props ) {
	const updatedKeys = [
		...new Set(
			props.data.reduce( ( accum, curr ) => {
				Object.keys( curr ).forEach( key => key !== 'date' && accum.push( key ) );
				return accum;
			}, [] )
		),
	].map( key => ( {
		key,
		total: props.data.reduce( ( a, c ) => a + c[ key ], 0 ),
		visible: true,
		focus: true,
	} ) );
	if ( props.layout === 'comparison' ) {
		updatedKeys.sort( ( a, b ) => b.total - a.total );
	}
	return updatedKeys;
}

/**
 * A chart container using d3, to display timeseries data with an interactive legend.
 */
class Chart extends Component {
	constructor( props ) {
		super( props );
		this.chartRef = createRef();
		const wpBody = document.getElementById( 'wpbody' ).getBoundingClientRect().width;
		const wpWrap = document.getElementById( 'wpwrap' ).getBoundingClientRect().width;
		const calcGap = wpWrap > 782 ? gaplarge.match( /\d+/ )[ 0 ] : gap.match( /\d+/ )[ 0 ];
		this.state = {
			data: props.data,
			orderedKeys: getOrderedKeys( props ),
			type: props.type,
			visibleData: [ ...props.data ],
			width: wpBody - 2 * calcGap,
		};
		this.handleTypeToggle = this.handleTypeToggle.bind( this );
		this.handleLegendToggle = this.handleLegendToggle.bind( this );
		this.handleLegendHover = this.handleLegendHover.bind( this );
		this.updateDimensions = this.updateDimensions.bind( this );
		this.getVisibleData = this.getVisibleData.bind( this );
	}

	componentDidUpdate( prevProps ) {
		const { data } = this.props;
		const orderedKeys = getOrderedKeys( this.props );
		if ( ! isEqual( [ ...data ].sort(), [ ...prevProps.data ].sort() ) ) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				orderedKeys: orderedKeys,
				visibleData: this.getVisibleData( data, orderedKeys ),
			} );
			/* eslint-enable react/no-did-update-set-state */
		}
	}

	componentDidMount() {
		window.addEventListener( 'resize', this.updateDimensions );
	}

	componentWillUnmount() {
		window.removeEventListener( 'resize', this.updateDimensions );
	}

	handleTypeToggle( type ) {
		if ( this.state.type !== type ) {
			this.setState( { type } );
		}
	}

	handleLegendToggle( event ) {
		const { data } = this.props;
		const orderedKeys = this.state.orderedKeys.map( d => ( {
			...d,
			visible: d.key === event.target.id ? ! d.visible : d.visible,
		} ) );
		const copyEvent = { ...event }; // can't pass a synthetic event into the hover handler
		this.setState(
			{
				orderedKeys,
				visibleData: this.getVisibleData( data, orderedKeys ),
			},
			() => {
				this.handleLegendHover( copyEvent );
			}
		);
	}

	handleLegendHover( event ) {
		const hoverTarget = this.state.orderedKeys.filter( d => d.key === event.target.id )[ 0 ];
		this.setState( {
			orderedKeys: this.state.orderedKeys.map( d => {
				let enterFocus = d.key === event.target.id ? true : false;
				enterFocus = ! hoverTarget.visible ? true : enterFocus;
				return {
					...d,
					focus: event.type === 'mouseleave' || event.type === 'blur' ? true : enterFocus,
				};
			} ),
		} );
	}

	updateDimensions() {
		this.setState( {
			width: this.chartRef.current.offsetWidth,
		} );
	}

	getVisibleData( data, orderedKeys ) {
		const visibleKeys = orderedKeys.filter( d => d.visible );
		return data.map( d => {
			const newRow = { date: d.date };
			visibleKeys.forEach( row => {
				newRow[ row.key ] = d[ row.key ];
			} );
			return newRow;
		} );
	}

	render() {
		const { orderedKeys, type, visibleData, width } = this.state;
		const { dateParser, layout, title, tooltipFormat, xFormat, x2Format, yFormat } = this.props;
		const legendDirection = layout === 'standard' && width > WIDE_BREAKPOINT ? 'row' : 'column';
		const chartDirection = layout === 'comparison' && width > WIDE_BREAKPOINT ? 'row' : 'column';
		const legend = (
			<Legend
				className={ 'woocommerce-chart__legend' }
				colorScheme={ d3InterpolateViridis }
				data={ orderedKeys }
				handleLegendHover={ this.handleLegendHover }
				handleLegendToggle={ this.handleLegendToggle }
				legendDirection={ legendDirection }
			/>
		);
		const margin = {
			bottom: 50,
			left: 80,
			right: 30,
			top: 0,
		};
		return (
			<div className="woocommerce-chart" ref={ this.chartRef }>
				<div className="woocommerce-chart__header">
					<span className="woocommerce-chart__title">{ title }</span>
					{ width > WIDE_BREAKPOINT && legendDirection === 'row' && legend }
					<div className="woocommerce-chart__types">
						<IconButton
							className={ classNames( 'woocommerce-chart__type-button', {
								'woocommerce-chart__type-button-selected': type === 'line',
							} ) }
							icon={ <Gridicon icon="line-graph" /> }
							onClick={ partial( this.handleTypeToggle, 'line' ) }
						/>
						<IconButton
							className={ classNames( 'woocommerce-chart__type-button', {
								'woocommerce-chart__type-button-selected': type === 'bar',
							} ) }
							icon={ <Gridicon icon="stats-alt" /> }
							onClick={ partial( this.handleTypeToggle, 'bar' ) }
						/>
					</div>
				</div>
				<div
					className={ classNames(
						'woocommerce-chart__body',
						`woocommerce-chart__body-${ chartDirection }`
					) }
				>
					{ width > WIDE_BREAKPOINT && legendDirection === 'column' && legend }
					<D3Chart
						colorScheme={ d3InterpolateViridis }
						data={ visibleData }
						dateParser={ dateParser }
						height={ 300 }
						margin={ margin }
						orderedKeys={ orderedKeys }
						tooltipFormat={ tooltipFormat }
						type={ type }
						width={ chartDirection === 'row' ? width - 320 : width }
						xFormat={ xFormat }
						x2Format={ x2Format }
						yFormat={ yFormat }
					/>
				</div>
				{ width < WIDE_BREAKPOINT && <div className="woocommerce-chart__footer">{ legend }</div> }
			</div>
		);
	}
}

Chart.propTypes = {
	/**
	 * An array of data.
	 */
	data: PropTypes.array.isRequired,
	/**
	 * Format to parse dates into d3 time format
	 */
	dateParser: PropTypes.string.isRequired,
	/**
	 * A datetime formatting string to format the title of the toolip, passed to d3TimeFormat.
	 */
	tooltipFormat: PropTypes.string,
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
	/**
	 * `standard` (default) legend layout in the header or `comparison` moves legend layout to the left
	 */
	layout: PropTypes.oneOf( [ 'standard', 'comparison' ] ),
	/**
	 * A title describing this chart.
	 */
	title: PropTypes.string,
	/**
	 * Chart type of either `line` or `bar`.
	 */
	type: PropTypes.oneOf( [ 'bar', 'line' ] ),
};

Chart.defaultProps = {
	data: [],
	dateParser: '%Y-%m-%dT%H:%M:%S',
	tooltipFormat: '%Y-%m-%d',
	xFormat: '%d',
	x2Format: '%b %Y',
	yFormat: '$.3s',
	layout: 'standard',
	type: 'line',
};

export default Chart;
