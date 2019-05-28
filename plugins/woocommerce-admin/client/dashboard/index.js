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
import CustomizableDashboard from './customizable';
import DashboardCharts from './dashboard-charts';
import Header from 'header';
import Leaderboards from './leaderboards';
import { ReportFilters } from '@woocommerce/components';
import StorePerformance from './store-performance';
import TaskList from './task-list';
import ProfileWizard from './profile-wizard';

export default class Dashboard extends Component {
	renderDashboardOutput() {
		const { query, path } = this.props;

		// @todo This should check a selector client side, with wcSettings.showProfiler as initial state.
		if ( window.wcAdminFeatures.onboarding && wcSettings.showProfiler ) {
			return <ProfileWizard query={ query } />;
		}

		// @todo This should be replaced by a check of tasks from the REST API response from #1897.
		const requiredTasksComplete = true;
		if ( window.wcAdminFeatures.onboarding && ! requiredTasksComplete ) {
			return <TaskList />;
		}

		// @todo When the customizable dashboard is ready to be launched, we can pull `CustomizableDashboard`'s render
		// method into `index.js`, and replace both this feature check, and the existing dashboard below.
		if ( window.wcAdminFeatures[ 'analytics-dashboard/customizable' ] ) {
			return <CustomizableDashboard query={ query } path={ path } />;
		}

		return (
			<Fragment>
				<ReportFilters query={ query } path={ path } />
				<StorePerformance query={ query } />
				<DashboardCharts query={ query } path={ path } />
				<Leaderboards query={ query } />
			</Fragment>
		);
	}

	render() {
		return (
			<Fragment>
				<Header sections={ [ __( 'Dashboard', 'woocommerce-admin' ) ] } />
				{ this.renderDashboardOutput() }
			</Fragment>
		);
	}
}
