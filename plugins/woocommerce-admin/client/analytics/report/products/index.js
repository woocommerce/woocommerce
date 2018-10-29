/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { filters } from './config';
import { ReportFilters } from '@woocommerce/components';
import ProductsReportChart from './chart';
import ProductsReportTable from './table';

export default class ProductsReport extends Component {
	render() {
		const { path, query } = this.props;

		return (
			<Fragment>
				<ReportFilters query={ query } path={ path } filters={ filters } />
				<ProductsReportChart query={ query } />
				<ProductsReportTable query={ query } />
			</Fragment>
		);
	}
}

ProductsReport.propTypes = {
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
};
