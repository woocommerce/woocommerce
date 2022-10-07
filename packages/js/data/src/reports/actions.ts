/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';
import TYPES from './action-types';
import {
	Endpoint,
	ReportQueryParams,
	ReportStatQueryParams,
	ReportObject,
	ReportStatObject,
} from './types';

export function setReportItemsError(
	endpoint: Endpoint,
	query: ReportQueryParams,
	error: unknown
) {
	const resourceName = getResourceName( endpoint, query );

	return {
		type: TYPES.SET_ITEM_ERROR,
		resourceName,
		error,
	};
}

export function setReportItems(
	endpoint: Endpoint,
	query: ReportQueryParams,
	items: ReportObject
) {
	const resourceName = getResourceName( endpoint, query );

	return {
		type: TYPES.SET_REPORT_ITEMS,
		resourceName,
		items,
	};
}

export function setReportStats(
	endpoint: Endpoint,
	query: ReportStatQueryParams,
	stats: ReportStatObject
) {
	const resourceName = getResourceName( endpoint, query );

	return {
		type: TYPES.SET_REPORT_STATS,
		resourceName,
		stats,
	};
}

export function setReportStatsError(
	endpoint: Endpoint,
	query: ReportStatQueryParams,
	error: unknown
) {
	const resourceName = getResourceName( endpoint, query );

	return {
		type: TYPES.SET_STAT_ERROR,
		resourceName,
		error,
	};
}

export type Action = ReturnType<
	| typeof setReportItems
	| typeof setReportItemsError
	| typeof setReportStats
	| typeof setReportStatsError
>;
