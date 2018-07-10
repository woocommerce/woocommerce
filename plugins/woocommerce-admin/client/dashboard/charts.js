/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';

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
		return (
			<Card title={ __( 'Store Charts', 'wc-admin' ) }>
				<div className="woocommerce-dashboard__widget">
					<D3Chart
						className="woocommerce-dashboard__widget-bar-chart"
						data={ dummyOrders }
						height={ 300 }
						type={ 'line' }
						width={ 1042 }
					/>
				</div>
				<div className="woocommerce-dashboard__widget">
					<D3Chart
						className="woocommerce-dashboard__widget-bar-chart"
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

export default WidgetCharts;
