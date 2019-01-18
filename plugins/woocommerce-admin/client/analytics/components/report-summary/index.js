/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { getDateParamsFromQuery } from '@woocommerce/date';
import { getNewPath } from '@woocommerce/navigation';
import { SummaryList, SummaryListPlaceholder, SummaryNumber } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { getSummaryNumbers } from 'store/reports/utils';
import ReportError from 'analytics/components/report-error';
import { calculateDelta, formatValue } from './utils';
import withSelect from 'wc-api/with-select';

/**
 * Component to render summary numbers in reports.
 */
export class ReportSummary extends Component {
	render() {
		const { charts, query, selectedChart, summaryData } = this.props;
		const { totals, isError, isRequesting } = summaryData;

		if ( isError ) {
			return <ReportError isError />;
		}

		if ( isRequesting ) {
			return <SummaryListPlaceholder numberOfItems={ charts.length } />;
		}

		const primaryTotals = totals.primary || {};
		const secondaryTotals = totals.secondary || {};
		const { compare } = getDateParamsFromQuery( query );

		const summaryNumbers = charts.map( chart => {
			const { key, label, type } = chart;
			const delta = calculateDelta( primaryTotals[ key ], secondaryTotals[ key ] );
			const href = getNewPath( { chart: key } );
			const prevValue = formatValue( type, secondaryTotals[ key ] );
			const isSelected = selectedChart.key === key;
			const value = formatValue( type, primaryTotals[ key ] );

			return (
				<SummaryNumber
					key={ key }
					delta={ delta }
					href={ href }
					label={ label }
					prevLabel={
						'previous_period' === compare
							? __( 'Previous Period:', 'wc-admin' )
							: __( 'Previous Year:', 'wc-admin' )
					}
					prevValue={ prevValue }
					selected={ isSelected }
					value={ value }
				/>
			);
		} );

		return <SummaryList>{ summaryNumbers }</SummaryList>;
	}
}

ReportSummary.propTypes = {
	/**
	 * Properties of all the charts available for that report.
	 */
	charts: PropTypes.array.isRequired,
	/**
	 * The endpoint to use in API calls to populate the Summary Numbers.
	 * For example, if `taxes` is provided, data will be fetched from the report
	 * `taxes` endpoint (ie: `/wc/v4/reports/taxes/stats`). If the provided endpoint
	 * doesn't exist, an error will be shown to the user with `ReportError`.
	 */
	endpoint: PropTypes.string.isRequired,
	/**
	 * The query string represented in object form.
	 */
	query: PropTypes.object.isRequired,
	/**
	 * Properties of the selected chart.
	 */
	selectedChart: PropTypes.shape( {
		/**
		 * Key of the selected chart.
		 */
		key: PropTypes.string.isRequired,
	} ).isRequired,
};

export default compose(
	withSelect( ( select, props ) => {
		const { query, endpoint } = props;
		const summaryData = getSummaryNumbers( endpoint, query, select );

		return {
			summaryData,
		};
	} )
)( ReportSummary );
