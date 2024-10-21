/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import PropTypes from 'prop-types';
import { getNewPath } from '@woocommerce/navigation';
import {
	AnalyticsError,
	SummaryList,
	SummaryListPlaceholder,
	SummaryNumber,
} from '@woocommerce/components';
import { calculateDelta, formatValue } from '@woocommerce/number';
import { getSummaryNumbers, SETTINGS_STORE_NAME } from '@woocommerce/data';
import { getDateParamsFromQuery } from '@woocommerce/date';
import { recordEvent } from '@woocommerce/tracks';
import { CurrencyContext } from '@woocommerce/currency';

/**
 * Internal dependencies
 */

/**
 * Component to render summary numbers in reports.
 */
export class ReportSummary extends Component {
	formatVal( val, type ) {
		const { formatAmount, getCurrencyConfig } = this.context;
		return type === 'currency'
			? formatAmount( val )
			: formatValue( getCurrencyConfig(), type, val );
	}

	getValues( key, type ) {
		const { emptySearchResults, summaryData } = this.props;
		const { totals } = summaryData;

		const primaryTotal = totals.primary ? totals.primary[ key ] : 0;
		const secondaryTotal = totals.secondary ? totals.secondary[ key ] : 0;

		const primaryValue = emptySearchResults ? 0 : primaryTotal;
		const secondaryValue = emptySearchResults ? 0 : secondaryTotal;

		return {
			delta: calculateDelta( primaryValue, secondaryValue ),
			prevValue: this.formatVal( secondaryValue, type ),
			value: this.formatVal( primaryValue, type ),
		};
	}

	render() {
		const {
			charts,
			query,
			selectedChart,
			summaryData,
			endpoint,
			report,
			defaultDateRange,
		} = this.props;
		const { isError, isRequesting } = summaryData;

		if ( isError ) {
			return <AnalyticsError />;
		}

		if ( isRequesting ) {
			return <SummaryListPlaceholder numberOfItems={ charts.length } />;
		}

		const { compare } = getDateParamsFromQuery( query, defaultDateRange );

		const renderSummaryNumbers = ( { onToggle } ) =>
			charts.map( ( chart ) => {
				const {
					key,
					order,
					orderby,
					label,
					type,
					isReverseTrend,
					labelTooltipText,
				} = chart;
				const newPath = { chart: key };
				if ( orderby ) {
					newPath.orderby = orderby;
				}
				if ( order ) {
					newPath.order = order;
				}
				const href = getNewPath( newPath );
				const isSelected = selectedChart.key === key;
				const { delta, prevValue, value } = this.getValues( key, type );

				return (
					<SummaryNumber
						key={ key }
						delta={ delta }
						href={ href }
						label={ label }
						reverseTrend={ isReverseTrend }
						prevLabel={
							compare === 'previous_period'
								? __( 'Previous period:', 'woocommerce' )
								: __( 'Previous year:', 'woocommerce' )
						}
						prevValue={ prevValue }
						selected={ isSelected }
						value={ value }
						labelTooltipText={ labelTooltipText }
						onLinkClickCallback={ () => {
							// Wider than a certain breakpoint, there is no dropdown so avoid calling onToggle.
							if ( onToggle ) {
								onToggle();
							}
							recordEvent( 'analytics_chart_tab_click', {
								report: report || endpoint,
								key,
							} );
						} }
					/>
				);
			} );

		return <SummaryList>{ renderSummaryNumbers }</SummaryList>;
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
	 * `taxes` endpoint (ie: `/wc-analytics/reports/taxes/stats`). If the provided endpoint
	 * doesn't exist, an error will be shown to the user with `AnalyticsError`.
	 */
	endpoint: PropTypes.string.isRequired,
	/**
	 * Allows specifying properties different from the `endpoint` that will be used
	 * to limit the items when there is an active search.
	 */
	limitProperties: PropTypes.array,
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
		/**
		 * Chart label.
		 */
		label: PropTypes.string.isRequired,
		/**
		 * Order query argument.
		 */
		order: PropTypes.oneOf( [ 'asc', 'desc' ] ),
		/**
		 * Order by query argument.
		 */
		orderby: PropTypes.string,
		/**
		 * Number type for formatting.
		 */
		type: PropTypes.oneOf( [ 'average', 'number', 'currency' ] ).isRequired,
	} ).isRequired,
	/**
	 * Data to display in the SummaryNumbers.
	 */
	summaryData: PropTypes.object,
	/**
	 * Report name, if different than the endpoint.
	 */
	report: PropTypes.string,
};

ReportSummary.defaultProps = {
	summaryData: {
		totals: {
			primary: {},
			secondary: {},
		},
		isError: false,
	},
};

ReportSummary.contextType = CurrencyContext;

export default compose(
	withSelect( ( select, props ) => {
		const {
			charts,
			endpoint,
			limitProperties,
			query,
			filters,
			advancedFilters,
		} = props;
		const limitBy = limitProperties || [ endpoint ];

		const hasLimitByParam = limitBy.some(
			( item ) => query[ item ] && query[ item ].length
		);

		if ( query.search && ! hasLimitByParam ) {
			return {
				emptySearchResults: true,
			};
		}

		const fields = charts && charts.map( ( chart ) => chart.key );

		const { woocommerce_default_date_range: defaultDateRange } = select(
			SETTINGS_STORE_NAME
		).getSetting( 'wc_admin', 'wcAdminSettings' );

		const summaryData = getSummaryNumbers( {
			endpoint,
			query,
			select,
			limitBy,
			filters,
			advancedFilters,
			defaultDateRange,
			fields,
		} );

		return {
			summaryData,
			defaultDateRange,
		};
	} )
)( ReportSummary );
