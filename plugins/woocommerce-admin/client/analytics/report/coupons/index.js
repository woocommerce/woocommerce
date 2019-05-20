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
import CouponsReportTable from './table';
import getSelectedChart from 'lib/get-selected-chart';
import ReportChart from 'analytics/components/report-chart';
import ReportSummary from 'analytics/components/report-summary';

export default class CouponsReport extends Component {
	getChartMeta() {
		const { query } = this.props;
		const isCompareView =
			'compare-coupons' === query.filter && query.coupons && query.coupons.split( ',' ).length > 1;

		const mode = isCompareView ? 'item-comparison' : 'time-comparison';
		const itemsLabel = __( '%d coupons', 'woocommerce-admin' );

		return {
			itemsLabel,
			mode,
		};
	}

	render() {
		const { isRequesting, query, path } = this.props;
		const { mode, itemsLabel } = this.getChartMeta();

		const chartQuery = {
			...query,
		};

		if ( 'item-comparison' === mode ) {
			chartQuery.segmentby = 'coupon';
		}

		return (
			<Fragment>
				<ReportFilters query={ query } path={ path } filters={ filters } />
				<ReportSummary
					charts={ charts }
					endpoint="coupons"
					isRequesting={ isRequesting }
					query={ chartQuery }
					selectedChart={ getSelectedChart( query.chart, charts ) }
					filters={ filters }
				/>
				<ReportChart
					filters={ filters }
					mode={ mode }
					endpoint="coupons"
					path={ path }
					query={ chartQuery }
					isRequesting={ isRequesting }
					itemsLabel={ itemsLabel }
					selectedChart={ getSelectedChart( query.chart, charts ) }
				/>
				<CouponsReportTable isRequesting={ isRequesting } query={ query } filters={ filters } />
			</Fragment>
		);
	}
}

CouponsReport.propTypes = {
	query: PropTypes.object.isRequired,
};
