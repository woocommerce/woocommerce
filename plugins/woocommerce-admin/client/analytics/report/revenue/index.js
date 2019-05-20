/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { ReportFilters } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { charts } from './config';
import getSelectedChart from 'lib/get-selected-chart';
import ReportChart from 'analytics/components/report-chart';
import ReportSummary from 'analytics/components/report-summary';
import RevenueReportTable from './table';

export default class RevenueReport extends Component {
	render() {
		const { path, query } = this.props;

		return (
			<Fragment>
				<ReportFilters query={ query } path={ path } />
				<ReportSummary
					charts={ charts }
					endpoint="revenue"
					query={ query }
					selectedChart={ getSelectedChart( query.chart, charts ) }
				/>
				<ReportChart
					endpoint="revenue"
					path={ path }
					query={ query }
					selectedChart={ getSelectedChart( query.chart, charts ) }
				/>
				<RevenueReportTable query={ query } />
			</Fragment>
		);
	}
}

RevenueReport.propTypes = {
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
};
