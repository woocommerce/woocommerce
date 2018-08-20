/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';
import { Card } from '@woocommerce/components';
import ChartExample from 'components/chart/example';
import Header from 'layout/header';
import StorePerformance from './store-performance';
import TopSellingProducts from './top-selling-products';

export default class Dashboard extends Component {
	render() {
		return (
			<Fragment>
				<Header sections={ [ __( 'Dashboard', 'wc-admin' ) ] } />
				<StorePerformance />
				<ChartExample />
				<div className="woocommerce-dashboard__columns">
					<div>
						<TopSellingProducts />
					</div>
					<div>
						<Card title={ __( 'Column 2/1 Orders', 'wc-admin' ) } />
					</div>
				</div>
			</Fragment>
		);
	}
}
