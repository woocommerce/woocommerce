/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { ReportFilters } from '@woocommerce/components';
import { filters, advancedFilters } from './config';
import OrdersReportChart from './chart';
import OrdersReportTable from './table';

export default class OrdersReport extends Component {
	render() {
		const { path, query } = this.props;

		return (
			<Fragment>
				<ReportFilters
					query={ query }
					path={ path }
					filters={ filters }
					advancedConfig={ advancedFilters }
				/>
				<OrdersReportChart query={ query } />
				<OrdersReportTable query={ query } />
			</Fragment>
		);
	}
}

OrdersReport.propTypes = {
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
};
