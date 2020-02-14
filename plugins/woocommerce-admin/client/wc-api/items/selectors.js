/**
 * External dependencies
 */
import { isNil } from 'lodash';

/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';
import { DEFAULT_REQUIREMENT } from '../constants';

const getItems = ( getResource, requireResource ) => (
	type,
	query = {},
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = getResourceName( `items-query-${ type }`, query );
	const ids = requireResource( requirement, resourceName ).data || [];
	const items = new Map();
	ids.forEach( ( id ) => {
		items.set(
			id,
			getResource( getResourceName( `items-query-${ type }-item`, id ) )
				.data
		);
	} );
	return items;
};

const getItemsTotalCount = ( getResource ) => ( type, query = {} ) => {
	const resourceName = getResourceName( `items-query-${ type }`, query );
	return getResource( resourceName ).totalCount || 0;
};

const getItemsError = ( getResource ) => ( type, query = {} ) => {
	const resourceName = getResourceName( `items-query-${ type }`, query );
	return getResource( resourceName ).error;
};

const isGetItemsRequesting = ( getResource ) => ( type, query = {} ) => {
	const resourceName = getResourceName( `items-query-${ type }`, query );
	const { lastRequested, lastReceived } = getResource( resourceName );

	if ( isNil( lastRequested ) || isNil( lastReceived ) ) {
		return true;
	}

	return lastRequested > lastReceived;
};

export default {
	getItems,
	getItemsError,
	getItemsTotalCount,
	isGetItemsRequesting,
};
