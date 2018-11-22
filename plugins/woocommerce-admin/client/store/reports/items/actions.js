/** @format */

export default {
	setReportItems( endpoint, items, query ) {
		return {
			type: 'SET_REPORT_ITEMS',
			endpoint,
			items,
			query: query || {},
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
