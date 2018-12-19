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
import { charts, filters, advancedFilters } from './config';
import DownloadsReportTable from './table';
import getSelectedChart from 'lib/get-selected-chart';
import ReportChart from 'analytics/components/report-chart';
import ReportSummary from 'analytics/components/report-summary';

export default class DownloadsReport extends Component {
	render() {
		const { query, path } = this.props;

		return (
			<Fragment>
				<ReportFilters
					query={ query }
					path={ path }
					filters={ filters }
					showDatePicker={ false }
					advancedFilters={ advancedFilters }
				/>
				<ReportSummary
					charts={ charts }
					endpoint="downloads"
					query={ query }
					selectedChart={ getSelectedChart( query.chart, charts ) }
				/>
				<ReportChart
					charts={ charts }
					endpoint="downloads"
					path={ path }
					query={ query }
					selectedChart={ getSelectedChart( query.chart, charts ) }
				/>
				<DownloadsReportTable query={ query } />
			</Fragment>
		);
	}
}

DownloadsReport.propTypes = {
	query: PropTypes.object.isRequired,
};
