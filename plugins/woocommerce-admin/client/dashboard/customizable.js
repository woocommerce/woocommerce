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
import Leaderboards from './leaderboards';
import { ReportFilters, H } from '@woocommerce/components';
import StorePerformance from './store-performance';

// @todo Replace dashboard-charts, leaderboards, and store-performance sections as neccessary with customizable equivalents.
export default class CustomizableDashboard extends Component {
	render() {
		const { query, path } = this.props;
		return (
			<Fragment>
				<H>{ __( 'Customizable Dashboard', 'woocommerce-admin' ) }</H>
				<ReportFilters query={ query } path={ path } />
				<StorePerformance query={ query } />
				<DashboardCharts query={ query } path={ path } />
				<Leaderboards query={ query } />
			</Fragment>
		);
	}
}
