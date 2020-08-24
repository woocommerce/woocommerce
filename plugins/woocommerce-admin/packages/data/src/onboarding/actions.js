/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { WC_ADMIN_NAMESPACE } from '../constants';

export function setError( selector, error ) {
	return {
		type: TYPES.SET_ERROR,
		selector,
		error,
	};
}

export function setIsRequesting( selector, isRequesting ) {
	return {
		type: TYPES.SET_IS_REQUESTING,
		selector,
		isRequesting,
	};
}

export function setProfileItems( profileItems, replace = false ) {
	return {
		type: TYPES.SET_PROFILE_ITEMS,
		profileItems,
		replace,
	};
}

export function setTasksStatus( tasksStatus ) {
	return {
		type: TYPES.SET_TASKS_STATUS,
		tasksStatus,
	};
}

export function* updateProfileItems( items ) {
	yield setIsRequesting( 'updateProfileItems', true );

	try {
		const results = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/onboarding/profile`,
			method: 'POST',
			data: items,
		} );

		if ( results && results.status === 'success' ) {
			yield setProfileItems( items );
			yield setIsRequesting( 'updateProfileItems', false );
			return results;
		}

		throw new Error();
	} catch ( error ) {
		yield setError( 'updateProfileItems', error );
		yield setIsRequesting( 'updateProfileItems', false );
		throw new Error();
	}
}
