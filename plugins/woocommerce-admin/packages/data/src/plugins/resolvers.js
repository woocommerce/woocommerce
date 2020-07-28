/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { WC_ADMIN_NAMESPACE, JETPACK_NAMESPACE } from '../constants';
import {
	setIsRequesting,
	updateActivePlugins,
	setError,
	updateInstalledPlugins,
	updateIsJetpackConnected,
	updateJetpackConnectUrl,
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
