/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { __, _n, sprintf } from '@wordpress/i18n';
import { DispatchFromMap } from '@automattic/data-stores';
import { controls } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import { ACTION_TYPES as TYPES } from './action-types';
import { WC_ADMIN_NAMESPACE } from '../constants';
import { isRestApiError } from '../types';
import {
	PaypalOnboardingStatus,
	SelectorKeysWithActions,
	RecommendedTypes,
	InstallPluginsResponse,
	ActivatePluginsResponse,
	PluginsResponse,
	PluginNames,
	JetpackConnectionDataResponse,
	Plugin,
} from './types';

class PluginError extends Error {
	constructor( message: string, public data: unknown ) {
		super( message );
	}
}

type PluginResponseErrors = PluginsResponse< unknown >[ 'errors' ][ 'errors' ];

const isPluginResponseError = (
	plugins: Partial< PluginNames >[],
	error: unknown
): error is PluginResponseErrors =>
	typeof error === 'object' && error !== null && plugins[ 0 ] in error;

const formatErrorMessage = (
	actionType: 'install' | 'activate' = 'install',
	plugins: Partial< PluginNames >[],
	rawErrorMessage: string
) => {
	return sprintf(
		/* translators: %(actionType): install or activate (the plugin). %(pluginName): a plugin slug (e.g. woocommerce-services). %(error): a single error message or in plural a comma separated error message list.*/
		_n(
			'Could not %(actionType)s %(pluginName)s plugin, %(error)s',
			'Could not %(actionType)s the following plugins: %(pluginName)s with these Errors: %(error)s',
			Object.keys( plugins ).length || 1,
			'woocommerce'
		),
		{
			actionType,
			pluginName: plugins.join( ', ' ),
			error: rawErrorMessage,
		}
	);
};

export function updateActivePlugins( active: string[], replace = false ) {
	return {
		type: TYPES.UPDATE_ACTIVE_PLUGINS as const,
		active,
		replace,
	};
}

export function updateInstalledPlugins(
	installed: string[],
	replace = false
): {
	type: TYPES.UPDATE_INSTALLED_PLUGINS;
	installed: string[];
	replace?: boolean;
} {
	return {
		type: TYPES.UPDATE_INSTALLED_PLUGINS as const,
		installed,
		replace,
	};
}

export function setIsRequesting(
	selector: SelectorKeysWithActions,
	isRequesting: boolean
) {
	return {
		type: TYPES.SET_IS_REQUESTING as const,
		selector,
		isRequesting,
	};
}

export function setError( selector: SelectorKeysWithActions, error: unknown ) {
	return {
		type: TYPES.SET_ERROR as const,
		selector,
		error,
	};
}

export function updateIsJetpackConnected( jetpackConnection: boolean ) {
	return {
		type: TYPES.UPDATE_JETPACK_CONNECTION as const,
		jetpackConnection,
	};
}

export function updateJetpackConnectionData(
	results: JetpackConnectionDataResponse
) {
	return {
		type: TYPES.UPDATE_JETPACK_CONNECTION_DATA as const,
		results,
	};
}

export function updateJetpackConnectUrl(
	redirectUrl: string,
	jetpackConnectUrl: string
) {
	return {
		type: TYPES.UPDATE_JETPACK_CONNECT_URL as const,
		jetpackConnectUrl,
		redirectUrl,
	};
}

export const createErrorNotice = ( errorMessage: string ) => {
	return controls.dispatch(
		'core/notices',
		'createNotice',
		'error',
		errorMessage
	) as {
		type: 'CREATE_NOTICE';
		[ key: string ]: unknown;
	};
};

export function setPaypalOnboardingStatus(
	status: Partial< PaypalOnboardingStatus >
): {
	type: TYPES.SET_PAYPAL_ONBOARDING_STATUS;
	paypalOnboardingStatus: Partial< PaypalOnboardingStatus >;
} {
	return {
		type: TYPES.SET_PAYPAL_ONBOARDING_STATUS as const,
		paypalOnboardingStatus: status,
	};
}

