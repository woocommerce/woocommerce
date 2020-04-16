/**
 * External dependencies
 */
import { isNil } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { getSetting } from '@woocommerce/wc-admin-settings';

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
	const { profile } = getSetting( 'onboarding', {} );

	if ( ! ids.length ) {
		const data = {};
		Object.keys( profile ).forEach( ( id ) => {
			data[ id ] =
				getResource( getResourceName( resourceName, id ) ).data ||
				profile[ id ];
		} );
		return data;
	}

	const items = {};
	ids.forEach( ( id ) => {
		items[ id ] = getResource( getResourceName( resourceName, id ) ).data;
	} );

	return items;
};

const getProfileItemsError = ( getResource ) => () => {
	return getResource( 'onboarding-profile' ).error;
};

const isGetProfileItemsRequesting = ( getResource ) => () => {
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
