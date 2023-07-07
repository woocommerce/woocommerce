/**
 * Internal dependencies
 */
import { octokitWithAuth } from '../../core/github/api';

export type PaginatedDataTotals = {
	// count is the running total of items processed
	count_items_processed: number;
	// count_items_available is the total number of items available to be processed
	count_items_available: number;
	// Any other data that needs to be tracked
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	[ key: string ]: any;
};

/**
 * Helper method for getting data from GitHub REST API in paginated format.
 *
 * This function is used to process multiple pages of GitHub data by keeping track of running totals.
 * The requirements `totals` are properties `count` and `total_number`. A processing function `processPage` is also passed to handle each page's data by updating the `totals` object.
 *
 * @param {Object}   totals         An object for keeping track of the total data.
 * @param {string}   endpoint       API endpoint
 * @param {Object}   requestOptions API request options
 * @param {Function} processPage    A function to handle returned data and update totals
 * @param            page           Page number to start from
 * @param            per_page       Number of items per page
 * @return {Object}                The updated totals object
 */
export const requestPaginatedData = async (
	totals: PaginatedDataTotals,
	endpoint: string,
	requestOptions: { [ key: string ]: string | number },
	processPage: (
		data: { [ key: string ]: number | string | Array< number > },
		totals: PaginatedDataTotals
	) => PaginatedDataTotals,
	page = 1,
	per_page = 50
) => {
	const { data } = await octokitWithAuth().request( endpoint, {
		...requestOptions,
		page,
		per_page,
	} );

	let resultingTotals = processPage( data, totals );

	const { total_count } = data;
	if ( total_count > resultingTotals.count_items_processed ) {
		resultingTotals = await requestPaginatedData(
			resultingTotals,
			endpoint,
			requestOptions,
			processPage,
			page + 1
		);
	}

	return resultingTotals;
};
