/**
 * External dependencies
 */
import { apiFetch, dispatch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { pluginNames, STORE_NAME } from './constants';
import TYPES from './action-types';
import { WC_ADMIN_NAMESPACE } from '../constants';

export function updateActivePlugins( active, replace = false ) {
	return {
		type: TYPES.UPDATE_ACTIVE_PLUGINS,
		active,
		replace,
	};
}

export function updateInstalledPlugins( installed, replace = false ) {
	return {
		type: TYPES.UPDATE_INSTALLED_PLUGINS,
		installed,
		replace,
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

export function* installPlugins( plugins ) {
	yield setIsRequesting( 'installPlugins', true );

	try {
		const results = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/plugins/install`,
			method: 'POST',
			data: { plugins: plugins.join( ',' ) },
		} );

		if ( results.data.installed.length ) {
			yield updateInstalledPlugins( results.data.installed );
		}

		if ( Object.keys( results.errors.errors ).length ) {
			throw results.errors;
		}

		yield setIsRequesting( 'installPlugins', false );

		return results;
	} catch ( error ) {
		yield setError( 'installPlugins', error );
		throw formatErrors( error );
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

		if ( results.data.activated.length ) {
			yield updateActivePlugins( results.data.activated );
		}

		if ( Object.keys( results.errors.errors ).length ) {
			throw results.errors;
		}

		yield setIsRequesting( 'activatePlugins', false );

		return results;
	} catch ( error ) {
		yield setError( 'activatePlugins', error );
		throw formatErrors( error );
	}
}

export function* installAndActivatePlugins( plugins ) {
	try {
		yield dispatch( STORE_NAME, 'installPlugins', plugins );
		const activations = yield dispatch(
			STORE_NAME,
			'activatePlugins',
			plugins
		);
		return activations;
	} catch ( error ) {
		throw error;
	}
}

export function formatErrors( response ) {
	if ( response.errors ) {
		// Replace the slug with a plugin name if a constant exists.
		Object.keys( response.errors ).forEach( ( plugin ) => {
			response.errors[ plugin ] = response.errors[ plugin ].map(
				( pluginError ) => {
					return pluginNames[ plugin ]
						? pluginError.replace(
								`\`${ plugin }\``,
								pluginNames[ plugin ]
						  )
						: pluginError;
				}
			);
		} );
	}

	return response;
}
