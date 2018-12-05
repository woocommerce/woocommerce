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

const getOrders = ( getResource, requireResource ) => (
	query = {},
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = getResourceName( 'order-query', query );
	const ids = requireResource( requirement, resourceName ).data || [];
	const orders = ids.map( id => getResource( getResourceName( 'order', id ) ).data || {} );
	return orders;
};

const getOrdersTotalCount = ( getResource, requireResource ) => (
	query = {},
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = getResourceName( 'order-query', query );
	return requireResource( requirement, resourceName ).totalCount || 0;
};

const isGetOrdersRequesting = getResource => ( query = {} ) => {
	const resourceName = getResourceName( 'order-query', query );
	const { lastRequested, lastReceived } = getResource( resourceName );

	if ( isNil( lastRequested ) || isNil( lastReceived ) ) {
		return true;
	}

	return lastRequested > lastReceived;
};

const isGetOrdersError = getResource => ( query = {} ) => {
	const resourceName = getResourceName( 'order-query', query );
	return getResource( resourceName ).error;
};

export default {
	getOrders,
	getOrdersTotalCount,
	isGetOrdersRequesting,
	isGetOrdersError,
};
