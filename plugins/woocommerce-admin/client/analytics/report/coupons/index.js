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
import { charts, filters } from './config';
import CouponsReportTable from './table';
import getSelectedChart from 'lib/get-selected-chart';
import ReportChart from 'analytics/components/report-chart';
import ReportSummary from 'analytics/components/report-summary';

export default class CouponsReport extends Component {
	render() {
		const { query, path } = this.props;

		return (
			<Fragment>
				<ReportFilters query={ query } path={ path } filters={ filters } />
				<ReportSummary
					charts={ charts }
					endpoint="orders"
					query={ query }
					selectedChart={ getSelectedChart( query.chart, charts ) }
				/>
				<ReportChart
					charts={ charts }
					endpoint="orders"
					path={ path }
					query={ query }
					selectedChart={ getSelectedChart( query.chart, charts ) }
				/>
				<CouponsReportTable query={ query } />
			</Fragment>
		);
	}
}

CouponsReport.propTypes = {
	query: PropTypes.object.isRequired,
};
