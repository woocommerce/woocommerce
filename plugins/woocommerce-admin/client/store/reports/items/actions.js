/** @format */

export default {
	setReportItems( endpoint, query, data, totalCount ) {
		return {
			type: 'SET_REPORT_ITEMS',
			endpoint,
			query: query || {},
			data,
			totalCount,
		};
	},
	setReportItemsError( endpoint, query ) {
		return {
			type: 'SET_REPORT_ITEMS_ERROR',
			endpoint,
			query: query || {},
		};
	},
};
