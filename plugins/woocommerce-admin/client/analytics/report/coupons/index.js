/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { withDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { advancedFilters, charts, filters } from './config';
import CouponsReportTable from './table';
import getSelectedChart from '../../../lib/get-selected-chart';
import ReportChart from '../../components/report-chart';
import ReportSummary from '../../components/report-summary';
import ReportFilters from '../../components/report-filters';
import { STORE_KEY as CES_STORE_KEY } from '../../../customer-effort-score-tracks/data/constants';

class CouponsReport extends Component {
	getChartMeta() {
		const { query } = this.props;
		const isCompareView =
			query.filter === 'compare-coupons' &&
			query.coupons &&
			query.coupons.split( ',' ).length > 1;

		const mode = isCompareView ? 'item-comparison' : 'time-comparison';
		const itemsLabel = __( '%d coupons', 'woocommerce-admin' );

		return {
			itemsLabel,
			mode,
		};
	}

	render() {
		const {
			isRequesting,
			query,
			path,
			addCesSurveyForAnalytics,
		} = this.props;
		const { mode, itemsLabel } = this.getChartMeta();

		filters[ 0 ].filters.find(
			( item ) => item.value === 'compare-coupons'
		).settings.onClick = addCesSurveyForAnalytics;

		const chartQuery = {
			...query,
		};

		if ( mode === 'item-comparison' ) {
			chartQuery.segmentby = 'coupon';
		}

		return (
			<Fragment>
				<ReportFilters
					query={ query }
					path={ path }
					filters={ filters }
					advancedFilters={ advancedFilters }
					report="coupons"
				/>
				<ReportSummary
					charts={ charts }
					endpoint="coupons"
					isRequesting={ isRequesting }
					query={ chartQuery }
					selectedChart={ getSelectedChart( query.chart, charts ) }
					filters={ filters }
					advancedFilters={ advancedFilters }
				/>
				<ReportChart
					charts={ charts }
					filters={ filters }
					advancedFilters={ advancedFilters }
					mode={ mode }
					endpoint="coupons"
					path={ path }
					query={ chartQuery }
					isRequesting={ isRequesting }
					itemsLabel={ itemsLabel }
					selectedChart={ getSelectedChart( query.chart, charts ) }
				/>
				<CouponsReportTable
					isRequesting={ isRequesting }
					query={ query }
					filters={ filters }
					advancedFilters={ advancedFilters }
				/>
			</Fragment>
		);
	}
}

CouponsReport.propTypes = {
	query: PropTypes.object.isRequired,
};

export default withDispatch( ( dispatch ) => {
	const { addCesSurveyForAnalytics } = dispatch( CES_STORE_KEY );
	return { addCesSurveyForAnalytics };
} )( CouponsReport );
