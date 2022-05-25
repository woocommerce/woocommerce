/**
 * Internal dependencies
 */
import { TaskType } from './types';

/**
 * Filters tasks to only visible tasks, taking in account snoozed tasks.
 */
export function getVisibleTasks( tasks: TaskType[] ) {
	const nowTimestamp = Date.now();
	return tasks.filter(
		( task ) =>
			! task.isDismissed &&
			( ! task.isSnoozed || task.snoozedUntil < nowTimestamp )
	);
}
