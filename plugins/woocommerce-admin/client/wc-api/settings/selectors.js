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
import { analyticsSettings } from 'analytics/settings/config';

const getSettings = ( getResource, requireResource ) => (
	group,
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = `settings/${ group }`;
	const ids = requireResource( requirement, resourceName ).data || [];
	const settings = {};

	ids.forEach( id => {
		settings[ id ] = getResource( getResourceName( resourceName, id ) ).data;
	} );

	return settings;
};

const getSettingsError = getResource => group => {
	return getResource( `settings/${ group }` ).error;
};

const isGetSettingsRequesting = getResource => group => {
	const { lastReceived, lastRequested } = getResource( `settings/${ group }` );
	if ( isNil( lastRequested ) || isNil( lastReceived ) ) {
		return true;
	}

	// This selector is used by other "groups", so return early if not looking at wc_admin settings.
	if ( 'wc_admin' !== group ) {
		return lastRequested > lastReceived;
	}

	// Mutation operations for `wc_admin` settings update a different resource (batch endpoint) in fresh-data.
	// As such we must use lastReceived stamp from that resource to properly compare lastRequested to lastReceived.
	const settingName = analyticsSettings.length
		? analyticsSettings[ 0 ].name
		: 'woocommerce_actionable_order_statuses';
	const { lastReceived: lastMutationReceived } = getResource(
		getResourceName( 'settings/wc_admin', settingName )
	);

	// If we don't have a lastReceived on mutations, use the standard resource times.
	if ( isNil( lastMutationReceived ) ) {
		return lastRequested > lastReceived;
	}

	return lastRequested > lastMutationReceived;
};

export default {
	getSettings,
	getSettingsError,
	isGetSettingsRequesting,
};
