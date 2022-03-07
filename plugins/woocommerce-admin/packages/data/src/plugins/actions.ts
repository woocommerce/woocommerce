/**
 * External dependencies
 */
import {
	apiFetch,
	select,
	dispatch as depreciatedDispatch,
} from '@wordpress/data-controls';
import { controls } from '@wordpress/data';
import { _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { pluginNames, STORE_NAME } from './constants';
import { ACTION_TYPES as TYPES } from './action-types';
import { WC_ADMIN_NAMESPACE } from '../constants';
import { WPError } from '../types';
import {
	PaypalOnboardingStatus,
	PluginNames,
	SelectorKeysWithActions,
	RecommendedTypes,
} from './types';

// Can be removed in WP 5.9, wp.data is supported in >5.7.
const dispatch =
	controls && controls.dispatch ? controls.dispatch : depreciatedDispatch;
const resolveSelect =
	controls && controls.resolveSelect ? controls.resolveSelect : select;

type PluginsResponse< PluginData > = {
	data: PluginData;
	errors: WPError< PluginNames >;
	success: boolean;
	message: string;
} & Response;

type InstallPluginsResponse = PluginsResponse< {
	installed: string[];
	results: Record< string, boolean >;
	install_time?: Record< string, number >;
} >;

type ActivatePluginsResponse = PluginsResponse< {
	activated: string[];
	active: string[];
} >;

function isWPError(
	error: WPError< PluginNames > | Error | string
): error is WPError< PluginNames > {
	return ( error as WPError ).errors !== undefined;
}

class PluginError extends Error {
	constructor( message: string, public data: unknown ) {
		super( message );
	}
}

export function formatErrors(
	response: WPError< PluginNames > | Error | string
): string {
	if ( isWPError( response ) ) {
		// Replace the slug with a plugin name if a constant exists.
		( Object.keys( response.errors ) as PluginNames[] ).forEach(
			( plugin ) => {
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
			}
		);
	} else if ( typeof response === 'string' ) {
		return response;
	} else {
		return response.message;
	}
	return '';
}

const formatErrorMessage = (
	pluginErrors: Record< PluginNames, string[] >,
	actionType = 'install'
) => {
	return sprintf(
		/* translators: %(actionType): install or activate (the plugin). %(pluginName): a plugin slug (e.g. woocommerce-services). %(error): a single error message or in plural a comma separated error message list.*/
		_n(
			'Could not %(actionType)s %(pluginName)s plugin, %(error)s',
			'Could not %(actionType)s the following plugins: %(pluginName)s with these Errors: %(error)s',
			Object.keys( pluginErrors ).length || 1,
			'woocommerce-admin'
		),
		{
			actionType,
			pluginName: Object.keys( pluginErrors ).join( ', ' ),
			error: Object.values( pluginErrors ).join( ', \n' ),
		}
	);
};

export function updateActivePlugins(
	active: string[],
	replace = false
): { type: TYPES.UPDATE_ACTIVE_PLUGINS; active: string[]; replace?: boolean } {
	return {
		type: TYPES.UPDATE_ACTIVE_PLUGINS,
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
		type: TYPES.UPDATE_INSTALLED_PLUGINS,
		installed,
		replace,
	};
}

export function setIsRequesting(
	selector: SelectorKeysWithActions,
	isRequesting: boolean
): {
	type: TYPES.SET_IS_REQUESTING;
	selector: SelectorKeysWithActions;
	isRequesting: boolean;
} {
	return {
		type: TYPES.SET_IS_REQUESTING,
		selector,
		isRequesting,
	};
}

export function setError(
	selector: SelectorKeysWithActions,
	error: Partial< Record< PluginNames, string[] > >
): {
	type: TYPES.SET_ERROR;
	selector: SelectorKeysWithActions;
	error: Partial< Record< PluginNames, string[] > >;
} {
	return {
		type: TYPES.SET_ERROR,
		selector,
		error,
	};
}

export function updateIsJetpackConnected(
	jetpackConnection: boolean
): {
	type: TYPES.UPDATE_JETPACK_CONNECTION;
	jetpackConnection: boolean;
} {
	return {
		type: TYPES.UPDATE_JETPACK_CONNECTION,
		jetpackConnection,
	};
}

export function updateJetpackConnectUrl(
	redirectUrl: string,
	jetpackConnectUrl: string
): {
	type: TYPES.UPDATE_JETPACK_CONNECT_URL;
	redirectUrl: string;
	jetpackConnectUrl: string;
} {
	return {
		type: TYPES.UPDATE_JETPACK_CONNECT_URL,
		jetpackConnectUrl,
		redirectUrl,
	};
}

export function* installPlugins( plugins: string[] ) {
	yield setIsRequesting( 'installPlugins', true );

	try {
		const results: InstallPluginsResponse = yield apiFetch( {
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
		if ( plugins.length === 1 && ! error[ plugins[ 0 ] ] ) {
			// Incase of a network error
			error = { [ plugins[ 0 ] ]: error.message };
		}
		yield setError( 'installPlugins', error );
		throw new PluginError( formatErrorMessage( error ), error );
	}
}

export function* activatePlugins( plugins: string[] ) {
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

		yield setIsRequesting( 'activatePlugins', false );

		return results;
	} catch ( error ) {
		if ( plugins.length === 1 && ! error[ plugins[ 0 ] ] ) {
			// Incase of a network error
			error = { [ plugins[ 0 ] ]: error.message };
		}
		yield setError( 'activatePlugins', error );
		throw new PluginError( formatErrorMessage( error, 'activate' ), error );
	}
}

export function* installAndActivatePlugins( plugins: string[] ) {
	try {
		const installations: InstallPluginsResponse = yield dispatch(
			STORE_NAME,
			'installPlugins',
			plugins
		);
		const activations: InstallPluginsResponse = yield dispatch(
			STORE_NAME,
			'activatePlugins',
			plugins
		);
		return {
			...activations,
			data: {
				...activations.data,
				...installations.data,
			},
		};
	} catch ( error ) {
		throw error;
	}
}

export const createErrorNotice = ( errorMessage: string ) => {
	return dispatch( 'core/notices', 'createNotice', 'error', errorMessage );
};

export function* connectToJetpack(
	getAdminLink: ( endpoint: string ) => string
) {
	const url: string = yield resolveSelect(
		STORE_NAME,
		'getJetpackConnectUrl',
		{
			redirect_url: getAdminLink( 'admin.php?page=wc-admin' ),
		}
	);
	const error: string = yield resolveSelect(
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
	errorAction: ( errorMesage: string ) => void,
	getAdminLink: ( endpoint: string ) => string
) {
	try {
		yield dispatch( STORE_NAME, 'installPlugins', [ 'jetpack' ] );
		yield dispatch( STORE_NAME, 'activatePlugins', [ 'jetpack' ] );

		const url: string = yield dispatch(
			STORE_NAME,
			'connectToJetpack',
			getAdminLink
		);
		window.location.href = url;
	} catch ( error ) {
		yield errorAction( error.message );
	}
}

export function* connectToJetpackWithFailureRedirect(
	failureRedirect: string,
	errorAction: ( errorMesage: string ) => void,
	getAdminLink: ( endpoint: string ) => string
) {
	try {
		const url: string = yield dispatch(
			STORE_NAME,
			'connectToJetpack',
			getAdminLink
		);
		window.location.href = url;
	} catch ( error ) {
		yield errorAction( error.message );
		window.location.href = failureRedirect;
	}
}

export function setPaypalOnboardingStatus(
	status: Partial< PaypalOnboardingStatus >
): {
	type: TYPES.SET_PAYPAL_ONBOARDING_STATUS;
	paypalOnboardingStatus: Partial< PaypalOnboardingStatus >;
} {
	return {
		type: TYPES.SET_PAYPAL_ONBOARDING_STATUS,
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
		type: TYPES.SET_RECOMMENDED_PLUGINS,
		recommendedType: type,
		plugins,
	};
}

const SUPPORTED_TYPES = [ 'payments' ];
export function* dismissRecommendedPlugins( type: RecommendedTypes ) {
	if ( ! SUPPORTED_TYPES.includes( type ) ) {
		return [];
	}
	const plugins: Plugin[] = yield resolveSelect(
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

export type Actions =
	| ReturnType< typeof updateActivePlugins >
	| ReturnType< typeof updateInstalledPlugins >
	| ReturnType< typeof setIsRequesting >
	| ReturnType< typeof setError >
	| ReturnType< typeof updateIsJetpackConnected >
	| ReturnType< typeof updateJetpackConnectUrl >
	| ReturnType< typeof setPaypalOnboardingStatus >
	| ReturnType< typeof setRecommendedPlugins >;

// Types
export type ActionDispatchers = {
	installJetpackAndConnect: typeof installJetpackAndConnect;
	installAndActivatePlugins: typeof installAndActivatePlugins;
	dismissRecommendedPlugins: typeof dismissRecommendedPlugins;
};
