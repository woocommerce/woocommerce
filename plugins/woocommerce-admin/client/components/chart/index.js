/** @format */
/**
 * External dependencies
 */
import classNames from 'classnames';
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import D3Chart from './charts';
import Legend from './legend';

const WIDE_BREAKPOINT = 1130;

class Chart extends Component {
	constructor( props ) {
		super( props );
		this.getOrderedKeys = this.getOrderedKeys.bind( this );
		this.state = {
			data: props.data,
			orderedKeys: this.getOrderedKeys( props.data ).map( d => ( {
				...d,
				visible: true,
				focus: true,
			} ) ),
			bodyWidth: document.getElementById( 'wpbody' ).getBoundingClientRect().width,
		};
		this.handleLegendToggle = this.handleLegendToggle.bind( this );
		this.handleLegendHover = this.handleLegendHover.bind( this );
		this.updateDimensions = this.updateDimensions.bind( this );
	}

	// @TODO change this to `getDerivedStateFromProps` in React 16.4
	UNSAFE_componentWillReceiveProps( nextProps ) {
		this.setState( {
			data: nextProps.data,
			orderedKeys: this.getOrderedKeys( nextProps.data ).map( d => ( {
				...d,
				visible: true,
				focus: true,
			} ) ),
		} );
	}

	componentDidMount() {
		window.addEventListener( 'resize', this.updateDimensions );
	}

	componentWillUnmount() {
		window.removeEventListener( 'resize', this.updateDimensions );
	}

	getOrderedKeys( data ) {
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
			} ) )
			.sort( ( a, b ) => b.total - a.total );
	}

	handleLegendToggle( event ) {
		this.setState( {
			orderedKeys: this.state.orderedKeys.map( d => ( {
				...d,
				visible: d.key === event.target.id ? ! d.visible : d.visible,
			} ) ),
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
			bodyWidth: document.getElementById( 'wpbody' ).getBoundingClientRect().width,
		} );
	}

	render() {
		const { bodyWidth, data, orderedKeys } = this.state;
		const legendDirection =
			orderedKeys.length <= 2 && bodyWidth > WIDE_BREAKPOINT ? 'row' : 'column';
		const chartDirection = orderedKeys.length > 2 && bodyWidth > WIDE_BREAKPOINT ? 'row' : 'column';
		const legend = (
			<Legend
				className={ 'woocommerce_dashboard__widget-legend' }
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
			<div className="woocommerce-dashboard__widget woocommerce-dashboard__widget-chart">
				<div className="woocommerce-dashboard__widget-chart-header">
					{ bodyWidth > WIDE_BREAKPOINT && legendDirection === 'row' && legend }
				</div>
				<div
					className={ classNames(
						'woocommerce-dashboard__widget-chart-body',
						`woocommerce-dashboard__widget-chart-body-${ chartDirection }`
					) }
				>
					{ bodyWidth > WIDE_BREAKPOINT && legendDirection === 'column' && legend }
					<D3Chart
						data={ data }
						height={ 300 }
						margin={ margin }
						orderedKeys={ orderedKeys }
						type={ 'bar' }
						width={ chartDirection === 'row' ? 762 : 1082 }
					/>
				</div>
				{ bodyWidth < WIDE_BREAKPOINT && (
					<div className="woocommerce-dashboard__widget-chart-footer">{ legend }</div>
				) }
			</div>
		);
	}
}

Chart.propTypes = {
	data: PropTypes.array.isRequired,
	title: PropTypes.string,
};

export default Chart;
