/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';
import {
	ReportState,
	Endpoint,
	ReportQueryParams,
	ReportStatQueryParams,
} from './types';

const EMPTY_OBJECT = {};

export const getReportItemsError = (
	state: ReportState,
	endpoint: Endpoint,
	query: ReportQueryParams
) => {
	const resourceName = getResourceName( endpoint, query );
	return state.itemErrors[ resourceName ] || false;
};

export const getReportItems = (
	state: ReportState,
	endpoint: Endpoint,
	query: ReportQueryParams
) => {
	const resourceName = getResourceName( endpoint, query );
	return state.items[ resourceName ] || EMPTY_OBJECT;
};

export const getReportStats = (
	state: ReportState,
	endpoint: Endpoint,
	query: ReportStatQueryParams
) => {
	const resourceName = getResourceName( endpoint, query );
	return state.stats[ resourceName ] || EMPTY_OBJECT;
};

export const getReportStatsError = (
	state: ReportState,
	endpoint: Endpoint,
	query: ReportStatQueryParams
) => {
	const resourceName = getResourceName( endpoint, query );
	return state.statErrors[ resourceName ] || false;
};
