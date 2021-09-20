/**
 * Internal dependencies
 */
import TYPES from './action-types';

export const defaultState = {
	errors: {},
	freeExtensions: [],
	profileItems: {
		business_extensions: null,
		completed: null,
		industry: null,
		other_platform: null,
		other_platform_name: null,
		product_count: null,
		product_types: null,
		revenue: null,
		selling_venues: null,
		setup_client: null,
		skipped: null,
		theme: null,
		wccom_connected: null,
		is_agree_marketing: null,
		store_email: null,
	},
	emailPrefill: '',
	paymentMethods: [],
	requesting: {},
	taskLists: [],
	tasksStatus: {},
};

const getUpdatedTaskLists = ( taskLists, args ) => {
	return taskLists.map( ( taskList ) => {
		return {
			...taskList,
			tasks: taskList.tasks.map( ( task ) => {
				if ( args.id === task.id ) {
					return {
						...task,
						...args,
					};
				}
				return task;
			} ),
		};
	} );
};

const onboarding = (
	state = defaultState,
	{
		freeExtensions,
		type,
		profileItems,
		emailPrefill,
		paymentMethods,
		replace,
		error,
		isRequesting,
		selector,
		task,
		taskId,
		taskListId,
		taskList,
		taskLists,
		tasksStatus,
	}
) => {
	switch ( type ) {
		case TYPES.SET_PROFILE_ITEMS:
			return {
				...state,
				profileItems: replace
					? profileItems
					: { ...state.profileItems, ...profileItems },
			};
		case TYPES.SET_EMAIL_PREFILL:
			return {
				...state,
				emailPrefill,
			};
		case TYPES.SET_TASKS_STATUS:
			return {
				...state,
				tasksStatus: { ...state.tasksStatus, ...tasksStatus },
			};
		case TYPES.SET_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					[ selector ]: error,
				},
			};
		case TYPES.SET_IS_REQUESTING:
			return {
				...state,
				requesting: {
					...state.requesting,
					[ selector ]: isRequesting,
				},
			};
		case TYPES.GET_PAYMENT_METHODS_SUCCESS:
			return {
				...state,
				paymentMethods,
			};
		case TYPES.GET_FREE_EXTENSIONS_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					getFreeExtensions: error,
				},
			};
		case TYPES.GET_FREE_EXTENSIONS_SUCCESS:
			return {
				...state,
				freeExtensions,
			};
		case TYPES.GET_TASK_LISTS_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					getTaskLists: error,
				},
			};
		case TYPES.GET_TASK_LISTS_SUCCESS:
			return {
				...state,
				taskLists,
			};
		case TYPES.DISMISS_TASK_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					dismissTask: error,
				},
				taskLists: getUpdatedTaskLists( state.taskLists, {
					id: taskId,
					isDismissed: false,
				} ),
			};
		case TYPES.DISMISS_TASK_REQUEST:
			return {
				...state,
				requesting: {
					...state.requesting,
					dismissTask: true,
				},
				taskLists: getUpdatedTaskLists( state.taskLists, {
					id: taskId,
					isDismissed: true,
				} ),
			};
		case TYPES.DISMISS_TASK_SUCCESS:
			return {
				...state,
				requesting: {
					...state.requesting,
					dismissTask: false,
				},
				taskLists: getUpdatedTaskLists( state.taskLists, task ),
			};
		case TYPES.UNDO_DISMISS_TASK_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					undoDismissTask: error,
				},
				taskLists: getUpdatedTaskLists( state.taskLists, {
					id: taskId,
					isDismissed: true,
				} ),
			};
		case TYPES.UNDO_DISMISS_TASK_REQUEST:
			return {
				...state,
				requesting: {
					...state.requesting,
					undoDismissTask: true,
				},
				taskLists: getUpdatedTaskLists( state.taskLists, {
					id: taskId,
					isDismissed: false,
				} ),
			};
		case TYPES.UNDO_DISMISS_TASK_SUCCESS:
			return {
				...state,
				requesting: {
					...state.requesting,
					undoDismissTask: false,
				},
				taskLists: getUpdatedTaskLists( state.taskLists, task ),
			};
		case TYPES.SNOOZE_TASK_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					snoozeTask: error,
				},
				taskLists: getUpdatedTaskLists( state.taskLists, {
					id: taskId,
					isSnoozed: false,
				} ),
			};
		case TYPES.SNOOZE_TASK_REQUEST:
			return {
				...state,
				requesting: {
					...state.requesting,
					snoozeTask: true,
				},
				taskLists: getUpdatedTaskLists( state.taskLists, {
					id: taskId,
					isSnoozed: true,
				} ),
			};
		case TYPES.SNOOZE_TASK_SUCCESS:
			return {
				...state,
				requesting: {
					...state.requesting,
					snoozeTask: false,
				},
				taskLists: getUpdatedTaskLists( state.taskLists, task ),
			};
		case TYPES.UNDO_SNOOZE_TASK_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					undoSnoozeTask: error,
				},
				taskLists: getUpdatedTaskLists( state.taskLists, {
					id: taskId,
					isSnoozed: true,
				} ),
			};
		case TYPES.UNDO_SNOOZE_TASK_REQUEST:
			return {
				...state,
				requesting: {
					...state.requesting,
					undoSnoozeTask: true,
				},
				taskLists: getUpdatedTaskLists( state.taskLists, {
					id: taskId,
					isSnoozed: false,
				} ),
			};
		case TYPES.UNDO_SNOOZE_TASK_SUCCESS:
			return {
				...state,
				requesting: {
					...state.requesting,
					undoSnoozeTask: false,
				},
				taskLists: getUpdatedTaskLists( state.taskLists, task ),
			};
		case TYPES.HIDE_TASK_LIST_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					hideTaskList: error,
				},
				taskLists: state.taskLists.map( ( list ) => {
					if ( taskListId === list.id ) {
						return {
							...list,
							isHidden: false,
						};
					}
					return list;
				} ),
			};
		case TYPES.HIDE_TASK_LIST_REQUEST:
			return {
				...state,
				requesting: {
					...state.requesting,
					hideTaskList: true,
				},
				taskLists: state.taskLists.map( ( list ) => {
					if ( taskListId === list.id ) {
						return {
							...list,
							isHidden: true,
						};
					}
					return list;
				} ),
			};
		case TYPES.HIDE_TASK_LIST_SUCCESS:
			return {
				...state,
				requesting: {
					...state.requesting,
					hideTaskList: false,
				},
				taskLists: state.taskLists.map( ( list ) => {
					return taskListId === list.id ? taskList : list;
				} ),
			};
		default:
			return state;
	}
};

export default onboarding;
