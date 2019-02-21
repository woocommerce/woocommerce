/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';

/**
 * WooCommerce dependencies
 */
import { ReportFilters } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { charts, filters } from './config';
import CategoriesReportTable from './table';
import getSelectedChart from 'lib/get-selected-chart';
import ReportChart from 'analytics/components/report-chart';
import ReportSummary from 'analytics/components/report-summary';

export default class CategoriesReport extends Component {
	getChartMeta() {
		const { query } = this.props;

		const isCategoryDetailsView = [ 'top_items', 'top_revenue', 'compare-categories' ].includes(
			query.filter
		);
		const mode = isCategoryDetailsView ? 'item-comparison' : 'time-comparison';
		const itemsLabel = __( '%d categories', 'wc-admin' );

		return {
			itemsLabel,
			mode,
		};
	}

	render() {
		const { query, path } = this.props;
		const { mode, itemsLabel } = this.getChartMeta();

		const chartQuery = {
			...query,
		};

		if ( 'item-comparison' === mode ) {
			chartQuery.segmentby = 'category';
		}

		return (
			<Fragment>
				<ReportFilters query={ query } path={ path } filters={ filters } />
				<ReportSummary
					charts={ charts }
					endpoint="products"
					query={ chartQuery }
					selectedChart={ getSelectedChart( query.chart, charts ) }
				/>
				<ReportChart
					filters={ filters }
					charts={ charts }
					mode={ mode }
					endpoint="products"
					path={ path }
					query={ chartQuery }
					itemsLabel={ itemsLabel }
					selectedChart={ getSelectedChart( query.chart, charts ) }
				/>
				<CategoriesReportTable query={ query } />
			</Fragment>
		);
	}
}

CategoriesReport.propTypes = {
	query: PropTypes.object.isRequired,
	path: PropTypes.string.isRequired,
};
