/**
 * Internal dependencies
 */
import { getReportTableQuery, getRequestQuery } from './utils';

export type Endpoint =
	| 'products'
	| 'variations'
	| 'orders'
	| 'categories'
	| 'taxes'
	| 'coupons'
	| 'stocks'
	| 'downloads'
	| 'customers';

export type ReportQueryParams = ReturnType< typeof getReportTableQuery >;

export type ReportState = {
	itemErrors: {
		[ resourceName: string ]: unknown;
	};
	items: {
		[ resourceName: string ]: ReportObject;
	};
	statErrors: {
		[ resourceName: string ]: unknown;
	};
	stats: {
		[ resourceName: string ]: ReportStatObject;
	};
};

export type ReportStatQueryParams = ReturnType< typeof getRequestQuery >;

export type ReportObject = {
	data: Record< string, unknown >;
	totalResults: number;
	totalPages: number;
};

export type ReportStatObject = {
	data: {
		totals: Record< string, unknown >;
		intervals: Array< {
			/** Type of interval. */
			interval?: 'day' | 'week' | 'month' | 'year';
			/** The date the report start, in the site's timezone. */
			date_start?: string;
			/** The date the report start, as GMT. */
			date_start_gmt?: string;
			/** The date the report end, in the site's timezone. */
			date_end?: string;
			/** The date the report end, as GMT. */
			date_end_gmt?: string;
			/** Interval subtotals. */
			subtotals?: Record< string, unknown >;
		} >;
	};
	totalResults: number;
	totalPages: number;
};
