/**
 * External dependencies
 */
import { isNil } from 'lodash';

/**
 * Internal dependencies
 */
import { getResourceName } from '../../utils';
import { DEFAULT_REQUIREMENT } from '../../constants';

const getReportItems = ( getResource, requireResource ) => (
	type,
	query = {},
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = getResourceName(
		`report-items-query-${ type }`,
		query
	);
	return requireResource( requirement, resourceName ) || {};
};

const getReportItemsError = ( getResource ) => ( type, query = {} ) => {
	const resourceName = getResourceName(
		`report-items-query-${ type }`,
		query
	);
	return getResource( resourceName ).error;
};

const isReportItemsRequesting = ( getResource ) => ( type, query = {} ) => {
	const resourceName = getResourceName(
		`report-items-query-${ type }`,
		query
	);
	const { lastRequested, lastReceived } = getResource( resourceName );

	if ( isNil( lastRequested ) || isNil( lastReceived ) ) {
		return true;
	}

	return lastRequested > lastReceived;
};

export default {
	getReportItems,
	getReportItemsError,
	isReportItemsRequesting,
};
