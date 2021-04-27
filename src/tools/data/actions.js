/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { API_NAMESPACE } from './constants';

export function addCurrentlyRunning( command ) {
	return {
		type: TYPES.ADD_CURRENTLY_RUNNING,
		command,
	};
}

export function removeCurrentlyRunning( command ) {
	return {
		type: TYPES.REMOVE_CURRENTLY_RUNNING,
		command,
	};
}

export function addMessage( source, message ) {
	return {
		type: TYPES.ADD_MESSAGE,
		source,
		message,
	};
}

export function updateMssage( source, message, status ) {
	return {
		type: TYPES.ADD_MESSAGE,
		source,
		message,
		status,
	};
}

export function removeMessage( source ) {
	return {
		type: TYPES.REMOVE_MESSAGE,
		source,
	};
}

export function* triggerWcaInstall() {
	const id = 'Trigger WCA Install';

	try {
		yield addCurrentlyRunning( id );
		yield addMessage( id, 'Installing...' );

		yield apiFetch( {
			path: API_NAMESPACE + '/tools/trigger-wca-install/v1',
			method: 'POST',
		} );

		yield removeCurrentlyRunning( id );
		yield updateMssage( id, 'Install Completed' );
	} catch ( ex ) {
		yield updateMssage( id, ex.message, 'error' );
	}
}