export function setRecommendedPlugins(
	type: string,
	plugins: Plugin[]
): {
	type: TYPES.SET_RECOMMENDED_PLUGINS;
	recommendedType: string;
	plugins: Plugin[];
} {
	return {
		type: TYPES.SET_RECOMMENDED_PLUGINS as const,
		recommendedType: type,
		plugins,
	};
}

function* handlePluginAPIError(
	actionType: 'install' | 'activate',
	plugins: Partial< PluginNames >[],
	error: unknown
) {
	let rawErrorMessage;

	if ( isPluginResponseError( plugins, error ) ) {
		// Backend error messages are in the form of { plugin-slug: [ error messages ] }.
		rawErrorMessage = Object.values( error ).join( ', \n' );
	} else {
		// Other error such as API connection errors.
		rawErrorMessage =
			isRestApiError( error ) || error instanceof Error
				? error.message
				: JSON.stringify( error );
	}

	// Track the error.
	switch ( actionType ) {
		case 'install':
			recordEvent( 'install_plugins_error', {
				plugins: plugins.join( ', ' ),
				message: rawErrorMessage,
			} );
			break;
		case 'activate':
			recordEvent( 'activate_plugins_error', {
				plugins: plugins.join( ', ' ),
				message: rawErrorMessage,
			} );
	}

	throw new PluginError(
		formatErrorMessage( actionType, plugins, rawErrorMessage ),
		error
	);
}

