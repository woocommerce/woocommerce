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

const getProducts = ( getResource, requireResource ) => (
	query = {},
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = getResourceName( 'product-query', query );
	const ids = requireResource( requirement, resourceName ).data || [];
	const products = ids.map( id => getResource( getResourceName( 'product', id ) ).data || {} );
	return products;
};

const getProductsError = getResource => ( query = {} ) => {
	const resourceName = getResourceName( 'product-query', query );
	return getResource( resourceName ).error;
};

const isGetProductsRequesting = getResource => ( query = {} ) => {
	const resourceName = getResourceName( 'product-query', query );
	const { lastRequested, lastReceived } = getResource( resourceName );

	if ( isNil( lastRequested ) || isNil( lastReceived ) ) {
		return true;
	}

	return lastRequested > lastReceived;
};

export default {
	getProducts,
	getProductsError,
	isGetProductsRequesting,
};
