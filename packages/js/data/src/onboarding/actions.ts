/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { controls, dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { WC_ADMIN_NAMESPACE } from '../constants';
import { store } from './';

import { DeprecatedTasks } from './deprecated-tasks';
import { STORE_NAME as OPTIONS_STORE_NAME } from '../options/constants';
import {
	ExtensionList,
	ProfileItems,
	TaskListType,
	TaskType,
	OnboardingProductTypes,
	InstallAndActivatePluginsAsyncResponse,
	GetJetpackAuthUrlResponse,
	CoreProfilerStep,
	CoreProfilerCompletedSteps,
} from './types';
import { Plugin, PluginNames } from '../plugins/types';
import { optionsStore } from '..';

export function getFreeExtensionsError( error: unknown ) {
	return {
		type: TYPES.GET_FREE_EXTENSIONS_ERROR,
		error,
	};
}

export function getFreeExtensionsSuccess( freeExtensions: ExtensionList[] ) {
	return {
		type: TYPES.GET_FREE_EXTENSIONS_SUCCESS,
		freeExtensions,
	};
}

export function setError( selector: string, error: unknown ) {
	return {
		type: TYPES.SET_ERROR,
		selector,
		error,
	};
}

export function setIsRequesting( selector: string, isRequesting: boolean ) {
	return {
		type: TYPES.SET_IS_REQUESTING,
		selector,
		isRequesting,
	};
}

export function setProfileItems( profileItems: ProfileItems, replace = false ) {
	return {
		type: TYPES.SET_PROFILE_ITEMS,
		profileItems,
		replace,
	};
}

export function getTaskListsError( error: unknown ) {
	return {
		type: TYPES.GET_TASK_LISTS_ERROR,
		error,
	};
}

export function getTaskListsSuccess( taskLists: TaskListType[] ) {
	return {
		type: TYPES.GET_TASK_LISTS_SUCCESS,
		taskLists,
	};
}

export function snoozeTaskError( taskId: string, error: unknown ) {
	return {
		type: TYPES.SNOOZE_TASK_ERROR,
		taskId,
		error,
	};
}

export function snoozeTaskRequest( taskId: string ) {
	return {
		type: TYPES.SNOOZE_TASK_REQUEST,
		taskId,
	};
}

export function snoozeTaskSuccess( task: Partial< TaskType > ) {
	return {
		type: TYPES.SNOOZE_TASK_SUCCESS,
		task,
	};
}

export function undoSnoozeTaskError( taskId: string, error: unknown ) {
	return {
		type: TYPES.UNDO_SNOOZE_TASK_ERROR,
		taskId,
		error,
	};
}

export function undoSnoozeTaskRequest( taskId: string ) {
	return {
		type: TYPES.UNDO_SNOOZE_TASK_REQUEST,
		taskId,
	};
}

export function undoSnoozeTaskSuccess( task: Partial< TaskType > ) {
	return {
		type: TYPES.UNDO_SNOOZE_TASK_SUCCESS,
		task,
	};
}

export function dismissTaskError( taskId: string, error: unknown ) {
	return {
		type: TYPES.DISMISS_TASK_ERROR,
		taskId,
		error,
	};
}

export function dismissTaskRequest( taskId: string ) {
	return {
		type: TYPES.DISMISS_TASK_REQUEST,
		taskId,
	};
}

export function dismissTaskSuccess( task: Partial< TaskType > ) {
	return {
		type: TYPES.DISMISS_TASK_SUCCESS,
		task,
	};
}

export function undoDismissTaskError( taskId: string, error: unknown ) {
	return {
		type: TYPES.UNDO_DISMISS_TASK_ERROR,
		taskId,
		error,
	};
}

export function undoDismissTaskRequest( taskId: string ) {
	return {
		type: TYPES.UNDO_DISMISS_TASK_REQUEST,
		taskId,
	};
}

export function undoDismissTaskSuccess( task: Partial< TaskType > ) {
	return {
		type: TYPES.UNDO_DISMISS_TASK_SUCCESS,
		task,
	};
}

export function hideTaskListError( taskListId: string, error: unknown ) {
	return {
		type: TYPES.HIDE_TASK_LIST_ERROR,
		taskListId,
		error,
	};
}

export function hideTaskListRequest( taskListId: string ) {
	return {
		type: TYPES.HIDE_TASK_LIST_REQUEST,
		taskListId,
	};
}

export function hideTaskListSuccess( taskList: TaskListType ) {
	return {
		type: TYPES.HIDE_TASK_LIST_SUCCESS,
		taskList,
		taskListId: taskList.id,
	};
}

export function unhideTaskListError( taskListId: string, error: unknown ) {
	return {
		type: TYPES.UNHIDE_TASK_LIST_ERROR,
		taskListId,
		error,
	};
}

export function unhideTaskListRequest( taskListId: string ) {
	return {
		type: TYPES.UNHIDE_TASK_LIST_REQUEST,
		taskListId,
	};
}

export function unhideTaskListSuccess( taskList: TaskListType ) {
	return {
		type: TYPES.UNHIDE_TASK_LIST_SUCCESS,
		taskList,
		taskListId: taskList.id,
	};
}

export function optimisticallyCompleteTaskRequest( taskId: string ) {
	return {
		type: TYPES.OPTIMISTICALLY_COMPLETE_TASK_REQUEST,
		taskId,
	};
}

export function keepCompletedTaskListSuccess(
	taskListId: string,
	keepCompletedList: 'yes' | 'no'
) {
	return {
		type: TYPES.KEEP_COMPLETED_TASKS_SUCCESS,
		taskListId,
		keepCompletedTaskList: keepCompletedList,
	};
}

export function visitedTask( taskId: string ) {
	return {
		type: TYPES.VISITED_TASK,
		taskId,
	};
}

export function setPaymentMethods( paymentMethods: Plugin[] ) {
	return {
		type: TYPES.GET_PAYMENT_METHODS_SUCCESS,
		paymentMethods,
	};
}

export function setEmailPrefill( email: string ) {
	return {
		type: TYPES.SET_EMAIL_PREFILL,
		emailPrefill: email,
	};
}

export function actionTaskError( taskId: string, error: unknown ) {
	return {
		type: TYPES.ACTION_TASK_ERROR,
		taskId,
		error,
	};
}

export function actionTaskRequest( taskId: string ) {
	return {
		type: TYPES.ACTION_TASK_REQUEST,
		taskId,
	};
}

export function actionTaskSuccess( task: Partial< TaskType > ) {
	return {
		type: TYPES.ACTION_TASK_SUCCESS,
		task,
	};
}

export function getProductTypesSuccess( productTypes: OnboardingProductTypes ) {
	return {
		type: TYPES.GET_PRODUCT_TYPES_SUCCESS,
		productTypes,
	};
}

export function getProductTypesError( error: unknown ) {
	return {
		type: TYPES.GET_PRODUCT_TYPES_ERROR,
		error,
	};
}

export function setProfileProgress(
	profileProgress: Partial< CoreProfilerCompletedSteps >
) {
	return {
		type: TYPES.SET_PROFILE_PROGRESS,
		profileProgress,
	};
}

export function* keepCompletedTaskList( taskListId: string ) {
	const updateOptionsParams = {
		woocommerce_task_list_keep_completed: 'yes',
	};
	const response: {
		success: 'yes' | 'no';
	} = yield controls.dispatch(
		OPTIONS_STORE_NAME,
		'updateOptions',
		updateOptionsParams
	);
	if ( response && response.success ) {
		yield keepCompletedTaskListSuccess( taskListId, 'yes' );
	}
}

export function* updateProfileItems( items: ProfileItems ) {
	yield setIsRequesting( 'updateProfileItems', true );
	yield setError( 'updateProfileItems', null );

	try {
		const results: {
			items: ProfileItems;
			status: string;
		} = yield apiFetch( {
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
	} finally {
		yield dispatch( optionsStore ).invalidateResolution( 'getOption', [
			'woocommerce_onboarding_profile',
		] );
		yield dispatch( store ).invalidateResolution( 'getProfileItems', [] );
	}
}

export function* updateCoreProfilerStep( step: CoreProfilerStep ) {
	yield setIsRequesting( 'updateCoreProfilerStep', true );
	yield setError( 'updateCoreProfilerStep', null );

	try {
		const results: {
			results: CoreProfilerStep;
			status: string;
		} = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/onboarding/profile/progress/core-profiler/complete`,
			method: 'POST',
			data: { step },
		} );

		if ( results && results.status === 'success' ) {
			yield setIsRequesting( 'updateCoreProfilerStep', false );
			return results;
		}

		throw new Error();
	} catch ( error ) {
		yield setError( 'updateCoreProfilerStep', error );
		yield setIsRequesting( 'updateCoreProfilerStep', false );
		throw error;
	} finally {
		yield dispatch( store ).invalidateResolution(
			'getProfileProgress',
			[]
		);
		yield dispatch( store ).invalidateResolution(
			'getCoreProfilerCompletedSteps',
			[]
		);
		yield dispatch( store ).invalidateResolution(
			'getMostRecentCoreProfilerStep',
			[]
		);
	}
}

export function* snoozeTask( id: string ) {
	yield snoozeTaskRequest( id );

	try {
		const task: TaskType = yield apiFetch( {
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

export function* undoSnoozeTask( id: string ) {
	yield undoSnoozeTaskRequest( id );

	try {
		const task: TaskType = yield apiFetch( {
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

export function* dismissTask( id: string ) {
	yield dismissTaskRequest( id );

	try {
		const task: TaskType = yield apiFetch( {
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

export function* undoDismissTask( id: string ) {
	yield undoDismissTaskRequest( id );

	try {
		const task: TaskType = yield apiFetch( {
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

export function* hideTaskList( id: string ) {
	yield hideTaskListRequest( id );

	try {
		const taskList: TaskListType = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/onboarding/tasks/${ id }/hide`,
			method: 'POST',
		} );

		yield hideTaskListSuccess( taskList );
	} catch ( error ) {
		yield hideTaskListError( id, error );
		throw new Error();
	}
}

export function* unhideTaskList( id: string ) {
	yield unhideTaskListRequest( id );

	try {
		const taskList: TaskListType = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/onboarding/tasks/${ id }/unhide`,
			method: 'POST',
		} );

		yield unhideTaskListSuccess( taskList );
	} catch ( error ) {
		yield unhideTaskListError( id, error );
		throw new Error();
	}
}

export function* optimisticallyCompleteTask( id: string ) {
	yield optimisticallyCompleteTaskRequest( id );
}

export function* actionTask( id: string ) {
	yield actionTaskRequest( id );

	try {
		const task: TaskType = yield apiFetch( {
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

export function* installAndActivatePluginsAsync(
	plugins: Partial< PluginNames >[]
) {
	yield setIsRequesting( 'installAndActivatePluginsAsync', true );

	try {
		const results: InstallAndActivatePluginsAsyncResponse = yield apiFetch(
			{
				path: `${ WC_ADMIN_NAMESPACE }/onboarding/plugins/install-and-activate-async`,
				method: 'POST',
				data: { plugins },
			}
		);

		return results;
	} catch ( error ) {
		throw error;
	} finally {
		yield setIsRequesting( 'installAndActivatePluginsAsync', false );
	}
}

export function* updateStoreCurrencyAndMeasurementUnits( countryCode: string ) {
	yield setIsRequesting( 'updateStoreCurrencyAndMeasurementUnits', true );

	try {
		const results: {
			results: null;
			status: string;
		} = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/onboarding/profile/update-store-currency-and-measurement-units`,
			method: 'POST',
			data: {
				country_code: countryCode,
			},
		} );
		return results;
	} catch ( error ) {
		throw error;
	} finally {
		yield setIsRequesting(
			'updateStoreCurrencyAndMeasurementUnits',
			false
		);
	}
}

