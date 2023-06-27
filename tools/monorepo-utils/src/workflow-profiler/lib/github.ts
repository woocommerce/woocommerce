/**
 * Internal dependencies
 */
import { octokitWithAuth } from '../../core/github/api';

export type PaginatedDataTotals = {
	count: number;
	total_count: number;
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	[ key: string ]: any;
};

/**
 * Helper method for getting data from GitHub REST API in paginated format.
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
	if ( total_count > resultingTotals.count ) {
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
