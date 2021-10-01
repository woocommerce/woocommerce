/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { WC_ADMIN_NAMESPACE } from '../constants';

export function getFreeExtensionsError( error ) {
	return {
		type: TYPES.GET_FREE_EXTENSIONS_ERROR,
		error,
	};
}

export function getFreeExtensionsSuccess( freeExtensions ) {
	return {
		type: TYPES.GET_FREE_EXTENSIONS_SUCCESS,
		freeExtensions,
	};
}

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

export function getTaskListsError( error ) {
	return {
		type: TYPES.GET_TASK_LISTS_ERROR,
		error,
	};
}

export function getTaskListsSuccess( taskLists ) {
	return {
		type: TYPES.GET_TASK_LISTS_SUCCESS,
		taskLists,
	};
}

export function snoozeTaskError( taskId, error ) {
	return {
		type: TYPES.SNOOZE_TASK_ERROR,
		taskId,
		error,
	};
}

export function snoozeTaskRequest( taskId ) {
	return {
		type: TYPES.SNOOZE_TASK_REQUEST,
		taskId,
	};
}

export function snoozeTaskSuccess( task ) {
	return {
		type: TYPES.SNOOZE_TASK_SUCCESS,
		task,
	};
}

export function undoSnoozeTaskError( taskId, error ) {
	return {
		type: TYPES.UNDO_SNOOZE_TASK_ERROR,
		taskId,
		error,
	};
}

export function undoSnoozeTaskRequest( taskId ) {
	return {
		type: TYPES.UNDO_SNOOZE_TASK_REQUEST,
		taskId,
	};
}

export function undoSnoozeTaskSuccess( task ) {
	return {
		type: TYPES.UNDO_SNOOZE_TASK_SUCCESS,
		task,
	};
}

export function dismissTaskError( taskId, error ) {
	return {
		type: TYPES.DISMISS_TASK_ERROR,
		taskId,
		error,
	};
}

export function dismissTaskRequest( taskId ) {
	return {
		type: TYPES.DISMISS_TASK_REQUEST,
		taskId,
	};
}

export function dismissTaskSuccess( task ) {
	return {
		type: TYPES.DISMISS_TASK_SUCCESS,
		task,
	};
}

export function undoDismissTaskError( taskId, error ) {
	return {
		type: TYPES.UNDO_DISMISS_TASK_ERROR,
		taskId,
		error,
	};
}

export function undoDismissTaskRequest( taskId ) {
	return {
		type: TYPES.UNDO_DISMISS_TASK_REQUEST,
		taskId,
	};
}

export function undoDismissTaskSuccess( task ) {
	return {
		type: TYPES.UNDO_DISMISS_TASK_SUCCESS,
		task,
	};
}

export function hideTaskListError( taskListId, error ) {
	return {
		type: TYPES.HIDE_TASK_LIST_ERROR,
		taskListId,
		error,
	};
}

export function hideTaskListRequest( taskListId ) {
	return {
		type: TYPES.HIDE_TASK_LIST_REQUEST,
		taskListId,
	};
}

export function hideTaskListSuccess( taskList ) {
	return {
		type: TYPES.HIDE_TASK_LIST_SUCCESS,
		taskList,
	};
}

export function optimisticallyCompleteTaskRequest( taskId ) {
	return {
		type: TYPES.OPTIMISTICALLY_COMPLETE_TASK_REQUEST,
		taskId,
	};
}

export function setTasksStatus( tasksStatus ) {
	return {
		type: TYPES.SET_TASKS_STATUS,
		tasksStatus,
	};
}

export function setPaymentMethods( paymentMethods ) {
	return {
		type: TYPES.GET_PAYMENT_METHODS_SUCCESS,
		paymentMethods,
	};
}

export function setEmailPrefill( email ) {
	return {
		type: TYPES.SET_EMAIL_PREFILL,
		emailPrefill: email,
	};
}

export function* updateProfileItems( items ) {
	yield setIsRequesting( 'updateProfileItems', true );
	yield setError( 'updateProfileItems', null );

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
		throw error;
	}
}

/**
 * Used to keep backwards compatibility with the extended task list filter on the client.
 * This can be removed after version WC Admin 2.10 (see deprecated notice in resolvers.js).
 *
 * @param {Object} task the returned task object.
 * @param {Array}  keys to keep in the task object.
 * @return {Object} task with the keys specified.
 */
function possiblyPruneTaskData( task, keys ) {
	if ( ! task.time && ! task.title ) {
		// client side task
		return keys.reduce(
			( simplifiedTask, key ) => {
				return {
					...simplifiedTask,
					[ key ]: task[ key ],
				};
			},
			{ id: task.id }
		);
	}
	return task;
}

export function* snoozeTask( id ) {
	yield snoozeTaskRequest( id );

	try {
		const task = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/onboarding/tasks/${ id }/snooze`,
			method: 'POST',
		} );

		yield snoozeTaskSuccess(
			possiblyPruneTaskData( task, [
				'isSnoozed',
				'isDismissed',
				'snoozedUntil',
			] )
		);
	} catch ( error ) {
		yield snoozeTaskError( id, error );
		throw new Error();
	}
}

export function* undoSnoozeTask( id ) {
	yield undoSnoozeTaskRequest( id );

	try {
		const task = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/onboarding/tasks/${ id }/undo_snooze`,
			method: 'POST',
		} );

		yield undoSnoozeTaskSuccess(
			possiblyPruneTaskData( task, [
				'isSnoozed',
				'isDismissed',
				'snoozedUntil',
			] )
		);
	} catch ( error ) {
		yield undoSnoozeTaskError( id, error );
		throw new Error();
	}
}

export function* dismissTask( id ) {
	yield dismissTaskRequest( id );

	try {
		const task = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/onboarding/tasks/${ id }/dismiss`,
			method: 'POST',
		} );

		yield dismissTaskSuccess(
			possiblyPruneTaskData( task, [ 'isDismissed', 'isSnoozed' ] )
		);
	} catch ( error ) {
		yield dismissTaskError( id, error );
		throw new Error();
	}
}

export function* undoDismissTask( id ) {
	yield undoDismissTaskRequest( id );

	try {
		const task = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/onboarding/tasks/${ id }/undo_dismiss`,
			method: 'POST',
		} );

		yield undoDismissTaskSuccess(
			possiblyPruneTaskData( task, [ 'isDismissed', 'isSnoozed' ] )
		);
	} catch ( error ) {
		yield undoDismissTaskError( id, error );
		throw new Error();
	}
}

export function* hideTaskList( id ) {
	yield hideTaskListRequest( id );

	try {
		const taskList = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/onboarding/tasks/${ id }/hide`,
			method: 'POST',
		} );

		yield hideTaskListSuccess( taskList );
	} catch ( error ) {
		yield hideTaskListError( id, error );
		throw new Error();
	}
}

export function* optimisticallyCompleteTask( id ) {
	yield optimisticallyCompleteTaskRequest( id );
}
