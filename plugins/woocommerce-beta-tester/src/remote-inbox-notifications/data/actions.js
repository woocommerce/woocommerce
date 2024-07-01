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
		setNotice( {
			message: 'Failed to import notifications',
			status: 'error',
		} );
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
		setNotice( {
			message: 'Notifications deleted successfully.',
			status: 'success',
		} );
	} catch {
		setNotice( {
			message: 'Failed to delete notification',
			status: 'error',
		} );
	}
}

export function* testNotification( name ) {
	try {
		const response = yield apiFetch( {
			method: 'GET',
			path: `${ API_NAMESPACE }/remote-inbox-notifications/${ name }/test`,
		} );

		if ( response.success ) {
			yield controls.dispatch(
				STORE_KEY,
				'invalidateResolutionForStoreSelector',
				'getNotifications'
			);
		}

		yield setNotice( {
			message:
				typeof response.message === 'string'
					? response.message
					: 'The following rules have failed.\n\n' +
					  JSON.stringify( response.message, null, 2 ),
			status: response.success ? 'success' : 'error',
		} );
	} catch ( e ) {
		setNotice( {
			message: 'Failed to test notification',
			status: 'error',
		} );
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

		setNotice( {
			message: 'All notifications deleted successfully.',
			status: 'success',
		} );
	} catch {
		setNotice( {
			message: 'Failed to delete all notifications',
			status: 'error',
		} );
	}
}
