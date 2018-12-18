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

const getCustomers = ( getResource, requireResource ) => (
	query = {},
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = getResourceName( 'customers-query', query );
	const ids = requireResource( requirement, resourceName ).data || [];
	return ids.map( id => getResource( getResourceName( 'customer', id ) ).data || {} );
};

const getCustomersError = getResource => ( query = {} ) => {
	const resourceName = getResourceName( 'customers-query', query );
	return getResource( resourceName ).error;
};

const isGetCustomersRequesting = getResource => ( query = {} ) => {
	const resourceName = getResourceName( 'customers-query', query );
	const { lastRequested, lastReceived } = getResource( resourceName );

	if ( isNil( lastRequested ) || isNil( lastReceived ) ) {
		return true;
	}

	return lastRequested > lastReceived;
};

export default {
	getCustomers,
	getCustomersError,
	isGetCustomersRequesting,
};
