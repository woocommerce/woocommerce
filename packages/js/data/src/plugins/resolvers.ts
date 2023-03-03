/**
 * External dependencies
 */
import { apiFetch, select } from '@wordpress/data-controls';
import { controls } from '@wordpress/data';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { WC_ADMIN_NAMESPACE, JETPACK_NAMESPACE } from '../constants';
import { OPTIONS_STORE_NAME } from '../options';
import { PAYPAL_NAMESPACE, STORE_NAME } from './constants';
import {
	setIsRequesting,
	updateActivePlugins,
	setError,
	updateInstalledPlugins,
	updateIsJetpackConnected,
	updateJetpackConnectUrl,
	updateJetpackConnectionData,
	setPaypalOnboardingStatus,
	setRecommendedPlugins,
} from './actions';
import {
	PaypalOnboardingStatus,
	RecommendedTypes,
	JetpackConnectionDataResponse,
} from './types';

// Can be removed in WP 5.9, wp.data is supported in >5.7.
const resolveSelect =
	controls && controls.resolveSelect ? controls.resolveSelect : select;
type PluginGetResponse = {
	plugins: string[];
} & Response;

type JetpackConnectionResponse = {
	isActive: boolean;
} & Response;

type ConnectJetpackResponse = {
	slug: 'jetpack';
	name: string;
	connectAction: string;
} & Response;

export function* getActivePlugins() {
	yield setIsRequesting( 'getActivePlugins', true );
	try {
		const url = WC_ADMIN_NAMESPACE + '/plugins/active';
		const results: PluginGetResponse = yield apiFetch( {
			path: url,
			method: 'GET',
		} );

		yield updateActivePlugins( results.plugins, true );
	} catch ( error ) {
		yield setError( 'getActivePlugins', error );
	}
}

export function* getInstalledPlugins() {
	yield setIsRequesting( 'getInstalledPlugins', true );

	try {
		const url = WC_ADMIN_NAMESPACE + '/plugins/installed';
		const results: PluginGetResponse = yield apiFetch( {
			path: url,
			method: 'GET',
		} );

		yield updateInstalledPlugins( results.plugins, true );
	} catch ( error ) {
		yield setError( 'getInstalledPlugins', error );
	}
}

export function* isJetpackConnected() {
	yield setIsRequesting( 'isJetpackConnected', true );

	try {
		const url = JETPACK_NAMESPACE + '/connection';
		const results: JetpackConnectionResponse = yield apiFetch( {
			path: url,
			method: 'GET',
		} );

		yield updateIsJetpackConnected( results.isActive );
	} catch ( error ) {
		yield setError( 'isJetpackConnected', error );
	}

	yield setIsRequesting( 'isJetpackConnected', false );
}

export function* getJetpackConnectionData() {
	yield setIsRequesting( 'getJetpackConnectionData', true );

	try {
		const url = JETPACK_NAMESPACE + '/connection/data';

		const results: JetpackConnectionDataResponse = yield apiFetch( {
			path: url,
			method: 'GET',
		} );

		yield updateJetpackConnectionData( results );
	} catch ( error ) {
		yield setError( 'getJetpackConnectionData', error );
	}

	yield setIsRequesting( 'getJetpackConnectionData', false );
}

export function* getJetpackConnectUrl( query: { redirect_url: string } ) {
	yield setIsRequesting( 'getJetpackConnectUrl', true );

	try {
		const url = addQueryArgs(
			WC_ADMIN_NAMESPACE + '/plugins/connect-jetpack',
			query
		);
		const results: ConnectJetpackResponse = yield apiFetch( {
			path: url,
			method: 'GET',
		} );

		yield updateJetpackConnectUrl(
			query.redirect_url,
			results.connectAction
		);
	} catch ( error ) {
		yield setError( 'getJetpackConnectUrl', error );
	}

	yield setIsRequesting( 'getJetpackConnectUrl', false );
}

function* setOnboardingStatusWithOptions() {
	const options: {
		merchant_email_production: string;
		merchant_id_production: string;
		client_id_production: string;
		client_secret_production: string;
	} = yield resolveSelect(
		OPTIONS_STORE_NAME,
		'getOption',
		'woocommerce-ppcp-settings'
	);
	const onboarded =
		options.merchant_email_production &&
		options.merchant_id_production &&
		options.client_id_production &&
		options.client_secret_production;
	yield setPaypalOnboardingStatus( {
		production: {
			state: onboarded ? 'onboarded' : 'unknown',
			onboarded: onboarded ? true : false,
		},
	} );
}

export function* getPaypalOnboardingStatus() {
	yield setIsRequesting( 'getPaypalOnboardingStatus', true );

	const errorData: {
		data?: { status: number };
	} = yield resolveSelect(
		STORE_NAME,
		'getPluginsError',
		'getPaypalOnboardingStatus'
	);
	if ( errorData && errorData.data && errorData.data.status === 404 ) {
		// The get-status request doesn't exist fall back to using options.
		yield setOnboardingStatusWithOptions();
	} else {
		try {
			const url = PAYPAL_NAMESPACE + '/onboarding/get-status';
			const results: PaypalOnboardingStatus = yield apiFetch( {
				path: url,
				method: 'GET',
			} );

			yield setPaypalOnboardingStatus( results );
		} catch ( error ) {
			yield setOnboardingStatusWithOptions();
			yield setError( 'getPaypalOnboardingStatus', error );
		}
	}

	yield setIsRequesting( 'getPaypalOnboardingStatus', false );
}

const SUPPORTED_TYPES = [ 'payments' ];
export function* getRecommendedPlugins( type: RecommendedTypes ) {
	if ( ! SUPPORTED_TYPES.includes( type ) ) {
		return [];
	}
	yield setIsRequesting( 'getRecommendedPlugins', true );

	try {
		const url = WC_ADMIN_NAMESPACE + '/payment-gateway-suggestions';
		const results: Plugin[] = yield apiFetch( {
			path: url,
			method: 'GET',
		} );

		yield setRecommendedPlugins( type, results );
	} catch ( error ) {
		yield setError( 'getRecommendedPlugins', error );
	}

	yield setIsRequesting( 'getRecommendedPlugins', false );
}
