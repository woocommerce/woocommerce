/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { format as formatDate } from '@wordpress/date';
import { withSelect } from '@wordpress/data';
import { get, isEqual } from 'lodash';
import PropTypes from 'prop-types';
import { Chart } from '@woocommerce/components';
import {
	getReportChartData,
	getTooltipValueFormat,
	SETTINGS_STORE_NAME,
	REPORTS_STORE_NAME,
	isLeapYear,
} from '@woocommerce/data';
import {
	getAllowedIntervalsForQuery,
	getCurrentDates,
	getDateFormatsForInterval,
	getIntervalForQuery,
	getChartTypeForQuery,
	getPreviousDate,
} from '@woocommerce/date';
import { CurrencyContext } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import ReportError from '../report-error';
import {
	getChartMode,
	getSelectedFilter,
	createDateFormatter,
	dataHasLeapYear,
} from './utils';

/**
 * Component that renders the chart in reports.
 */
export class ReportChart extends Component {
	shouldComponentUpdate( nextProps ) {
		if (
			nextProps.isRequesting !== this.props.isRequesting ||
			nextProps.primaryData.isRequesting !==
				this.props.primaryData.isRequesting ||
			nextProps.secondaryData.isRequesting !==
				this.props.secondaryData.isRequesting ||
			! isEqual( nextProps.query, this.props.query )
		) {
			return true;
		}

		return false;
	}

	getItemChartData() {
		const { primaryData, selectedChart } = this.props;
		const chartData = primaryData.data.intervals.map( function (
			interval
		) {
			const intervalData = {};
			interval.subtotals.segments.forEach( function ( segment ) {
				if ( segment.segment_label ) {
					const label = intervalData[ segment.segment_label ]
						? segment.segment_label +
						  ' (#' +
						  segment.segment_id +
						  ')'
						: segment.segment_label;
					intervalData[ segment.segment_id ] = {
						label,
						value: segment.subtotals[ selectedChart.key ] || 0,
					};
				}
			} );
			return {
				date: formatDate( 'Y-m-d\\TH:i:s', interval.date_start ),
				...intervalData,
			};
		} );
		return chartData;
	}

	// hasLeapYear() {
	// 	const { primaryData, secondaryData } = this.props;

	// 	return (
	// 		dataHasLeapYear( primaryData ) || dataHasLeapYear( secondaryData )
	// 	);
	// }

