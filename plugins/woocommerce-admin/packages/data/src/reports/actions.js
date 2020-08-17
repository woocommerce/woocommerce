/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';
import TYPES from './action-types';

export function setReportItemsError( endpoint, query, error ) {
	const resourceName = getResourceName( endpoint, query );

	return {
		type: TYPES.SET_ITEM_ERROR,
		resourceName,
		error,
	};
}

export function setReportItems( endpoint, query, items ) {
	const resourceName = getResourceName( endpoint, query );

	return {
		type: TYPES.SET_REPORT_ITEMS,
		resourceName,
		items,
	};
}

export function setReportStats( endpoint, query, stats ) {
	const resourceName = getResourceName( endpoint, query );

	return {
		type: TYPES.SET_REPORT_STATS,
		resourceName,
		stats,
	};
}

export function setReportStatsError( endpoint, query, error ) {
	const resourceName = getResourceName( endpoint, query );

	return {
		type: TYPES.SET_STAT_ERROR,
		resourceName,
		error,
	};
}
