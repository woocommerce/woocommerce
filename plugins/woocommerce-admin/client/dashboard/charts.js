/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { withAPIData } from '@wordpress/components';
import { Component, compose } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Card from 'components/card';
import D3Chart from 'components/d3/charts';
import { dummyOrders } from 'components/d3/charts/dummy';

class WidgetCharts extends Component {
	constructor() {
		super( ...arguments );
	}

	render() {
		// const { orders, products } = this.props;
		// const totalOrders = ( orders.data && orders.data.length ) || 0;
		// const totalProducts = ( products.data && products.data.length ) || 0;
		return (
			<Card title={ __( 'Store Charts', 'woo-dash' ) }>
				<div className="woo-dash__widget">
					<D3Chart
						className="woo-dash__widget-bar-chart"
						data={ dummyOrders }
						height={ 300 }
						type={ 'line' }
						width={ 1042 }
					/>
				</div>
				<div className="woo-dash__widget">
					<D3Chart
						className="woo-dash__widget-bar-chart"
						data={ dummyOrders }
						height={ 300 }
						type={ 'bar' }
						width={ 1042 }
					/>
				</div>
			</Card>
		);
	}
}

export default compose( [
	withAPIData( () => ( {
		orders: '/wc/v2/orders?status=completed',
		products: '/wc/v2/products',
	} ) ),
] )( WidgetCharts );
