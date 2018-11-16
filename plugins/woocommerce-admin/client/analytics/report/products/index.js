/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { ReportFilters } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { charts, filters } from './config';
import getSelectedChart from 'lib/get-selected-chart';
import ProductsReportTable from './table';
import ReportChart from 'analytics/components/report-chart';
import ReportSummary from 'analytics/components/report-summary';

export default class ProductsReport extends Component {
	render() {
		const { path, query } = this.props;

		const itemsLabel =
			'single_product' === query.filter && !! query.products
				? __( '%s variations', 'wc-admin' )
				: __( '%s products', 'wc-admin' );

		return (
			<Fragment>
				<ReportFilters query={ query } path={ path } filters={ filters } />
				<ReportSummary
					charts={ charts }
					endpoint="products"
					query={ query }
					selectedChart={ getSelectedChart( query.chart, charts ) }
				/>
				<ReportChart
					comparisonChart
					charts={ charts }
					endpoint="products"
					itemsLabel={ itemsLabel }
					path={ path }
					query={ query }
					selectedChart={ getSelectedChart( query.chart, charts ) }
				/>
				<ProductsReportTable query={ query } />
			</Fragment>
		);
	}
}

ProductsReport.propTypes = {
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
};
