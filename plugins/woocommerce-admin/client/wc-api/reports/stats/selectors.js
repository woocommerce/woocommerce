/** @format */

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
	endpoint,
	query = {},
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = getResourceName( 'report-stats-query', { endpoint, query } );
	const data = requireResource( requirement, resourceName ) || {};

	return data;
};

const isReportStatsRequesting = getResource => ( endpoint, query = {} ) => {
	const resourceName = getResourceName( 'report-stats-query', { endpoint, query } );
	const { lastRequested, lastReceived } = getResource( resourceName );

	if ( isNil( lastRequested ) ) {
		return false;
	}

	if ( isNil( lastReceived ) ) {
		return true;
	}

	return lastRequested > lastReceived;
};

const isReportStatsError = getResource => ( endpoint, query = {} ) => {
	const resourceName = getResourceName( 'report-stats-query', { endpoint, query } );
	return getResource( resourceName ).error;
};

export default {
	getReportStats,
	isReportStatsRequesting,
	isReportStatsError,
};