export function setJetpackAuthUrl(
	results: GetJetpackAuthUrlResponse,
	redirectUrl: string,
	from = ''
) {
	return {
		type: TYPES.SET_JETPACK_AUTH_URL,
		results,
		redirectUrl,
		from,
	};
}

export function coreProfilerCompletedError( error: unknown ) {
	return {
		type: TYPES.CORE_PROFILER_COMPLETED_ERROR,
		error,
	};
}

export function coreProfilerCompletedRequest() {
	return {
		type: TYPES.CORE_PROFILER_COMPLETED_REQUEST,
	};
}

export function coreProfilerCompletedSuccess() {
	return {
		type: TYPES.CORE_PROFILER_COMPLETED_SUCCESS,
	};
}

export function* coreProfilerCompleted() {
	yield coreProfilerCompletedRequest();

	try {
		yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/launch-your-store/initialize-coming-soon`,
			method: 'POST',
		} );
	} catch ( error ) {
		yield coreProfilerCompletedError( error );
		throw error;
	} finally {
		yield coreProfilerCompletedSuccess();
	}
}

export type Action = ReturnType<
	| typeof getFreeExtensionsError
	| typeof getFreeExtensionsSuccess
	| typeof setError
	| typeof setIsRequesting
	| typeof setProfileItems
	| typeof snoozeTaskRequest
	| typeof snoozeTaskSuccess
	| typeof snoozeTaskError
	| typeof getTaskListsError
	| typeof getTaskListsSuccess
	| typeof undoSnoozeTaskError
	| typeof undoSnoozeTaskSuccess
	| typeof dismissTaskError
	| typeof dismissTaskSuccess
	| typeof dismissTaskRequest
	| typeof undoDismissTaskError
	| typeof undoDismissTaskSuccess
	| typeof undoDismissTaskRequest
	| typeof undoSnoozeTaskRequest
	| typeof hideTaskListError
	| typeof hideTaskListSuccess
	| typeof hideTaskListRequest
	| typeof unhideTaskListError
	| typeof unhideTaskListSuccess
	| typeof unhideTaskListRequest
	| typeof optimisticallyCompleteTaskRequest
	| typeof keepCompletedTaskListSuccess
	| typeof visitedTask
	| typeof setPaymentMethods
	| typeof setEmailPrefill
	| typeof actionTaskError
	| typeof actionTaskSuccess
	| typeof actionTaskRequest
	| typeof getProductTypesError
	| typeof getProductTypesSuccess
	| typeof setJetpackAuthUrl
	| typeof coreProfilerCompletedRequest
	| typeof coreProfilerCompletedSuccess
	| typeof coreProfilerCompletedError
	| typeof setProfileProgress
>;
