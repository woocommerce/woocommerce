/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { WC_ADMIN_NAMESPACE } from '../constants';
import {
	getFreeExtensionsError,
	getFreeExtensionsSuccess,
	getTaskListsError,
	getTaskListsSuccess,
	setProfileItems,
	setError,
	setTasksStatus,
	setPaymentMethods,
	setEmailPrefill,
} from './actions';
import { DeprecatedTasks } from './deprecated-tasks';

export function* getProfileItems() {
	try {
		const results = yield apiFetch( {
			path: WC_ADMIN_NAMESPACE + '/onboarding/profile',
			method: 'GET',
		} );

		yield setProfileItems( results, true );
	} catch ( error ) {
		yield setError( 'getProfileItems', error );
	}
}

export function* getEmailPrefill() {
	try {
		const results = yield apiFetch( {
			path:
				WC_ADMIN_NAMESPACE +
				'/onboarding/profile/experimental_get_email_prefill',
			method: 'GET',
		} );

		yield setEmailPrefill( results.email );
	} catch ( error ) {
		yield setError( 'getEmailPrefill', error );
	}
}

export function* getTasksStatus() {
	try {
		const results = yield apiFetch( {
			path: WC_ADMIN_NAMESPACE + '/onboarding/tasks/status',
			method: 'GET',
		} );

		yield setTasksStatus( results, true );
	} catch ( error ) {
		yield setError( 'getTasksStatus', error );
	}
}

export function* getTaskLists() {
	const deprecatedTasks = new DeprecatedTasks();
	try {
		const results = yield apiFetch( {
			path: WC_ADMIN_NAMESPACE + '/onboarding/tasks',
			method: deprecatedTasks.hasDeprecatedTasks() ? 'POST' : 'GET',
			data: deprecatedTasks.getPostData(),
		} );

		deprecatedTasks.mergeDeprecatedCallbackFunctions( results );

		yield getTaskListsSuccess( results );
	} catch ( error ) {
		yield getTaskListsError( error );
	}
}

export function* getPaymentGatewaySuggestions() {
	try {
		const results = yield apiFetch( {
			path: WC_ADMIN_NAMESPACE + '/onboarding/payments',
			method: 'GET',
		} );

		yield setPaymentMethods( results );
	} catch ( error ) {
		yield setError( 'getPaymentGatewaySuggestions', error );
	}
}

export function* getFreeExtensions() {
	try {
		const results = yield apiFetch( {
			path: WC_ADMIN_NAMESPACE + '/onboarding/free-extensions',
			method: 'GET',
		} );

		yield getFreeExtensionsSuccess( results );
	} catch ( error ) {
		yield getFreeExtensionsError( error );
	}
}