	getTimeChartData() {
		const {
			query,
			primaryData,
			secondaryData,
			selectedChart,
			defaultDateRange,
		} = this.props;
		const currentInterval = getIntervalForQuery( query, defaultDateRange );
		const { primary, secondary } = getCurrentDates(
			query,
			defaultDateRange
		);
		// console.log(
		// 	'Primary',
		// 	primaryData.data.intervals,
		// 	currentInterval,
		// 	query,
		// 	defaultDateRange
		// );

		const primaryDataHasLeapYear = dataHasLeapYear( primaryData );
		const secondaryDataHasLeapYear = dataHasLeapYear( secondaryData );
		const primaryDataIntervals = [ ...primaryData.data.intervals ];
		const secondaryDataIntervals = [ ...secondaryData.data.intervals ];

		// In time series, we need to add placeholder data and adjust subtotals in non-leap years
		// to account for the extra day in leap years to ensure an accurate comparison.
		if ( currentInterval === 'day' && secondaryDataHasLeapYear ) {
			// Append data for the missing leap day so everything else is shifted to the right correctly.
		} // Assume secondaryDataHasLeapYear is true if the secondary dataset includes a leap year

		const chartData = [];

		for ( let index = 0; index < primaryDataIntervals.length; index++ ) {
			const interval = primaryDataIntervals[ index ];

			const secondaryDate = getPreviousDate(
				interval.date_start,
				primary.after,
				secondary.after,
				query.compare,
				currentInterval
			);

			let today = formatDate( 'Y-m-d\\TH:i:s', interval.date_start );
			let primaryLabel = `${ primary.label } (${ primary.range })`;
			let primaryLabelDate = interval.date_start;
			let primaryValue = interval.subtotals[ selectedChart.key ] || 0;

			const secondaryInterval = secondaryDataIntervals[ index ];
			let secondaryLabel = `${ secondary.label } (${ secondary.range })`;
			let secondaryLabelDate = secondaryDate.format(
				'YYYY-MM-DD HH:mm:ss'
			);
			let secondaryValue =
				( secondaryInterval &&
					secondaryInterval.subtotals[ selectedChart.key ] ) ||
				0;

			if ( currentInterval === 'day' ) {
				if ( primaryDataHasLeapYear ) {
					const date = new Date( interval.date_start );
					if (
						isLeapYear( date.getFullYear() ) &&
						date.getMonth() === 1 &&
						date.getDate() === 29
					) {
						secondaryLabel = __(
							'Leap day placeholder',
							'woocommerce'
						);
						secondaryLabelDate = null;
						secondaryValue = 0;

						// Append data for the missing leap day so everything else is shifted to the right correctly.
						secondaryDataIntervals.splice(
							index,
							0,
							secondaryDataIntervals[ index ]
						);
					}
				} else if ( secondaryDataHasLeapYear ) {
					const date = new Date( secondaryInterval.date_start );
					console.log('YES!! eap', date, secondaryInterval )
					if (
						isLeapYear( date.getFullYear() ) &&
						date.getMonth() === 1 &&
						date.getDate() === 28
					) {
						primaryLabel = __(
							'Leap day placeholder',
							'woocommerce'
						);
						primaryLabelDate = null;
						primaryValue = 0;
						// today = '2021-02-29T00:00:00';

						// Append data for the missing leap day so everything else is shifted to the right correctly.
						console.log('Before', primaryDataIntervals.length)
						primaryDataIntervals.splice(
							index + 1,
							0,
							primaryDataIntervals[ index ]
						);

						console.log(primaryDataIntervals.length)
					}
				}
			}

			chartData.push( {
				date: today,
				primary: {
					label: primaryLabel,
					labelDate: primaryLabelDate,
					value: primaryValue,
				},
				secondary: {
					label: secondaryLabel,
					labelDate: secondaryLabelDate,
					value: secondaryValue,
				},
			} );
		}

		console.log('Chart data', chartData)

		// const chartData = primaryData.data.intervals.map( function (
		// 	interval,
		// 	index
		// ) {
		// 	const secondaryDate = getPreviousDate(
		// 		interval.date_start,
		// 		primary.after,
		// 		secondary.after,
		// 		query.compare,
		// 		currentInterval
		// 	);

		// 	const secondaryInterval = secondaryDataIntervals[ index ];
		// 	let secondaryLabel = `${ secondary.label } (${ secondary.range })`;
		// 	let secondaryLabelDate = secondaryDate.format(
		// 		'YYYY-MM-DD HH:mm:ss'
		// 	);
		// 	let secondaryValue =
		// 		( secondaryInterval &&
		// 			secondaryInterval.subtotals[ selectedChart.key ] ) ||
		// 		0;

		// 	if ( currentInterval === 'day' && primaryDataHasLeapYear ) {
		// 		const date = new Date( interval.date_start );
		// 		if (
		// 			isLeapYear( date.getFullYear() ) &&
		// 			date.getMonth() === 1 &&
		// 			date.getDate() === 29
		// 		) {
		// 			secondaryLabel = __(
		// 				'Leap day placeholder',
		// 				'woocommerce'
		// 			);
		// 			secondaryLabelDate = null;
		// 			secondaryValue = 0;

		// 			// Append data for the missing leap day so everything else is shifted to the right correctly.
		// 			secondaryDataIntervals.splice(
		// 				index,
		// 				0,
		// 				secondaryDataIntervals[ index ]
		// 			);
		// 		}
		// 	}

		// 	return {
		// 		date: formatDate( 'Y-m-d\\TH:i:s', interval.date_start ),
		// 		primary: {
		// 			label: `${ primary.label } (${ primary.range })`,
		// 			labelDate: interval.date_start,
		// 			value: interval.subtotals[ selectedChart.key ] || 0,
		// 		},
		// 		secondary: {
		// 			label: secondaryLabel,
		// 			labelDate: secondaryLabelDate,
		// 			value: secondaryValue,
		// 		},
		// 	};
		// } );

		return chartData;
	}

