/** @format */

/**
 * External dependencies
 */
import { isNil } from 'lodash';

/**
 * Internal dependencies
 */
import { DEFAULT_REQUIREMENT } from '../constants';
import { getResourceName } from '../utils';

const getProfileItems = ( getResource, requireResource ) => (
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = 'onboarding-profile';
	const ids = requireResource( requirement, resourceName ).data || [];

	if ( ! ids.length ) {
		return wcSettings.onboardingProfile;
	}

	const items = {};
	ids.forEach( id => {
		items[ id ] = getResource( getResourceName( resourceName, id ) ).data;
	} );

	return items;
};

const getProfileItemsError = getResource => () => {
	return getResource( 'onboarding-profile' ).error;
};

const isGetProfileItemsRequesting = getResource => () => {
	const { lastReceived, lastRequested } = getResource( 'onboarding-profile' );

	if ( isNil( lastRequested ) || isNil( lastReceived ) ) {
		return true;
	}

	return lastRequested > lastReceived;
};

export default {
	getProfileItems,
	getProfileItemsError,
	isGetProfileItemsRequesting,
};
