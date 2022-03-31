/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { WC_ADMIN_NAMESPACE } from '../constants';
import { DeprecatedTasks } from './deprecated-tasks';

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

export function unhideTaskListError( taskListId, error ) {
	return {
		type: TYPES.UNHIDE_TASK_LIST_ERROR,
		taskListId,
		error,
	};
}

export function unhideTaskListRequest( taskListId ) {
	return {
		type: TYPES.UNHIDE_TASK_LIST_REQUEST,
		taskListId,
	};
}

export function unhideTaskListSuccess( taskList ) {
	return {
		type: TYPES.UNHIDE_TASK_LIST_SUCCESS,
		taskList,
	};
}

export function optimisticallyCompleteTaskRequest( taskId ) {
	return {
		type: TYPES.OPTIMISTICALLY_COMPLETE_TASK_REQUEST,
		taskId,
	};
}

export function visitedTask( taskId ) {
	return {
		type: TYPES.VISITED_TASK,
		taskId,
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

export function actionTaskError( taskId, error ) {
	return {
		type: TYPES.ACTION_TASK_ERROR,
		taskId,
		error,
	};
}

export function actionTaskRequest( taskId ) {
	return {
		type: TYPES.ACTION_TASK_REQUEST,
		taskId,
	};
}

export function actionTaskSuccess( task ) {
	return {
		type: TYPES.ACTION_TASK_SUCCESS,
		task,
	};
}

export function getProductTypesSuccess( productTypes ) {
	return {
		type: TYPES.GET_PRODUCT_TYPES_SUCCESS,
		productTypes,
	};
}

export function getProductTypesError( error ) {
	return {
		type: TYPES.GET_PRODUCT_TYPES_ERROR,
		error,
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

export function* snoozeTask( id ) {
	yield snoozeTaskRequest( id );

	try {
		const task = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/onboarding/tasks/${ id }/snooze`,
			method: 'POST',
		} );

		yield snoozeTaskSuccess(
			DeprecatedTasks.possiblyPruneTaskData( task, [
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
			DeprecatedTasks.possiblyPruneTaskData( task, [
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
			DeprecatedTasks.possiblyPruneTaskData( task, [
				'isDismissed',
				'isSnoozed',
			] )
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
			DeprecatedTasks.possiblyPruneTaskData( task, [
				'isDismissed',
				'isSnoozed',
			] )
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

export function* unhideTaskList( id ) {
	yield unhideTaskListRequest( id );

	try {
		const taskList = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/onboarding/tasks/${ id }/unhide`,
			method: 'POST',
		} );

		yield unhideTaskListSuccess( taskList );
	} catch ( error ) {
		yield unhideTaskListError( id, error );
		throw new Error();
	}
}

export function* optimisticallyCompleteTask( id ) {
	yield optimisticallyCompleteTaskRequest( id );
}

export function* actionTask( id ) {
	yield actionTaskRequest( id );

	try {
		const task = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/onboarding/tasks/${ id }/action`,
			method: 'POST',
		} );

		yield actionTaskSuccess(
			DeprecatedTasks.possiblyPruneTaskData( task, [ 'isActioned' ] )
		);
	} catch ( error ) {
		yield actionTaskError( id, error );
		throw new Error();
	}
}
