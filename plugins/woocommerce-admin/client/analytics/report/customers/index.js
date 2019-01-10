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
		const { query, path } = this.props;
		const tableQuery = {
			orderby: 'date_registered',
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
				<CustomersReportTable query={ tableQuery } />
			</Fragment>
		);
	}
}

CustomersReport.propTypes = {
	query: PropTypes.object.isRequired,
};
