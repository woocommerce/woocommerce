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
import { filters, advancedFilters } from './config';
import CustomersReportTable from './table';

export default class CustomersReport extends Component {
	render() {
		const { isRequesting, query, path } = this.props;
		const tableQuery = {
			orderby: 'date_last_active',
			order: 'desc',
			...query,
		};

		return (
			<Fragment>
				<ReportFilters
					query={ query }
					path={ path }
					filters={ filters }
					showDatePicker={ false }
					advancedFilters={ advancedFilters }
				/>
				<CustomersReportTable
					isRequesting={ isRequesting }
					query={ tableQuery }
					filters={ filters }
					advancedFilters={ advancedFilters }
				/>
			</Fragment>
		);
	}
}

CustomersReport.propTypes = {
	query: PropTypes.object.isRequired,
};
