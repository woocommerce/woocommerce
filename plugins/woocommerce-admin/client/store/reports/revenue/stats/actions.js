/** @format */

export default {
	setReportRevenueStats( report, query ) {
		return {
			type: 'SET_REPORT_REVENUE_STATS',
			report,
			query: query || {},
		};
	},
	setReportRevenueStatsError( query ) {
		return {
			type: 'SET_REPORT_REVENUE_STATS_ERROR',
			query: query || {},
		};
	},
};
