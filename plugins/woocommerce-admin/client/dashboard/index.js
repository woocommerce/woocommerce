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
import Card from '../components/card';

export default class Dashboard extends Component {
	render() {
		return (
			<Fragment>
				<Header sections={ [ __( 'Dashboard', 'wc-admin' ) ] } />
				<StorePerformance />
				<Chart />
				<div className="woocommerce-dashboard__columns">
					<div className="woocommerce-dashboard__column">
						<Card title={ __( 'Top Selling Products', 'wc-admin' ) } />
					</div>
					<div className="woocommerce-dashboard__column">
						<Card title={ __( 'Orders', 'wc-admin' ) } />
					</div>
				</div>
			</Fragment>
		);
	}
}
