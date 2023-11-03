/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';
import {
	ReportState,
	ReportItemsEndpoint,
	ReportStatEndpoint,
	ReportQueryParams,
	ReportStatQueryParams,
	ReportItemObjectInfer,
	ReportStatObjectInfer,
} from './types';

const EMPTY_OBJECT = {} as const;

export const getReportItemsError = (
	state: ReportState,
	endpoint: ReportItemsEndpoint,
	query: ReportQueryParams
) => {
	const resourceName = getResourceName( endpoint, query );
	return state.itemErrors[ resourceName ] || false;
};

export const getReportItems = < T >(
	state: ReportState,
	endpoint: ReportItemsEndpoint,
	query: ReportQueryParams
): ReportItemObjectInfer< T > => {
	const resourceName = getResourceName( endpoint, query );
	return (
		( state.items[ resourceName ] as ReportItemObjectInfer< T > ) ||
		EMPTY_OBJECT
	);
};

export const getReportStats = < T >(
	state: ReportState,
	endpoint: ReportStatEndpoint,
	query: ReportStatQueryParams
): ReportStatObjectInfer< T > => {
	const resourceName = getResourceName( endpoint, query );
	return (
		( state.stats[ resourceName ] as ReportStatObjectInfer< T > ) ||
		EMPTY_OBJECT
	);
};

export const getReportStatsError = (
	state: ReportState,
	endpoint: ReportStatEndpoint,
	query: ReportStatQueryParams
) => {
	const resourceName = getResourceName( endpoint, query );
	return state.statErrors[ resourceName ] || false;
};
