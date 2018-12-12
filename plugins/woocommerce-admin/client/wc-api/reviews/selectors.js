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

const getReviews = ( getResource, requireResource ) => (
	query = {},
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = getResourceName( 'review-query', query );
	const ids = requireResource( requirement, resourceName ).data || [];
	const reviews = ids.map( id => getResource( getResourceName( 'review', id ) ).data || {} );
	return reviews;
};

const getReviewsTotalCount = ( getResource, requireResource ) => (
	query = {},
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = getResourceName( 'review-query', query );
	return requireResource( requirement, resourceName ).totalCount || 0;
};

const isGetReviewsRequesting = getResource => ( query = {} ) => {
	const resourceName = getResourceName( 'review-query', query );
	const { lastRequested, lastReceived } = getResource( resourceName );

	if ( isNil( lastRequested ) || isNil( lastReceived ) ) {
		return true;
	}

	return lastRequested > lastReceived;
};

const isGetReviewsError = getResource => ( query = {} ) => {
	const resourceName = getResourceName( 'review-query', query );
	return getResource( resourceName ).error;
};

export default {
	getReviews,
	getReviewsTotalCount,
	isGetReviewsRequesting,
	isGetReviewsError,
};
