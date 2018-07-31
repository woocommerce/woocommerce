/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { Component, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Card from 'components/card';
import D3Chart from './charts';
import dummyOrders from './test/fixtures/dummy';
import Legend from './legend';

const WIDE_BREAKPOINT = 1130;

class WidgetCharts extends Component {
	constructor() {
		super( ...arguments );
		this.getOrderedKeys = this.getOrderedKeys.bind( this );
		this.handleLegendHover = this.handleLegendHover.bind( this );
		this.handleLegendToggle = this.handleLegendToggle.bind( this );
		this.updateDimensions = this.updateDimensions.bind( this );
		this.handleChange = this.handleChange.bind( this );
		this.getSomeOrders = this.getSomeOrders.bind( this );
		const products = [
			{
				key: 'date',
				selected: true,
			},
			{
				key: 'Cap',
				selected: true,
			},
			{
				key: 'T-Shirt',
				selected: true,
			},
			{
				key: 'Sunglasses',
				selected: true,
			},
			{
				key: 'Polo',
				selected: true,
			},
			{
				key: 'Hoodie',
				selected: true,
			},
		];
		const someOrders = this.getSomeOrders( products );
		this.state = {
			products,
			orderedKeys: this.getOrderedKeys( someOrders ).map( d => ( {
				...d,
				visible: true,
				focus: true,
			} ) ),
			someOrders,
			bodyWidth: document.getElementById( 'wpbody' ).getBoundingClientRect().width,
		};
	}

	getSomeOrders( products ) {
		return dummyOrders.map( d => {
			return Object.keys( d )
				.filter( key =>
					products
						.filter( k => k.selected )
						.map( k => k.key )
						.includes( key )
				)
				.reduce( ( accum, current ) => ( { ...accum, [ current ]: d[ current ] } ), {} );
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

	handleChange( event ) {
		const products = this.state.products.map( d => ( {
			...d,
			selected: d.key === event.target.id ? ! d.selected : d.selected,
		} ) );
		const someOrders = this.getSomeOrders( products );
		const orderedKeys = this.getOrderedKeys( someOrders ).map( d => ( {
			...d,
			visible: true,
			focus: true,
		} ) );
		this.setState( { products, orderedKeys, someOrders } );
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
		const legendDirection =
			this.state.orderedKeys.length <= 2 && this.state.bodyWidth > WIDE_BREAKPOINT
				? 'row'
				: 'column';
		const chartDirection =
			this.state.orderedKeys.length > 2 && this.state.bodyWidth > WIDE_BREAKPOINT
				? 'row'
				: 'column';
		const legend = (
			<Legend
				className={ 'woocommerce_dashboard__widget-legend' }
				data={ this.state.orderedKeys }
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
			<Fragment>
				<Card title={ __( 'Test Categories', 'wc-admin' ) }>
					<ul>
						{ this.state.products.map( d => (
							<li key={ d.key } style={ { display: 'inline', marginRight: '12px' } }>
								<label htmlFor={ d.key }>
									<input
										id={ d.key }
										type="checkbox"
										onChange={ this.handleChange }
										checked={ d.selected }
									/>{' '}
									{ d.key }
								</label>
							</li>
						) ) }
					</ul>
				</Card>
				<Card title={ __( 'Store Charts', 'wc-admin' ) }>
					<div className="woocommerce-dashboard__widget woocommerce-dashboard__widget-chart">
						<div className="woocommerce-dashboard__widget-chart-header">
							{ this.state.bodyWidth > WIDE_BREAKPOINT && legendDirection === 'row' && legend }
						</div>
						<div
							className={ classNames(
								'woocommerce-dashboard__widget-chart-body',
								`woocommerce-dashboard__widget-chart-body-${ chartDirection }`
							) }
						>
							{ this.state.bodyWidth > WIDE_BREAKPOINT && legendDirection === 'column' && legend }
							<D3Chart
								data={ this.state.someOrders }
								height={ 300 }
								margin={ margin }
								orderedKeys={ this.state.orderedKeys }
								type={ 'bar' }
								width={ chartDirection === 'row' ? 762 : 1082 }
							/>
						</div>
						{ this.state.bodyWidth < WIDE_BREAKPOINT && (
							<div className="woocommerce-dashboard__widget-chart-footer">{ legend }</div>
						) }
					</div>
				</Card>
			</Fragment>
		);
	}
}

export default WidgetCharts;
