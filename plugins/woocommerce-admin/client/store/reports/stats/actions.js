/** @format */

export default {
	setReportStats( endpoint, report, query ) {
		return {
			type: 'SET_REPORT_STATS',
			endpoint,
			report,
			query: query || {},
		};
	},
	setReportStatsError( endpoint, query ) {
		return {
			type: 'SET_REPORT_STATS_ERROR',
			endpoint,
			query: query || {},
		};
	},
};
