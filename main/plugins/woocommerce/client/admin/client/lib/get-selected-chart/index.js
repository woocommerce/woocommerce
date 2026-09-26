/**
 * External dependencies
 */
import { find } from 'lodash';

/**
 * Takes a chart name returns the configuration for that chart from and array
 * of charts. If the chart is not found it will return the first chart.
 *
 * @param {string} chartName - the name of the chart to get configuration for
 * @param {Array}  charts    - list of charts for a particular report
 * @return {Object} - chart configuration object
 */
export default function getSelectedChart( chartName, charts = [] ) {
	const chart = find( charts, { key: chartName } );
	if ( chart ) {
		return chart;
	}
	return charts[ 0 ];
}
