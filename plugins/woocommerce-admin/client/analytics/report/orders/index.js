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
import { advancedFilters, charts, filters } from './config';
import getSelectedChart from 'lib/get-selected-chart';
import OrdersReportTable from './table';
import ReportChart from 'analytics/components/report-chart';
import ReportSummary from 'analytics/components/report-summary';

export default class OrdersReport extends Component {
	render() {
		const { path, query } = this.props;

		return (
			<Fragment>
				<ReportFilters
					query={ query }
					path={ path }
					filters={ filters }
					advancedFilters={ advancedFilters }
				/>
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
				<OrdersReportTable query={ query } />
			</Fragment>
		);
	}
}

OrdersReport.propTypes = {
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
};
