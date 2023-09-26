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
import getSelectedChart from '../../../lib/get-selected-chart';
import ReportChart from '../../components/report-chart';
import ReportSummary from '../../components/report-summary';
import RevenueReportTable from './table';
import ReportFilters from '../../components/report-filters';
import { ReportDateTour } from '~/guided-tours/report-date-tour';

export default class RevenueReport extends Component {
	render() {
		const { path, query } = this.props;

		return (
			<Fragment>
				<ReportFilters
					query={ query }
					path={ path }
					report="revenue"
					filters={ filters }
					advancedFilters={ advancedFilters }
				/>
				<ReportSummary
					charts={ charts }
					endpoint="revenue"
					query={ query }
					selectedChart={ getSelectedChart( query.chart, charts ) }
					filters={ filters }
					advancedFilters={ advancedFilters }
				/>
				<ReportChart
					charts={ charts }
					endpoint="revenue"
					path={ path }
					query={ query }
					selectedChart={ getSelectedChart( query.chart, charts ) }
					filters={ filters }
					advancedFilters={ advancedFilters }
				/>
				<RevenueReportTable
					query={ query }
					filters={ filters }
					advancedFilters={ advancedFilters }
				/>
				<ReportDateTour
					optionName="woocommerce_revenue_report_date_tour_shown"
					headingText={ __(
						'Revenue is now reported from paid orders ✅',
						'woocommerce'
					) }
				/>
			</Fragment>
		);
	}
}

RevenueReport.propTypes = {
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
};
