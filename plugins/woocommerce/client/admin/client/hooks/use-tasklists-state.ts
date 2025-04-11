/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import {
	ONBOARDING_STORE_NAME,
	getVisibleTasks,
	TaskListType,
} from '@woocommerce/data';
/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';

// TODO: replace this with the actual selectors when @woocommerce/data types are updated.
type Selectors = {
	getTaskList: ( taskListId: string ) => TaskListType;
	hasFinishedResolution: ( action: string ) => boolean;
};

/**
 * Get the number of things to do next
 *
 * @param {Object} extendedTaskList The extended task list
 * @return {number} The number of things to do next
 */
function getThingsToDoNextCount( extendedTaskList: TaskListType ) {
	if (
		! extendedTaskList ||
		! extendedTaskList.tasks.length ||
		extendedTaskList.isHidden
	) {
		return 0;
	}
	return extendedTaskList.tasks.filter(
		( task ) => task.canView && ! task.isComplete && ! task.isDismissed
	).length;
}

/**
 * Check if a task list is visible
 *
 * @param {string} taskListId The ID of the task list to check
 * @return {boolean} True if the task list is visible, false otherwise
 */
export const isTaskListVisible = ( taskListId: string ) =>
	getAdminSetting( 'visibleTaskListIds', [] ).includes( taskListId );

/**
 * Check if a task list is completed
 *
 * @param {string} taskListId The ID of the task list to check
 * @return {boolean} True if the task list is completed, false otherwise
 */
export const isTaskListCompleted = ( taskListId: string ) =>
	getAdminSetting( 'completedTaskListIds', [] ).includes( taskListId );

/**
 * Check if a task list is active (visible and not completed)
 *
 * @param {string} taskListId The ID of the task list to check
 * @return {boolean} True if the task list is active, false otherwise
 */
export const isTaskListActive = ( taskListId: string ) =>
	isTaskListVisible( taskListId ) && ! isTaskListCompleted( taskListId );

/**
 * Get default state values when task lists are not visible
 *
 * @return {Object} Default state values
 */
const getDefaultState = () => {
	const setupTaskListHidden = ! isTaskListVisible( 'setup' );
	const setupTaskListComplete = isTaskListCompleted( 'setup' );
	const setupTaskListActive = isTaskListActive( 'setup' );

	return {
		requestingTaskListOptions: false,
		setupTaskListHidden,
		setupTaskListComplete,
		setupTaskListActive,
		setupTasksCount: undefined,
		setupTasksCompleteCount: undefined,
		thingsToDoNextCount: undefined,
	};
};

/**
 * Get setup task list related states
 *
 * @param {Object} selectors Store selectors
 * @return {Object} Setup task list states
 */
const getSetupTaskListState = ( selectors: Selectors ) => {
	const { getTaskList, hasFinishedResolution } = selectors;
	const setupList = getTaskList( 'setup' );
	const setupVisibleTasks = getVisibleTasks( setupList?.tasks || [] );

	// Use the task list object to determine the state to override the default state.
	const setupTaskListHidden = setupList ? setupList.isHidden : true;
	const setupTaskListComplete = setupList?.isComplete;
	const setupTaskListActive =
		! setupTaskListHidden && ! setupTaskListComplete;

	return {
		setupTaskListHidden,
		setupTaskListComplete,
		setupTaskListActive,
		setupTasksCount: setupVisibleTasks.length,
		setupTasksCompleteCount: setupVisibleTasks.filter(
			( task ) => task.isComplete
		).length,
		requestingTaskListOptions: ! hasFinishedResolution( 'getTaskLists' ),
	};
};

/**
 * Get extended task list states
 *
 * @param {Object} selectors Store selectors
 * @return {Object} Extended task list states
 */
const getExtendedTaskListState = ( selectors: Selectors ) => {
	const { getTaskList, hasFinishedResolution } = selectors;
	const extendedTaskList = getTaskList( 'extended' );

	return {
		thingsToDoNextCount: getThingsToDoNextCount( extendedTaskList ),
		requestingTaskListOptions: ! hasFinishedResolution( 'getTaskLists' ),
	};
};

/**
 * Hook to get task list states
 *
 * This will only return the state for the task list that is currently visible.
 *
 * @param {Object}  options                  The options object
 * @param {boolean} options.setupTasklist    Whether to include the setup task list in the state
 * @param {boolean} options.extendedTaskList Whether to include the extended task list in the state
 *
 * @return {Object} Task list related states
 */
export const useTaskListsState = (
	{ setupTasklist, extendedTaskList } = {
		setupTasklist: true,
		extendedTaskList: true,
	}
) => {
	const shouldGetSetupTaskList = setupTasklist && isTaskListActive( 'setup' );
	const shouldGetExtendedTaskList =
		extendedTaskList && isTaskListActive( 'extended' );

	return useSelect(
		( select ) => {
			// If no task lists are visible, return default state
			if ( ! shouldGetSetupTaskList && ! shouldGetExtendedTaskList ) {
				return getDefaultState();
			}

			const selectors = select( ONBOARDING_STORE_NAME );

			// If setup task list is not visible, return extended-only state
			if ( ! shouldGetSetupTaskList ) {
				return {
					...getDefaultState(),
					...getExtendedTaskListState( selectors ),
				};
			}

			// If extended task list is not visible, return setup-only state
			if ( ! shouldGetExtendedTaskList ) {
				return {
					...getDefaultState(),
					...getSetupTaskListState( selectors ),
				};
			}

			// Return full state with both setup and extended task lists
			return {
				...getDefaultState(),
				...getSetupTaskListState( selectors ),
				...getExtendedTaskListState( selectors ),
			};
		},
		[ shouldGetSetupTaskList, shouldGetExtendedTaskList ]
	);
};
