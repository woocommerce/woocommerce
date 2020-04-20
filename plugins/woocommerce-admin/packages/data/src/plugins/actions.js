/**
 * External Dependencies
 */

import { apiFetch } from '@wordpress/data-controls';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal Dependencies
 */
import TYPES from './action-types';
import { WC_ADMIN_NAMESPACE } from '../constants';
import { pluginNames } from './constants';

export function updateActivePlugins( active ) {
	return {
		type: TYPES.UPDATE_ACTIVE_PLUGINS,
		active,
	};
}

export function updateInstalledPlugins( installed, added ) {
	return {
		type: TYPES.UPDATE_INSTALLED_PLUGINS,
		installed,
		added,
	};
}

export function setIsRequesting( selector, isRequesting ) {
	return {
		type: TYPES.SET_IS_REQUESTING,
		selector,
		isRequesting,
	};
}

export function setError( selector, error ) {
	return {
		type: TYPES.SET_ERROR,
		selector,
		error,
	};
}

export function updateIsJetpackConnected( jetpackConnection ) {
	return {
		type: TYPES.UPDATE_JETPACK_CONNECTION,
		jetpackConnection,
	};
}

export function updateJetpackConnectUrl( redirectUrl, jetpackConnectUrl ) {
	return {
		type: TYPES.UPDATE_JETPACK_CONNECT_URL,
		jetpackConnectUrl,
		redirectUrl,
	};
}

function getPluginErrorMessage( action, plugin ) {
	const pluginName = pluginNames[ plugin ] || plugin;
	switch ( action ) {
		case 'install':
			return sprintf(
				__(
					'There was an error installing %s. Please try again.',
					'woocommerce-admin'
				),
				pluginName
			);
		case 'connect':
			return sprintf(
				__(
					'There was an error connecting to %s. Please try again.',
					'woocommerce-admin'
				),
				pluginName
			);
		case 'activate':
		default:
			return sprintf(
				__(
					'There was an error activating %s. Please try again.',
					'woocommerce-admin'
				),
				pluginName
			);
	}
}

export function* installPlugin( plugin ) {
	yield setIsRequesting( 'installPlugin', true );

	try {
		const results = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/plugins/install`,
			method: 'POST',
			data: { plugin },
		} );

		if ( results && results.status === 'success' ) {
			yield updateInstalledPlugins( null, results.slug );
			return results;
		}

		throw new Error();
	} catch ( error ) {
		const errorMsg = getPluginErrorMessage( 'install', plugin );
		yield setError( 'installPlugin', errorMsg );
		return errorMsg;
	}
}

export function* activatePlugins( plugins ) {
	yield setIsRequesting( 'activatePlugins', true );

	try {
		const results = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/plugins/activate`,
			method: 'POST',
			data: { plugins: plugins.join( ',' ) },
		} );

		if ( results && results.status === 'success' ) {
			yield updateActivePlugins( results.activatedPlugins );
			return results;
		}

		throw new Error();
	} catch ( error ) {
		yield setError( 'activatePlugins', error );
		return plugins.map( ( plugin ) => {
			return getPluginErrorMessage( 'activate', plugin );
		} );
	}
}
