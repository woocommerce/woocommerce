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

const getCategories = ( getResource, requireResource ) => (
	query = {},
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = getResourceName( 'category-query', query );
	const ids = requireResource( requirement, resourceName ).data || [];
	const categories = ids.reduce(
		( acc, id ) => ( {
			...acc,
			[ id ]: getResource( getResourceName( 'category', id ) ).data || {},
		} ),
		{}
	);
	return categories;
};

const getCategoriesTotalCount = getResource => ( query = {} ) => {
	const resourceName = getResourceName( 'category-query', query );
	return getResource( resourceName ).totalCount || 0;
};

const getCategoriesError = getResource => ( query = {} ) => {
	const resourceName = getResourceName( 'category-query', query );
	return getResource( resourceName ).error;
};

const isGetCategoriesRequesting = getResource => ( query = {} ) => {
	const resourceName = getResourceName( 'category-query', query );
	const { lastRequested, lastReceived } = getResource( resourceName );

	if ( isNil( lastRequested ) || isNil( lastReceived ) ) {
		return true;
	}

	return lastRequested > lastReceived;
};

export default {
	getCategories,
	getCategoriesError,
	getCategoriesTotalCount,
	isGetCategoriesRequesting,
};