	getTimeChartTotals() {
		const { primaryData, secondaryData, selectedChart } = this.props;

		return {
			primary: get(
				primaryData,
				[ 'data', 'totals', selectedChart.key ],
				null
			),
			secondary: get(
				secondaryData,
				[ 'data', 'totals', selectedChart.key ],
				null
			),
		};
	}

	renderChart( mode, isRequesting, chartData, legendTotals ) {
		const {
			emptySearchResults,
			filterParam,
			interactiveLegend,
			itemsLabel,
			legendPosition,
			path,
			query,
			selectedChart,
			showHeaderControls,
			primaryData,
			defaultDateRange,
		} = this.props;
		const currentInterval = getIntervalForQuery( query, defaultDateRange );
		const allowedIntervals = getAllowedIntervalsForQuery(
			query,
			defaultDateRange
		);
		const formats = getDateFormatsForInterval(
			currentInterval,
			primaryData.data.intervals.length,
			{ type: 'php' }
		);
		const emptyMessage = emptySearchResults
			? __( 'No data for the current search', 'woocommerce' )
			: __( 'No data for the selected date range', 'woocommerce' );
		const { formatAmount, getCurrencyConfig } = this.context;
		return (
			<Chart
				allowedIntervals={ allowedIntervals }
				data={ chartData }
				dateParser={ '%Y-%m-%dT%H:%M:%S' }
				emptyMessage={ emptyMessage }
				filterParam={ filterParam }
				interactiveLegend={ interactiveLegend }
				interval={ currentInterval }
				isRequesting={ isRequesting }
				itemsLabel={ itemsLabel }
				legendPosition={ legendPosition }
				legendTotals={ legendTotals }
				mode={ mode }
				path={ path }
				query={ query }
				screenReaderFormat={ createDateFormatter(
					formats.screenReaderFormat
				) }
				showHeaderControls={ showHeaderControls }
				title={ selectedChart.label }
				tooltipLabelFormat={ createDateFormatter(
					formats.tooltipLabelFormat
				) }
				tooltipTitle={
					( mode === 'time-comparison' && selectedChart.label ) ||
					null
				}
				tooltipValueFormat={ getTooltipValueFormat(
					selectedChart.type,
					formatAmount
				) }
				chartType={ getChartTypeForQuery( query ) }
				valueType={ selectedChart.type }
				xFormat={ createDateFormatter( formats.xFormat ) }
				x2Format={ createDateFormatter( formats.x2Format ) }
				currency={ getCurrencyConfig() }
			/>
		);
	}

	renderItemComparison() {
		const { isRequesting, primaryData } = this.props;

		if ( primaryData.isError ) {
			return <ReportError />;
		}

		const isChartRequesting = isRequesting || primaryData.isRequesting;
		const chartData = this.getItemChartData();

		return this.renderChart(
			'item-comparison',
			isChartRequesting,
			chartData
		);
	}

	renderTimeComparison() {
		const { isRequesting, primaryData, secondaryData } = this.props;

		if ( ! primaryData || primaryData.isError || secondaryData.isError ) {
			return <ReportError />;
		}

		const isChartRequesting =
			isRequesting ||
			primaryData.isRequesting ||
			secondaryData.isRequesting;
		const chartData = this.getTimeChartData();
		const legendTotals = this.getTimeChartTotals();

		return this.renderChart(
			'time-comparison',
			isChartRequesting,
			chartData,
			legendTotals
		);
	}

