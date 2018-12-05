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

const getReportItems = ( getResource, requireResource ) => (
	type,
	query = {},
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = getResourceName( `report-items-query-${ type }`, query );
	const data = requireResource( requirement, resourceName ) || {};

	return data;
};

const isGetReportItemsRequesting = getResource => ( type, query = {} ) => {
	const resourceName = getResourceName( `report-items-query-${ type }`, query );
	const { lastRequested, lastReceived } = getResource( resourceName );

	if ( isNil( lastRequested ) ) {
		return false;
	}

	if ( isNil( lastReceived ) ) {
		return true;
	}

	return lastRequested > lastReceived;
};

const isGetReportItemsError = getResource => ( type, query = {} ) => {
	const resourceName = getResourceName( `report-items-query-${ type }`, query );
	return getResource( resourceName ).error;
};

export default {
	getReportItems,
	isGetReportItemsRequesting,
	isGetReportItemsError,
};
