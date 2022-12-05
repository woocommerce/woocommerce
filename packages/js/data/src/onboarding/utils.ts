/**
 * Internal dependencies
 */
import { TaskType } from './types';

/**
 * Filters tasks to only visible tasks, taking in account snoozed tasks.
 */
export const getVisibleTasks = ( tasks: TaskType[] ) =>
	tasks.filter( ( task ) => ! task.isDismissed );
