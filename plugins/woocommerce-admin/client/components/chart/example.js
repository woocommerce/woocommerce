/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Card from 'components/card';
import Chart from './index';
import dummyOrders from './test/fixtures/dummy';

class WidgetCharts extends Component {
	constructor() {
		super( ...arguments );
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
			someOrders,
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

	handleChange( event ) {
		const products = this.state.products.map( d => ( {
			...d,
			selected: d.key === event.target.id ? ! d.selected : d.selected,
		} ) );
		const someOrders = this.getSomeOrders( products );
		this.setState( { products, someOrders } );
	}

	render() {
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
					<Chart data={ this.state.someOrders } title="Example Chart" layout="comparison" />
				</Card>
			</Fragment>
		);
	}
}

export default WidgetCharts;
