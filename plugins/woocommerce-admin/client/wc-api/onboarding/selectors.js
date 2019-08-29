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
		return wcSettings.onboarding.profile;
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

const getJetpackConnectUrl = ( getResource, requireResource ) => (
	requirement = DEFAULT_REQUIREMENT
) => {
	return requireResource( requirement, 'jetpack-connect-url' ).data;
};

const getJetpackConnectUrlError = getResource => () => {
	return getResource( 'jetpack-connect-url' ).error;
};

const isGetJetpackConnectUrlRequesting = getResource => () => {
	const { lastReceived, lastRequested } = getResource( 'jetpack-connect-url' );

	if ( isNil( lastRequested ) || isNil( lastReceived ) ) {
		return true;
	}

	return lastRequested > lastReceived;
};

const getPluginInstallations = getResource => plugins => {
	const resourceName = 'plugin-install';

	const installations = {};
	plugins.forEach( plugin => {
		const data = getResource( getResourceName( resourceName, plugin ) ).data;
		if ( data ) {
			installations[ plugin ] = data;
		}
	} );

	return installations;
};

const isJetpackConnected = ( getResource, requireResource ) => (
	requirement = DEFAULT_REQUIREMENT
) => {
	const activePluginsData = requireResource( requirement, 'active-plugins' ).data || undefined;
	const activePlugins = ! activePluginsData
		? wcSettings.onboarding.activePlugins
		: activePluginsData;

	// Avoid issuing API calls, since Jetpack is obviously not connected.
	if ( ! activePlugins.includes( 'jetpack' ) ) {
		return false;
	}

	const data =
		requireResource( requirement, 'jetpack-status' ).data || wcSettings.dataEndpoints.jetpackStatus;
	return ( data && data.isActive ) || false;
};

const getActivePlugins = ( getResource, requireResource ) => (
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = 'active-plugins';
	const data = requireResource( requirement, resourceName ).data || [];
	if ( ! data.length ) {
		return wcSettings.onboarding.activePlugins;
	}

	return data;
};

const getActivePluginsError = getResource => () => {
	return getResource( 'active-plugins' ).error;
};

const isGetActivePluginsRequesting = getResource => () => {
	const { lastReceived, lastRequested } = getResource( 'active-plugins' );

	if ( isNil( lastRequested ) || isNil( lastReceived ) ) {
		return true;
	}

	return lastRequested > lastReceived;
};

const getPluginActivations = getResource => plugins => {
	const resourceName = 'plugin-activate';

	const activations = {};
	plugins.forEach( plugin => {
		const data = getResource( getResourceName( resourceName, plugin ) ).data;
		if ( data ) {
			activations[ plugin ] = data;
		}
	} );

	return activations;
};

const getPluginActivationErrors = getResource => plugins => {
	const resourceName = 'plugin-activate';

	const errors = {};
	plugins.forEach( plugin => {
		const error = getResource( getResourceName( resourceName, plugin ) ).error;
		if ( error ) {
			errors[ plugin ] = error;
		}
	} );

	return errors;
};

const getPluginInstallationErrors = getResource => plugins => {
	const resourceName = 'plugin-install';

	const errors = {};
	plugins.forEach( plugin => {
		const error = getResource( getResourceName( resourceName, plugin ) ).error;
		if ( error ) {
			errors[ plugin ] = error;
		}
	} );

	return errors;
};

const isPluginActivateRequesting = getResource => () => {
	const { lastReceived, lastRequested } = getResource( 'plugin-activate' );

	if ( ! isNil( lastRequested ) && isNil( lastReceived ) ) {
		return true;
	}

	return lastRequested > lastReceived;
};

const isPluginInstallRequesting = getResource => () => {
	const { lastReceived, lastRequested } = getResource( 'plugin-install' );

	if ( ! isNil( lastRequested ) && isNil( lastReceived ) ) {
		return true;
	}

	return lastRequested > lastReceived;
};

export default {
	getProfileItems,
	getProfileItemsError,
	isGetProfileItemsRequesting,
	getJetpackConnectUrl,
	getJetpackConnectUrlError,
	isGetJetpackConnectUrlRequesting,
	getActivePlugins,
	getActivePluginsError,
	isGetActivePluginsRequesting,
	getPluginActivations,
	getPluginInstallations,
	getPluginInstallationErrors,
	getPluginActivationErrors,
	isPluginActivateRequesting,
	isPluginInstallRequesting,
	isJetpackConnected,
};
