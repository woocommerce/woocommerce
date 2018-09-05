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
import Chart from './index';
import dummyOrders from './test/fixtures/dummy';

class WidgetCharts extends Component {
	render() {
		return (
			<Card title={ __( 'Test Categories', 'wc-admin' ) }>
				<Chart
					data={ dummyOrders }
					tooltipFormat={ 'Hour of %H' }
					type={ 'bar' }
					xFormat={ '%H' }
				/>
			</Card>
		);
	}
}

export default WidgetCharts;
