/** @format */

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
		Object.keys( profile ).forEach( id => {
			data[ id ] = getResource( getResourceName( resourceName, id ) ).data || profile[ id ];
		} );
		return data;
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
	query = {},
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = getResourceName( 'jetpack-connect-url', query );
	return requireResource( requirement, resourceName ).data;
};

const getJetpackConnectUrlError = getResource => ( query = {} ) => {
	const resourceName = getResourceName( 'jetpack-connect-url', query );
	return getResource( resourceName ).error;
};

const isGetJetpackConnectUrlRequesting = getResource => ( query = {} ) => {
	const resourceName = getResourceName( 'jetpack-connect-url', query );
	const { lastReceived, lastRequested } = getResource( resourceName );

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
	const activePlugins = getSetting(
		'onboarding',
		{},
		ob => requireResource( requirement, 'active-plugins' ).data || ob.activePlugins
	);

	// Avoid issuing API calls, since Jetpack is obviously not connected.
	if ( ! activePlugins.includes( 'jetpack' ) ) {
		return false;
	}
	const data = getSetting(
		'dataEndpoints',
		{},
		de => requireResource( requirement, 'jetpack-status' ).data || de.jetpackStatus
	);

	return ( data && data.isActive ) || false;
};

const getActivePlugins = ( getResource, requireResource ) => (
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = 'active-plugins';
	const data = requireResource( requirement, resourceName ).data || [];
	if ( ! data.length ) {
		return getSetting( 'onboarding', {}, ob => ob.activePlugins || [] );
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
