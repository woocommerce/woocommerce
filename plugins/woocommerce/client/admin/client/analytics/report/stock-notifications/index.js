/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { advancedFilters, charts, filters } from './config';
import StockNotificationsReportTable from './table';
import getSelectedChart from '../../../lib/get-selected-chart';
import ReportChart from '../../components/report-chart';
import ReportSummary from '../../components/report-summary';
import { ReportHeader } from '../../components/report-header';

class StockNotificationsReport extends Component {
	render() {
		const { isRequesting, query, path } = this.props;
		const selectedChart = getSelectedChart( query.chart, charts );
		/* translators: %d: number of sign-ups */
		const itemsLabel = __( '%d sign-ups', 'woocommerce' );

		return (
			<Fragment>
				<ReportHeader
					query={ query }
					path={ path }
					filters={ filters }
					advancedFilters={ advancedFilters }
					report="stock-notifications"
				/>
				<ReportSummary
					charts={ charts }
					endpoint="stock-notifications"
					query={ query }
					selectedChart={ selectedChart }
					filters={ filters }
					advancedFilters={ advancedFilters }
				/>
				<ReportChart
					charts={ charts }
					filters={ filters }
					advancedFilters={ advancedFilters }
					mode="time-comparison"
					endpoint="stock-notifications"
					path={ path }
					query={ query }
					isRequesting={ isRequesting }
					itemsLabel={ itemsLabel }
					selectedChart={ selectedChart }
				/>
				<StockNotificationsReportTable
					isRequesting={ isRequesting }
					query={ query }
					filters={ filters }
					advancedFilters={ advancedFilters }
				/>
			</Fragment>
		);
	}
}

StockNotificationsReport.propTypes = {
	query: PropTypes.object.isRequired,
};

export default StockNotificationsReport;
