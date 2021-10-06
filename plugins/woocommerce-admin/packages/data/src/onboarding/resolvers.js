/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { applyFilters } from '@wordpress/hooks';
import deprecated from '@wordpress/deprecated';
import { parse } from 'qs';

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

function getQuery() {
	const searchString = window.location && window.location.search;
	if ( ! searchString ) {
		return {};
	}

	const search = searchString.substring( 1 );
	return parse( search );
}

/**
 * This function will be depreciated in favour of registering tasks on the back-end.
 *
 */
function getTasksFromDeprecatedFilter() {
	const filteredTasks = applyFilters(
		'woocommerce_admin_onboarding_task_list',
		[],
		getQuery()
	);
	if ( filteredTasks && filteredTasks.length > 0 ) {
		deprecated( 'woocommerce_admin_onboarding_task_list', {
			version: '2.10.0',
			alternative: 'TaskLists::add_task()',
			plugin: '@woocommerce/data',
		} );
		return {
			original: filteredTasks.reduce( ( org, task ) => {
				return {
					...org,
					[ task.key ]: task,
				};
			}, {} ),
			parsed: filteredTasks.map( ( task ) => ( {
				title: task.title,
				content: task.content,
				additional_info: task.additionalInfo,
				time: task.time,
				level: task.level ? parseInt( task.level, 10 ) : 3,
				list_id: task.type || 'extended',
				is_visible: task.visible,
				id: task.key,
				is_snoozeable: task.allowRemindMeLater,
				is_dismissable: task.isDismissable,
				is_complete: task.completed,
			} ) ),
		};
	}
	return { original: {}, parsed: [] };
}

export function* getTaskLists() {
	const tasksFromDeprecatedFilter = getTasksFromDeprecatedFilter();
	try {
		const results = yield apiFetch( {
			path: WC_ADMIN_NAMESPACE + '/onboarding/tasks',
			method:
				tasksFromDeprecatedFilter.parsed.length > 0 ? 'POST' : 'GET',
			data: tasksFromDeprecatedFilter.parsed.length && {
				extended_tasks: tasksFromDeprecatedFilter.parsed,
			},
		} );

		if ( tasksFromDeprecatedFilter.parsed.length > 0 ) {
			for ( const taskList of results ) {
				// Merge any extended task list items, to keep the callback functions around.
				taskList.tasks = taskList.tasks.map( ( task ) => {
					if (
						tasksFromDeprecatedFilter.original &&
						tasksFromDeprecatedFilter.original[ task.id ]
					) {
						return {
							...tasksFromDeprecatedFilter.original[ task.id ],
							...task,
						};
					}
					return task;
				} );
			}
		}
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
