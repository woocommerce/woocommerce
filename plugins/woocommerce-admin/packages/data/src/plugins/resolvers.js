/**
 * External dependencies
 */
import { apiFetch, select } from '@wordpress/data-controls';
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
	setPaypalOnboardingStatus,
} from './actions';

export function* getActivePlugins() {
	yield setIsRequesting( 'getActivePlugins', true );
	try {
		const url = WC_ADMIN_NAMESPACE + '/plugins/active';
		const results = yield apiFetch( {
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
		const results = yield apiFetch( {
			path: url,
			method: 'GET',
		} );

		yield updateInstalledPlugins( results, true );
	} catch ( error ) {
		yield setError( 'getInstalledPlugins', error );
	}
}

export function* isJetpackConnected() {
	yield setIsRequesting( 'isJetpackConnected', true );

	try {
		const url = JETPACK_NAMESPACE + '/connection';
		const results = yield apiFetch( {
			path: url,
			method: 'GET',
		} );

		yield updateIsJetpackConnected( results.isActive );
	} catch ( error ) {
		yield setError( 'isJetpackConnected', error );
	}

	yield setIsRequesting( 'isJetpackConnected', false );
}

export function* getJetpackConnectUrl( query ) {
	yield setIsRequesting( 'getJetpackConnectUrl', true );

	try {
		const url = addQueryArgs(
			WC_ADMIN_NAMESPACE + '/plugins/connect-jetpack',
			query
		);
		const results = yield apiFetch( {
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

export function* getPaypalOnboardingStatus() {
	yield setIsRequesting( 'getPaypalOnboardingStatus', true );

	const errorData = yield select(
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
			const results = yield apiFetch( {
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

function* setOnboardingStatusWithOptions() {
	const options = yield select(
		OPTIONS_STORE_NAME,
		'getOption',
		'woocommerce-ppcp-settings'
	);
	yield setPaypalOnboardingStatus( {
		production: {
			onboarded:
				options.merchant_email_production &&
				options.merchant_id_production &&
				options.client_id_production &&
				options.client_secret_production
					? true
					: false,
		},
	} );
}
