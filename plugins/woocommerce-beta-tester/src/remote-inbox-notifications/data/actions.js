/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { controls } from '@wordpress/data';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { API_NAMESPACE, STORE_KEY } from './constants';

/**
 * Initialize the state
 *
 * @param {Array} notifications
 */
export function setNotifications( notifications ) {
	return {
		type: TYPES.SET_NOTIFICATIONS,
		notifications,
	};
}

export function setLoadingState( isLoading ) {
	return {
		type: TYPES.SET_IS_LOADING,
		isLoading,
	};
}

export function setNotice( notice ) {
	return {
		type: TYPES.SET_NOTICE,
		notice,
	};
}

export function* importNotifications( notifications ) {
	try {
		yield apiFetch( {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			path: `${ API_NAMESPACE }/remote-inbox-notifications/import`,
			body: JSON.stringify( notifications ),
		} );

		yield controls.dispatch(
			STORE_KEY,
			'invalidateResolutionForStoreSelector',
			'getNotifications'
		);
	} catch ( error ) {
		throw new Error();
	}
}

export function* deleteNotification( id ) {
	try {
		yield apiFetch( {
			method: 'POST',
			path: `${ API_NAMESPACE }/remote-inbox-notifications/${ id }/delete`,
		} );

		yield {
			type: TYPES.DELETE_NOTIFICATION,
			id,
		};
	} catch {
		throw new Error();
	}
}

export function* testNotification( name ) {
	try {
		const response = yield apiFetch( {
			method: 'GET',
			path: `${ API_NAMESPACE }/remote-inbox-notifications/${ name }/test`,
		} );

		yield setNotice( {
			message:
				typeof response.message === 'string'
					? response.message
					: 'The following rules have failed.\n\n' +
					  JSON.stringify( response.message, null, 2 ),
			status: response.success ? 'success' : 'error',
		} );

		if ( response.success ) {
			yield controls.dispatch(
				STORE_KEY,
				'invalidateResolutionForStoreSelector',
				'getNotifications'
			);
		}
	} catch ( e ) {
		throw new Error( e );
	}
}

export function* deleteAllNotifications() {
	try {
		yield apiFetch( {
			method: 'POST',
			path: `${ API_NAMESPACE }/remote-inbox-notifications/delete-all`,
		} );

		yield controls.dispatch(
			STORE_KEY,
			'invalidateResolutionForStoreSelector',
			'getNotifications'
		);
	} catch {
		throw new Error();
	}
}