// Action Creator Generators
export function* installPlugins(
	plugins: Partial< PluginNames >[],
	async = false
) {
	yield setIsRequesting( 'installPlugins', true );

	try {
		const results: InstallPluginsResponse = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/plugins/install`,
			method: 'POST',
			data: { plugins: plugins.join( ',' ), async },
		} );

		if ( results.data.installed?.length ) {
			yield updateInstalledPlugins( results.data.installed );
		}
		if (
			results.errors?.errors &&
			Object.keys( results.errors.errors ).length
		) {
			throw results.errors.errors;
		}

		return results;
	} catch ( error ) {
		yield setError( 'installPlugins', error );
		yield handlePluginAPIError( 'install', plugins, error );
	} finally {
		yield setIsRequesting( 'installPlugins', false );
	}
}

export function* activatePlugins( plugins: Partial< PluginNames >[] ) {
	yield setIsRequesting( 'activatePlugins', true );

	try {
		const results: ActivatePluginsResponse = yield apiFetch( {
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

		return results;
	} catch ( error ) {
		yield setError( 'activatePlugins', error );
		yield handlePluginAPIError( 'activate', plugins, error );
	} finally {
		yield setIsRequesting( 'activatePlugins', false );
	}
}

export function* installAndActivatePlugins( plugins: string[] ) {
	try {
		const installations: InstallPluginsResponse = yield controls.dispatch(
			STORE_NAME,
			'installPlugins',
			plugins
		);
		const activations: InstallPluginsResponse = yield controls.dispatch(
			STORE_NAME,
			'activatePlugins',
			plugins
		);

		const response = {
			...activations,
			data: {
				...activations.data,
				...installations.data,
			},
		};

		// If everything was a success and we both installed and activated, make the success message more informative.
		if (
			installations.success &&
			Object.keys( installations.data.results ).length &&
			activations.success &&
			activations.data.activated.length
		) {
			// If only one plugin was installed, use the plugin details to create a more informative message.
			if ( activations.data.activated.length === 1 ) {
				const plugin_slug = activations.data.activated[ 0 ];
				const plugin = activations.data.plugin_details?.[ plugin_slug ];

				if ( plugin ) {
					response.message = sprintf(
						/* translators: %1$s: plugin name, %2$s: plugin version */
						__(
							'%1$s (%2$s) was successfully installed and activated.',
							'woocommerce'
						),
						plugin.name,
						plugin.version
					);
				} else {
					response.message = __(
						'A plugin was successfully installed and activated.',
						'woocommerce'
					);
				}
			} else {
				response.message = __(
					'Plugins were successfully installed and activated.',
					'woocommerce'
				);
			}
		}

		return response;
	} catch ( error ) {
		throw error;
	}
}

export function* connectToJetpack(
	getAdminLink: ( endpoint: string ) => string
) {
	const url: string = yield controls.resolveSelect(
		STORE_NAME,
		'getJetpackConnectUrl',
		{
			redirect_url: getAdminLink( 'admin.php?page=wc-admin' ),
		}
	);
	const error: string = yield controls.resolveSelect(
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

export function* installJetpackAndConnect(
	errorAction: ( errorMessage: string ) => void,
	getAdminLink: ( endpoint: string ) => string
) {
	try {
		yield controls.dispatch( STORE_NAME, 'installPlugins', [ 'jetpack' ] );
		yield controls.dispatch( STORE_NAME, 'activatePlugins', [ 'jetpack' ] );

		const url: string = yield controls.dispatch(
			STORE_NAME,
			'connectToJetpack',
			getAdminLink
		);
		window.location.href = url;
	} catch ( error ) {
		if ( error instanceof Error ) {
			yield errorAction( error.message );
		} else {
			throw error;
		}
	}
}

export function* connectToJetpackWithFailureRedirect(
	failureRedirect: string,
	errorAction: ( errorMessage: string ) => void,
	getAdminLink: ( endpoint: string ) => string
) {
	try {
		const url: string = yield controls.dispatch(
			STORE_NAME,
			'connectToJetpack',
			getAdminLink
		);
		window.location.href = url;
	} catch ( error ) {
		if ( error instanceof Error ) {
			yield errorAction( error.message );
		} else {
			throw error;
		}
		window.location.href = failureRedirect;
	}
}

const SUPPORTED_TYPES = [ 'payments' ];

export function* dismissRecommendedPlugins( type: RecommendedTypes ) {
	if ( ! SUPPORTED_TYPES.includes( type ) ) {
		return [];
	}
	const plugins: Plugin[] = yield controls.resolveSelect(
		STORE_NAME,
		'getRecommendedPlugins',
		type
	);
	yield setRecommendedPlugins( type, [] );

	let success: boolean;
	try {
		const url = WC_ADMIN_NAMESPACE + '/payment-gateway-suggestions/dismiss';
		success = yield apiFetch( {
			path: url,
			method: 'POST',
		} );
	} catch ( error ) {
		success = false;
	}
	if ( ! success ) {
		// Reset recommended plugins
		yield setRecommendedPlugins( type, plugins );
	}
	return success;
}

export function* deactivatePlugin( pluginFile: string ) {
	try {
		yield apiFetch( {
			path: `/wp/v2/plugins/${ pluginFile }`,
			method: 'POST',
			data: { status: 'inactive' },
		} );
	} catch ( error ) {
		throw error;
	}
}

export type Actions = ReturnType<
	| typeof updateActivePlugins
	| typeof updateInstalledPlugins
	| typeof setIsRequesting
	| typeof setError
	| typeof updateIsJetpackConnected
	| typeof updateJetpackConnectUrl
	| typeof updateJetpackConnectionData
	| typeof setPaypalOnboardingStatus
	| typeof setRecommendedPlugins
	| typeof createErrorNotice
>;

// Types
export type ActionDispatchers = DispatchFromMap< {
	installPlugins: typeof installPlugins;
	activatePlugins: typeof activatePlugins;
	installJetpackAndConnect: typeof installJetpackAndConnect;
	installAndActivatePlugins: typeof installAndActivatePlugins;
	connectToJetpackWithFailureRedirect: typeof connectToJetpackWithFailureRedirect;
	dismissRecommendedPlugins: typeof dismissRecommendedPlugins;
	deactivatePlugin: typeof deactivatePlugin;
} >;
