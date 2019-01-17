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
import DashboardCharts from './dashboard-charts';
import Header from 'header';
import Leaderboards from './leaderboards';
import { ReportFilters } from '@woocommerce/components';
import StorePerformance from './store-performance';

export default class Dashboard extends Component {
	render() {
		const { query, path } = this.props;
		return (
			<Fragment>
				<Header sections={ [ __( 'Dashboard', 'wc-admin' ) ] } />
				<ReportFilters query={ query } path={ path } />
				<StorePerformance query={ query } />
				<Leaderboards query={ query } />
				<DashboardCharts query={ query } path={ path } />
			</Fragment>
		);
	}
}
