/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { showDatePicker, filters } from './config';
import StockReportTable from './table';
import ReportFilters from 'analytics/components/report-filters';

export default class StockReport extends Component {
	render() {
		const { query, path } = this.props;

		return (
			<Fragment>
				<ReportFilters
					query={ query }
					path={ path }
					showDatePicker={ showDatePicker }
					filters={ filters }
					report="stock"
				/>
				<StockReportTable query={ query } filters={ filters } />
			</Fragment>
		);
	}
}

StockReport.propTypes = {
	query: PropTypes.object.isRequired,
};
