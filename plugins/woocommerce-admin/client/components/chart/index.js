/** @format */
/**
 * External dependencies
 */
import classNames from 'classnames';
import { isEqual } from 'lodash';
import { Component, createRef } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import D3Chart from './charts';
import Legend from './legend';
import { gap, gaplarge } from 'stylesheets/abstracts/_variables.scss';

const WIDE_BREAKPOINT = 1100;

function getOrderedKeys( data ) {
	return [
		...new Set(
			data.reduce( ( accum, curr ) => {
				Object.keys( curr ).forEach( key => key !== 'date' && accum.push( key ) );
				return accum;
			}, [] )
		),
	]
		.map( key => ( {
			key,
			total: data.reduce( ( a, c ) => a + c[ key ], 0 ),
			visible: true,
			focus: true,
		} ) )
		.sort( ( a, b ) => b.total - a.total );
}

class Chart extends Component {
	constructor( props ) {
		super( props );
		this.chartRef = createRef();
		const wpBody = document.getElementById( 'wpbody' ).getBoundingClientRect().width;
		const wpWrap = document.getElementById( 'wpwrap' ).getBoundingClientRect().width;
		const calcGap = wpWrap > 782 ? gaplarge.match( /\d+/ )[ 0 ] : gap.match( /\d+/ )[ 0 ];
		this.state = {
			data: props.data,
			orderedKeys: getOrderedKeys( props.data ),
			visibleData: [ ...props.data ],
			width: wpBody - 2 * calcGap,
		};
		this.handleLegendToggle = this.handleLegendToggle.bind( this );
		this.handleLegendHover = this.handleLegendHover.bind( this );
		this.updateDimensions = this.updateDimensions.bind( this );
		this.getVisibleData = this.getVisibleData.bind( this );
	}

	componentDidUpdate( prevProps ) {
		const { data } = this.props;
		const orderedKeys = getOrderedKeys( data );
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

	handleLegendToggle( event ) {
		const { data } = this.props;
		const orderedKeys = this.state.orderedKeys.map( d => ( {
			...d,
			visible: d.key === event.target.id ? ! d.visible : d.visible,
		} ) );
		this.setState( {
			orderedKeys,
			visibleData: this.getVisibleData( data, orderedKeys ),
		} );
	}

	handleLegendHover( event ) {
		this.setState( {
			orderedKeys: this.state.orderedKeys.map( d => {
				const enterFocus = d.key === event.target.id ? true : false;
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
		const { orderedKeys, visibleData, width } = this.state;
		const legendDirection = orderedKeys.length <= 2 && width > WIDE_BREAKPOINT ? 'row' : 'column';
		const chartDirection = orderedKeys.length > 2 && width > WIDE_BREAKPOINT ? 'row' : 'column';
		const legend = (
			<Legend
				className={ 'woocommerce-chart__legend' }
				data={ orderedKeys }
				handleLegendHover={ this.handleLegendHover }
				handleLegendToggle={ this.handleLegendToggle }
				legendDirection={ legendDirection }
			/>
		);
		const margin = {
			bottom: 50,
			left: 50,
			right: 0,
			top: 0,
		};
		return (
			<div className="woocommerce-chart" ref={ this.chartRef }>
				<div className="woocommerce-chart__header">
					{ width > WIDE_BREAKPOINT && legendDirection === 'row' && legend }
				</div>
				<div
					className={ classNames(
						'woocommerce-chart__body',
						`woocommerce-chart__body-${ chartDirection }`
					) }
				>
					{ width > WIDE_BREAKPOINT && legendDirection === 'column' && legend }
					<D3Chart
						data={ visibleData }
						height={ 300 }
						margin={ margin }
						orderedKeys={ orderedKeys }
						type={ 'line' }
						width={ chartDirection === 'row' ? width - 320 : width }
					/>
				</div>
				{ width < WIDE_BREAKPOINT && <div className="woocommerce-chart__footer">{ legend }</div> }
			</div>
		);
	}
}

Chart.propTypes = {
	data: PropTypes.array.isRequired,
	title: PropTypes.string,
};

Chart.defaultProps = {
	data: [],
};

export default Chart;
