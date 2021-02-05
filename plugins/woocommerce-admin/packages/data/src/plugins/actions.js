/**
 * External dependencies
 */
import { apiFetch, dispatch, select } from '@wordpress/data-controls';
import { _n, sprintf } from '@wordpress/i18n';

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
			throw results.errors.errors;
		}

		yield setIsRequesting( 'installPlugins', false );

		return results;
	} catch ( error ) {
		yield setError( 'installPlugins', error );
		throw new Error( formatErrorMessage( error ) );
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
			throw results.errors.errors;
		}

		yield setIsRequesting( 'activatePlugins', false );

		return results;
	} catch ( error ) {
		yield setError( 'activatePlugins', error );
		throw new Error( formatErrors( error ) );
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

export const createErrorNotice = ( errorMessage ) => {
	return dispatch( 'core/notices', 'createNotice', errorMessage );
};

export function* connectToJetpack( getAdminLink ) {
	const url = yield select( STORE_NAME, 'getJetpackConnectUrl', {
		redirect_url: getAdminLink( 'admin.php?page=wc-admin' ),
	} );
	const error = yield select(
		STORE_NAME,
		'getPluginsError',
		'getJetpackConnectUrl'
	);

	if ( error ) {
		throw new Error( error );
	} else {
		return url;
	}
}

export function* installJetpackAndConnect( errorAction, getAdminLink ) {
	try {
		yield dispatch( STORE_NAME, 'installPlugins', [ 'jetpack' ] );
		yield dispatch( STORE_NAME, 'activatePlugins', [ 'jetpack' ] );

		const url = yield dispatch(
			STORE_NAME,
			'connectToJetpack',
			getAdminLink
		);
		window.location = url;
	} catch ( error ) {
		yield errorAction( error.message );
	}
}

export function* connectToJetpackWithFailureRedirect(
	failureRedirect,
	errorAction,
	getAdminLink
) {
	try {
		const url = yield dispatch(
			STORE_NAME,
			'connectToJetpack',
			getAdminLink
		);
		window.location = url;
	} catch ( error ) {
		yield errorAction( error.message );
		window.location = failureRedirect;
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

const formatErrorMessage = ( pluginErrors, actionType = 'install' ) => {
	return sprintf(
		/* translators: %(actionType): install or activate (the plugin). %(pluginName): a plugin slug (e.g. woocommerce-services). %(error): a single error message or in plural a comma separated error message list.*/
		_n(
			'Could not %(actionType)s %(pluginName)s plugin, %(error)s',
			'Could not %(actionType)s the following plugins: %(pluginName)s with these Errors: %(error)s',
			pluginErrors.length,
			'woocommerce-admin'
		),
		{
			actionType,
			pluginName: Object.keys( pluginErrors ).join( ', ' ),
			error: Object.values( pluginErrors ).join( ', \n' ),
		}
	);
};
