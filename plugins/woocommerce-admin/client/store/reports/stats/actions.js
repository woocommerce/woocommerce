/** @format */

export default {
	setReportStats( endpoint, report, query, totalResults, totalPages ) {
		return {
			type: 'SET_REPORT_STATS',
			endpoint,
			report,
			totalResults,
			totalPages,
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
