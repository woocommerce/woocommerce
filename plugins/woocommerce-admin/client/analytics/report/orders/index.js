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
import OrdersReportTable from './table';
import ReportChart from 'analytics/components/report-chart';
import ReportSummary from 'analytics/components/report-summary';

export default class OrdersReport extends Component {
	getSelectedChart() {
		const { query } = this.props;
		const chart = find( charts, { key: query.chart } );
		if ( chart ) {
			return chart;
		}

		return charts[ 0 ];
	}

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
					selectedChart={ this.getSelectedChart() }
				/>
				<ReportChart
					charts={ charts }
					endpoint="orders"
					path={ path }
					query={ query }
					selectedChart={ this.getSelectedChart() }
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
