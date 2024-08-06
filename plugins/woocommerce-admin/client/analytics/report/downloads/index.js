/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';
import { ReportSummary } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { charts, filters, advancedFilters } from './config';
import DownloadsReportTable from './table';
import getSelectedChart from '../../../lib/get-selected-chart';
import ReportChart from '../../components/report-chart';
import ReportFilters from '../../components/report-filters';

export default class DownloadsReport extends Component {
	render() {
		const { query, path } = this.props;

		return (
			<Fragment>
				<ReportFilters
					query={ query }
					path={ path }
					filters={ filters }
					advancedFilters={ advancedFilters }
					report="downloads"
				/>
				<ReportSummary
					charts={ charts }
					endpoint="downloads"
					query={ query }
					selectedChart={ getSelectedChart( query.chart, charts ) }
					filters={ filters }
					advancedFilters={ advancedFilters }
					recordEvent={ recordEvent }
				/>
				<ReportChart
					charts={ charts }
					endpoint="downloads"
					path={ path }
					query={ query }
					selectedChart={ getSelectedChart( query.chart, charts ) }
					filters={ filters }
					advancedFilters={ advancedFilters }
				/>
				<DownloadsReportTable
					query={ query }
					filters={ filters }
					advancedFilters={ advancedFilters }
				/>
			</Fragment>
		);
	}
}

DownloadsReport.propTypes = {
	query: PropTypes.object.isRequired,
};
