/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';
import TYPES from './action-types';
import {
	ReportItemsEndpoint,
	ReportStatEndpoint,
	ReportQueryParams,
	ReportStatQueryParams,
	ReportItemObject,
	ReportStatObject,
} from './types';

export function setReportItemsError(
	endpoint: ReportItemsEndpoint,
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
	endpoint: ReportItemsEndpoint,
	query: ReportQueryParams,
	items: ReportItemObject
) {
	const resourceName = getResourceName( endpoint, query );

	return {
		type: TYPES.SET_REPORT_ITEMS,
		resourceName,
		items,
	};
}

export function setReportStats(
	endpoint: ReportStatEndpoint,
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
	endpoint: ReportStatEndpoint,
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
