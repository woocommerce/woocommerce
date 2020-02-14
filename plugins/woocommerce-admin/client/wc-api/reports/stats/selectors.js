/**
 * External dependencies
 */
import { isNil } from 'lodash';

/**
 * Internal dependencies
 */
import { getResourceName } from '../../utils';
import { DEFAULT_REQUIREMENT } from '../../constants';

const getReportStats = ( getResource, requireResource ) => (
	type,
	query = {},
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = getResourceName(
		`report-stats-query-${ type }`,
		query
	);
	const data = requireResource( requirement, resourceName ) || {};

	return data;
};

const getReportStatsError = ( getResource ) => ( type, query = {} ) => {
	const resourceName = getResourceName(
		`report-stats-query-${ type }`,
		query
	);
	return getResource( resourceName ).error;
};

const isReportStatsRequesting = ( getResource ) => ( type, query = {} ) => {
	const resourceName = getResourceName(
		`report-stats-query-${ type }`,
		query
	);
	const { lastRequested, lastReceived } = getResource( resourceName );

	if ( isNil( lastRequested ) || isNil( lastReceived ) ) {
		return true;
	}

	return lastRequested > lastReceived;
};

export default {
	getReportStats,
	getReportStatsError,
	isReportStatsRequesting,
};
