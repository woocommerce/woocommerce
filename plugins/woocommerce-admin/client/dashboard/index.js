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
import Header from 'header';
import StorePerformance from './store-performance';
import TopSellingProducts from './top-selling-products';
import DashboardCharts from './dashboard-charts';
import { ReportFilters } from '@woocommerce/components';

export default class Dashboard extends Component {
	render() {
		const { query, path } = this.props;
		return (
			<Fragment>
				<Header sections={ [ __( 'Dashboard', 'wc-admin' ) ] } />
				<ReportFilters query={ query } path={ path } />
				<StorePerformance />
				<div className="woocommerce-dashboard__columns">
					<div>
						<TopSellingProducts />
					</div>
				</div>
				<DashboardCharts query={ query } path={ path } />
			</Fragment>
		);
	}
}
