/** @format */
/**
 * External dependencies
 */
import { isNil } from 'lodash';

/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';
import { DEFAULT_REQUIREMENT } from '../constants';

const getImportStatus = ( getResource, requireResource ) => (
	timestamp,
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = getResourceName( 'import-status', timestamp );
	return requireResource( requirement, resourceName ).data || {};
};

const isGetImportStatusRequesting = getResource => timestamp => {
	const resourceName = getResourceName( 'import-status', timestamp );
	const { lastRequested, lastReceived } = getResource( resourceName );

	if ( isNil( lastRequested ) || isNil( lastReceived ) ) {
		return true;
	}

	return lastRequested > lastReceived;
};

const getImportTotals = ( getResource, requireResource ) => (
	query = {},
	timestamp,
	requirement = DEFAULT_REQUIREMENT
) => {
	const identifier = { ...query, timestamp };
	const resourceName = getResourceName( 'import-totals', identifier );
	return requireResource( requirement, resourceName ).data || {};
};

export default {
	getImportStatus,
	isGetImportStatusRequesting,
	getImportTotals,
};
