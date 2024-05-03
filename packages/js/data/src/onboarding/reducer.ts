/**
 * External dependencies
 */

import type { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { Action } from './actions';
import { OnboardingState, TaskListType, TaskType } from './types';

export const defaultState: OnboardingState = {
	errors: {},
	freeExtensions: [],
	profileItems: {
		business_extensions: null,
		completed: null,
		industry: null,
		number_employees: null,
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
		is_store_country_set: null,
	},
	emailPrefill: '',
	paymentMethods: [],
	productTypes: {},
	requesting: {},
	taskLists: {},
	jetpackAuthUrls: {},
};

const getUpdatedTaskLists = (
	taskLists: Record< string, TaskListType >,
	args: Partial< TaskType >
) => {
	return Object.keys( taskLists ).reduce(
		( lists, taskListId ) => {
			return {
				...lists,
				[ taskListId ]: {
					...taskLists[ taskListId ],
					tasks: taskLists[ taskListId ].tasks.map( ( task ) => {
						if ( args.id === task.id ) {
							return {
								...task,
								...args,
							};
						}
						return task;
					} ),
				},
			};
		},
		{ ...taskLists }
	);
};

const reducer: Reducer< OnboardingState, Action > = (
	state = defaultState,
	action
) => {
	switch ( action.type ) {
		case TYPES.SET_PROFILE_ITEMS:
			return {
				...state,
				profileItems: action.replace
					? action.profileItems
					: { ...state.profileItems, ...action.profileItems },
			};
		case TYPES.SET_EMAIL_PREFILL:
			return {
				...state,
				emailPrefill: action.emailPrefill,
			};
		case TYPES.SET_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					[ action.selector ]: action.error,
				},
			};
		case TYPES.SET_IS_REQUESTING:
			return {
				...state,
				requesting: {
					...state.requesting,
					[ action.selector ]: action.isRequesting,
				},
			};
		case TYPES.GET_PAYMENT_METHODS_SUCCESS:
			return {
				...state,
				paymentMethods: action.paymentMethods,
			};
		case TYPES.GET_PRODUCT_TYPES_SUCCESS:
			return {
				...state,
				productTypes: action.productTypes,
			};
		case TYPES.GET_PRODUCT_TYPES_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					productTypes: action.error,
				},
			};
		case TYPES.GET_FREE_EXTENSIONS_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					getFreeExtensions: action.error,
				},
			};
		case TYPES.GET_FREE_EXTENSIONS_SUCCESS:
			return {
				...state,
				freeExtensions: action.freeExtensions,
			};
		case TYPES.GET_TASK_LISTS_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					getTaskLists: action.error,
				},
			};
		case TYPES.GET_TASK_LISTS_SUCCESS:
			return {
				...state,
				taskLists: action.taskLists.reduce( ( lists, list ) => {
					return {
						...lists,
						[ list.id ]: list,
					};
				}, state.taskLists || {} ),
			};
		case TYPES.DISMISS_TASK_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					dismissTask: action.error,
				},
				taskLists: getUpdatedTaskLists( state.taskLists, {
					id: action.taskId,
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
					id: action.taskId,
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
				taskLists: getUpdatedTaskLists( state.taskLists, action.task ),
			};
		case TYPES.UNDO_DISMISS_TASK_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					undoDismissTask: action.error,
				},
				taskLists: getUpdatedTaskLists( state.taskLists, {
					id: action.taskId,
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
					id: action.taskId,
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
				taskLists: getUpdatedTaskLists( state.taskLists, action.task ),
			};
		case TYPES.SNOOZE_TASK_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					snoozeTask: action.error,
				},
				taskLists: getUpdatedTaskLists( state.taskLists, {
					id: action.taskId,
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
					id: action.taskId,
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
				taskLists: getUpdatedTaskLists( state.taskLists, action.task ),
			};
		case TYPES.UNDO_SNOOZE_TASK_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					undoSnoozeTask: action.error,
				},
				taskLists: getUpdatedTaskLists( state.taskLists, {
					id: action.taskId,
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
					id: action.taskId,
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
				taskLists: getUpdatedTaskLists( state.taskLists, action.task ),
			};
		case TYPES.HIDE_TASK_LIST_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					hideTaskList: action.error,
				},
				taskLists: {
					...state.taskLists,
					[ action.taskListId ]: {
						...state.taskLists[ action.taskListId ],
						isHidden: false,
						isVisible: true,
					},
				},
			};
		case TYPES.HIDE_TASK_LIST_REQUEST:
			return {
				...state,
				requesting: {
					...state.requesting,
					hideTaskList: true,
				},
				taskLists: {
					...state.taskLists,
					[ action.taskListId ]: {
						...state.taskLists[ action.taskListId ],
						isHidden: true,
						isVisible: false,
					},
				},
			};
		case TYPES.HIDE_TASK_LIST_SUCCESS:
			return {
				...state,
				requesting: {
					...state.requesting,
					hideTaskList: false,
				},
				taskLists: {
					...state.taskLists,
					[ action.taskListId ]: action.taskList,
				},
			};
		case TYPES.UNHIDE_TASK_LIST_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					unhideTaskList: action.error,
				},
				taskLists: {
					...state.taskLists,
					[ action.taskListId ]: {
						...state.taskLists[ action.taskListId ],
						isHidden: true,
						isVisible: false,
					},
				},
			};
		case TYPES.UNHIDE_TASK_LIST_REQUEST:
			return {
				...state,
				requesting: {
					...state.requesting,
					unhideTaskList: true,
				},
				taskLists: {
					...state.taskLists,
					[ action.taskListId ]: {
						...state.taskLists[ action.taskListId ],
						isHidden: false,
						isVisible: true,
					},
				},
			};
		case TYPES.UNHIDE_TASK_LIST_SUCCESS:
			return {
				...state,
				requesting: {
					...state.requesting,
					unhideTaskList: false,
				},
				taskLists: {
					...state.taskLists,
					[ action.taskListId ]: action.taskList,
				},
			};
		case TYPES.KEEP_COMPLETED_TASKS_SUCCESS:
			return {
				...state,
				taskLists: {
					...state.taskLists,
					[ action.taskListId ]: {
						...state.taskLists[ action.taskListId ],
						keepCompletedTaskList: action.keepCompletedTaskList,
					},
				},
			};
		case TYPES.OPTIMISTICALLY_COMPLETE_TASK_REQUEST:
			return {
				...state,
				taskLists: getUpdatedTaskLists( state.taskLists, {
					id: action.taskId,
					isComplete: true,
				} ),
			};
		case TYPES.VISITED_TASK:
			return {
				...state,
				taskLists: getUpdatedTaskLists( state.taskLists, {
					id: action.taskId,
					isVisited: true,
				} ),
			};
		case TYPES.ACTION_TASK_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					actionTask: action.error,
				},
				taskLists: getUpdatedTaskLists( state.taskLists, {
					id: action.taskId,
					isActioned: false,
				} ),
			};
		case TYPES.ACTION_TASK_REQUEST:
			return {
				...state,
				requesting: {
					...state.requesting,
					actionTask: true,
				},
				taskLists: getUpdatedTaskLists( state.taskLists, {
					id: action.taskId,
					isActioned: true,
				} ),
			};
		case TYPES.ACTION_TASK_SUCCESS:
			return {
				...state,
				requesting: {
					...state.requesting,
					actionTask: false,
				},
				taskLists: getUpdatedTaskLists( state.taskLists, action.task ),
			};
		case TYPES.SET_JETPACK_AUTH_URL:
			return {
				...state,
				jetpackAuthUrls: {
					...state.jetpackAuthUrls,
					[ action.redirectUrl ]: action.results,
				},
			};
		default:
			return state;
	}
};

export type State = ReturnType< typeof reducer >;
export default reducer;
