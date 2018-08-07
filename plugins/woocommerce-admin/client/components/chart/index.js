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
		} ) )
		.sort( ( a, b ) => b.total - a.total );
}

class Chart extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			data: null,
			orderedKeys: null,
			bodyWidth: document.getElementById( 'wpbody' ).getBoundingClientRect().width,
		};
		this.handleLegendToggle = this.handleLegendToggle.bind( this );
		this.handleLegendHover = this.handleLegendHover.bind( this );
		this.updateDimensions = this.updateDimensions.bind( this );
	}

	static getDerivedStateFromProps( nextProps, prevState ) {
		if ( prevState.data !== nextProps.data ) {
			return {
				data: nextProps.data,
				orderedKeys: getOrderedKeys( nextProps.data ).map( d => ( {
					...d,
					visible: true,
					focus: true,
				} ) ),
			};
		}

		return null;
	}

	shouldComponentUpdate( nextProps, nextState ) {
		return this.state.data !== null && this.state.data !== nextState.data;
	}

	componentDidMount() {
		window.addEventListener( 'resize', this.updateDimensions );
	}

	componentWillUnmount() {
		window.removeEventListener( 'resize', this.updateDimensions );
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
