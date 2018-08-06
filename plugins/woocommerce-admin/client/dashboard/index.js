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
import Header from 'layout/header';
import StorePerformance from './store-performance';
import Chart from 'components/chart';
import Card from 'components/card';

export default class Dashboard extends Component {
	render() {
		return (
			<Fragment>
				<Header sections={ [ __( 'Dashboard', 'wc-admin' ) ] } />
				<StorePerformance />
				<Chart />
				<div className="woocommerce-dashboard__columns">
					<div>
						<Card title={ __( 'Column 1/1', 'wc-admin' ) } />
						<Card title={ __( '1/2 Top Selling Products', 'wc-admin' ) } />
					</div>
					<div>
						<Card title={ __( 'Column 2/1 Orders', 'wc-admin' ) } />
						<Card title={ __( 'Column 2/2', 'wc-admin' ) } />
					</div>
				</div>
			</Fragment>
		);
	}
}
