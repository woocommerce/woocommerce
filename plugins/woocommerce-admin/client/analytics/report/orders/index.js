/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { advancedFilters, charts, filters } from './config';
import getSelectedChart from '../../../lib/get-selected-chart';
import OrdersReportTable from './table';
import ReportChart from '../../components/report-chart';
import ReportSummary from '../../components/report-summary';
import ReportFilters from '../../components/report-filters';

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
					report="orders"
				/>
				<ReportSummary
					charts={ charts }
					endpoint="orders"
					query={ query }
					selectedChart={ getSelectedChart( query.chart, charts ) }
					filters={ filters }
					advancedFilters={ advancedFilters }
				/>
				<ReportChart
					charts={ charts }
					endpoint="orders"
					path={ path }
					query={ query }
					selectedChart={ getSelectedChart( query.chart, charts ) }
					filters={ filters }
					advancedFilters={ advancedFilters }
				/>
				<OrdersReportTable
					query={ query }
					filters={ filters }
					advancedFilters={ advancedFilters }
				/>
			</Fragment>
		);
	}
}

OrdersReport.propTypes = {
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
};