	render() {
		const { mode } = this.props;
		if ( mode === 'item-comparison' ) {
			return this.renderItemComparison();
		}
		return this.renderTimeComparison();
	}
}

ReportChart.contextType = CurrencyContext;

ReportChart.propTypes = {
	/**
	 * Filters available for that report.
	 */
	filters: PropTypes.array,
	/**
	 * Whether there is an API call running.
	 */
	isRequesting: PropTypes.bool,
	/**
	 * Label describing the legend items.
	 */
	itemsLabel: PropTypes.string,
	/**
	 * Allows specifying properties different from the `endpoint` that will be used
	 * to limit the items when there is an active search.
	 */
	limitProperties: PropTypes.array,
	/**
	 * `items-comparison` (default) or `time-comparison`, this is used to generate correct
	 * ARIA properties.
	 */
	mode: PropTypes.string,
	/**
	 * Current path
	 */
	path: PropTypes.string.isRequired,
	/**
	 * Primary data to display in the chart.
	 */
	primaryData: PropTypes.object,
	/**
	 * The query string represented in object form.
	 */
	query: PropTypes.object.isRequired,
	/**
	 * Secondary data to display in the chart.
	 */
	secondaryData: PropTypes.object,
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
};

ReportChart.defaultProps = {
	isRequesting: false,
	primaryData: {
		data: {
			intervals: [],
		},
		isError: false,
		isRequesting: false,
	},
	secondaryData: {
		data: {
			intervals: [],
		},
		isError: false,
		isRequesting: false,
	},
};

export default compose(
	withSelect( ( select, props ) => {
		const {
			charts,
			endpoint,
			filters,
			isRequesting,
			limitProperties,
			query,
			advancedFilters,
		} = props;
		const limitBy = limitProperties || [ endpoint ];
		const selectedFilter = getSelectedFilter( filters, query );
		const filterParam = get( selectedFilter, [ 'settings', 'param' ] );
		const chartMode =
			props.mode ||
			getChartMode( selectedFilter, query ) ||
			'time-comparison';
		const { woocommerce_default_date_range: defaultDateRange } = select(
			SETTINGS_STORE_NAME
		).getSetting( 'wc_admin', 'wcAdminSettings' );

		/* eslint @wordpress/no-unused-vars-before-return: "off" */
		const reportStoreSelector = select( REPORTS_STORE_NAME );

		const newProps = {
			mode: chartMode,
			filterParam,
			defaultDateRange,
		};

		if ( isRequesting ) {
			return newProps;
		}

		const hasLimitByParam = limitBy.some(
			( item ) => query[ item ] && query[ item ].length
		);

		if ( query.search && ! hasLimitByParam ) {
			return {
				...newProps,
				emptySearchResults: true,
			};
		}

		const fields = charts && charts.map( ( chart ) => chart.key );

		const primaryData = getReportChartData( {
			endpoint,
			dataType: 'primary',
			query,
			selector: reportStoreSelector,
			limitBy,
			filters,
			advancedFilters,
			defaultDateRange,
			fields,
		} );

		if ( chartMode === 'item-comparison' ) {
			return {
				...newProps,
				primaryData,
			};
		}

		const secondaryData = getReportChartData( {
			endpoint,
			dataType: 'secondary',
			query,
			selector: reportStoreSelector,
			limitBy,
			filters,
			advancedFilters,
			defaultDateRange,
			fields,
		} );

		// console.log(
		// 	'Hehehe',
		// 	query,
		// 	defaultDateRange,
		// 	filters,
		// 	reportStoreSelector,
		// 	fields,
		// 	primaryData,
		// 	secondaryData
		// );

		return {
			...newProps,
			primaryData,
			secondaryData,
		};
	} )
)( ReportChart );
