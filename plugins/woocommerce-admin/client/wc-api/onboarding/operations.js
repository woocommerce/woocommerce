/** @format */

/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';
import { JETPACK_NAMESPACE, WC_ADMIN_NAMESPACE } from '../constants';
import { pluginNames } from './constants';

function read( resourceNames, fetch = apiFetch ) {
	return [
		...readActivePlugins( resourceNames, fetch ),
		...readProfileItems( resourceNames, fetch ),
		...readJetpackStatus( resourceNames, fetch ),
		...readJetpackConnectUrl( resourceNames, fetch ),
	];
}

function update( resourceNames, data, fetch = apiFetch ) {
	return [
		...activatePlugins( resourceNames, data, fetch ),
		...installPlugins( resourceNames, data, fetch ),
		...updateProfileItems( resourceNames, data, fetch ),
	];
}

function readProfileItems( resourceNames, fetch ) {
	const resourceName = 'onboarding-profile';

	if ( resourceNames.includes( resourceName ) ) {
		const url = WC_ADMIN_NAMESPACE + '/onboarding/profile';

		return [
			fetch( { path: url } )
				.then( profileItemsToResources )
				.catch( error => {
					return { [ resourceName ]: { error: String( error.message ) } };
				} ),
		];
	}

	return [];
}

function updateProfileItems( resourceNames, data, fetch ) {
	const resourceName = 'onboarding-profile';

	if ( resourceNames.includes( resourceName ) ) {
		const url = WC_ADMIN_NAMESPACE + '/onboarding/profile';

		return [
			fetch( {
				path: url,
				method: 'POST',
				data: data[ resourceName ],
			} )
				.then( profileItemToResource.bind( null, data[ resourceName ] ) )
				.catch( error => {
					return { [ resourceName ]: { error } };
				} ),
		];
	}

	return [];
}

function profileItemsToResources( items ) {
	const resourceName = 'onboarding-profile';

	const itemKeys = Object.keys( items );

	const resources = {};
	itemKeys.forEach( key => {
		const item = items[ key ];
		resources[ getResourceName( resourceName, key ) ] = { data: item };
	} );

	return {
		[ resourceName ]: {
			data: itemKeys,
		},
		...resources,
	};
}

function profileItemToResource( items ) {
	const resourceName = 'onboarding-profile';

	const resources = {};
	Object.keys( items ).forEach( key => {
		const item = items[ key ];
		resources[ getResourceName( resourceName, key ) ] = { data: item };
	} );

	return resources;
}

function readActivePlugins( resourceNames, fetch ) {
	const resourceName = 'active-plugins';
	if ( resourceNames.includes( resourceName ) ) {
		const url = WC_ADMIN_NAMESPACE + '/onboarding/plugins/active';

		return [
			fetch( { path: url } )
				.then( activePluginsToResources )
				.catch( error => {
					return { [ resourceName ]: { error: String( error.message ) } };
				} ),
		];
	}

	return [];
}

function activePluginsToResources( items ) {
	const { plugins } = items;
	const resourceName = 'active-plugins';
	return {
		[ resourceName ]: {
			data: plugins,
		},
	};
}

function activatePlugins( resourceNames, data, fetch ) {
	const resourceName = 'plugin-activate';
	if ( resourceNames.includes( resourceName ) ) {
		const plugins = data[ resourceName ];
		const url = WC_ADMIN_NAMESPACE + '/onboarding/plugins/activate';
		return [
			fetch( {
				path: url,
				method: 'POST',
				data: {
					plugins: plugins.join( ',' ),
				},
			} )
				.then( response => activatePluginToResource( response, plugins ) )
				.catch( error => {
					const resources = { [ resourceName ]: { error } };
					Object.keys( plugins ).forEach( key => {
						const pluginError = { ...error };
						const item = plugins[ key ];
						pluginError.message = getPluginErrorMessage( 'activate', item );
						resources[ getResourceName( resourceName, item ) ] = { error: pluginError };
					} );
					return resources;
				} ),
		];
	}

	return [];
}

function activatePluginToResource( response, items ) {
	const resourceName = 'plugin-activate';

	const resources = {
		[ resourceName ]: { data: items },
		[ 'active-plugins' ]: { data: response.active },
	};
	Object.keys( items ).forEach( key => {
		const item = items[ key ];
		resources[ getResourceName( resourceName, item ) ] = { data: item };
	} );
	return resources;
}

function readJetpackStatus( resourceNames, fetch ) {
	const resourceName = 'jetpack-status';

	if ( resourceNames.includes( resourceName ) ) {
		const url = JETPACK_NAMESPACE + '/connection';

		return [
			fetch( {
				path: url,
			} )
				.then( response => {
					return { [ resourceName ]: { data: response } };
				} )
				.catch( error => {
					return { [ resourceName ]: { error: String( error.message ) } };
				} ),
		];
	}

	return [];
}

function readJetpackConnectUrl( resourceNames, fetch ) {
	const resourceName = 'jetpack-connect-url';

	if ( resourceNames.includes( resourceName ) ) {
		const url = WC_ADMIN_NAMESPACE + '/onboarding/plugins/connect-jetpack';

		return [
			fetch( {
				path: url,
			} )
				.then( response => {
					return { [ resourceName ]: { data: response.connectAction } };
				} )
				.catch( error => {
					error.message = getPluginErrorMessage( 'connect', 'jetpack' );
					return { [ resourceName ]: { error } };
				} ),
		];
	}

	return [];
}

function getPluginErrorMessage( action, plugin ) {
	const pluginName = pluginNames[ plugin ] || plugin;
	switch ( action ) {
		case 'install':
			return sprintf(
				__( 'There was an error installing %s. Please try again.', 'woocommerce-admin' ),
				pluginName
			);
		case 'connect':
			return sprintf(
				__( 'There was an error connecting to %s. Please try again.', 'woocommerce-admin' ),
				pluginName
			);
		case 'activate':
		default:
			return sprintf(
				__( 'There was an error activating %s. Please try again.', 'woocommerce-admin' ),
				pluginName
			);
	}
}

function installPlugins( resourceNames, data, fetch ) {
	const resourceName = 'plugin-install';
	if ( resourceNames.includes( resourceName ) ) {
		const plugins = data[ resourceName ];

		return plugins.map( async plugin => {
			return fetch( {
				path: `${ WC_ADMIN_NAMESPACE }/onboarding/plugins/install`,
				method: 'POST',
				data: {
					plugin,
				},
			} )
				.then( response => {
					return {
						[ resourceName ]: { data: plugins },
						[ getResourceName( resourceName, plugin ) ]: { data: response },
					};
				} )
				.catch( error => {
					error.message = getPluginErrorMessage( 'install', pluginNames[ plugin ] || plugin );
					return {
						[ resourceName ]: { data: plugins },
						[ getResourceName( resourceName, plugin ) ]: { error },
					};
				} );
		} );
	}

	return [];
}

export default {
	read,
	update,
};
