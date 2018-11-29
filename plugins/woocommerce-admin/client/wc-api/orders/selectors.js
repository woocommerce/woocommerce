/** @format */
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

const isGetOrdersRequesting = getResource => ( query = {} ) => {
	const resourceName = getResourceName( 'order-query', query );
	const { lastRequested, lastReceived } = getResource( resourceName );
	return lastRequested && lastRequested > lastReceived;
};

const isGetOrdersError = getResource => ( query = {} ) => {
	const resourceName = getResourceName( 'order-query', query );
	return getResource( resourceName ).error;
};

export default {
	getOrders,
	isGetOrdersRequesting,
	isGetOrdersError,
};
