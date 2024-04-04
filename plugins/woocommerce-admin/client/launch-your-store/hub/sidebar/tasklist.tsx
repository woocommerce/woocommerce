/* eslint-disable @typescript-eslint/ban-ts-comment */
/* eslint-disable @woocommerce/dependency-group */
/**
 * External dependencies
 */
import { ONBOARDING_STORE_NAME, TaskType } from '@woocommerce/data';
import { navigateTo, getNewPath } from '@woocommerce/navigation';
import { resolveSelect } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import classnames from 'classnames';
// @ts-ignore No types for this exist yet.
import SidebarNavigationItem from '@wordpress/edit-site/build-module/components/sidebar-navigation-item';

/**
 * Internal dependencies
 */
import { createStorageUtils } from '~/utils/localStorage';
import { taskCompleteIcon, taskIcons } from './components/icons';

const SEVEN_DAYS_IN_SECONDS = 60 * 60 * 24 * 7;
export const LYS_RECENTLY_ACTIONED_TASKS_KEY = 'lys_recently_actioned_tasks';

export const {
	getWithExpiry: getRecentlyActionedTasks,
	setWithExpiry: saveRecentlyActionedTask,
} = createStorageUtils< string[] >(
	LYS_RECENTLY_ACTIONED_TASKS_KEY,
	SEVEN_DAYS_IN_SECONDS
);

export const getLysTasklist = async () => {
	const LYS_TASKS_WHITELIST = [
		'products',
		'customize-store',
		'woocommerce-payments',
		'payments',
		'shipping',
		'tax',
	];

	/**
	 * This filter allows customizing the list of tasks to show in WooCommerce Launch Your Store feature.
	 *
	 * @filter woocommerce_launch_your_store_tasklist_whitelist
	 * @param {string[]} LYS_TASKS_WHITELIST Default list of task IDs to show in LYS.
	 *
	 */
	const filteredTasks = applyFilters(
		'woocommerce_launch_your_store_tasklist_whitelist',
		[ ...LYS_TASKS_WHITELIST ]
	) as string[];

	const tasklist = await resolveSelect(
		ONBOARDING_STORE_NAME
	).getTaskListsByIds( [ 'setup' ] );

	const recentlyActionedTasks = getRecentlyActionedTasks() ?? [];

	/**
	 * show tasks that fulfill all the following conditions:
	 * 1. part of the whitelist of tasks to show in LYS
	 * 2. either not completed or recently actioned
	 */
	const visibleTasks = tasklist[ 0 ].tasks.filter(
		( task ) =>
			filteredTasks.includes( task.id ) &&
			( ! task.isComplete || recentlyActionedTasks.includes( task.id ) )
	);

	return {
		...tasklist[ 0 ],
		tasks: visibleTasks,
	};
};

export function taskClickedAction( event: {
	type: 'TASK_CLICKED';
	task: TaskType;
} ) {
	const recentlyActionedTasks = getRecentlyActionedTasks() ?? [];
	saveRecentlyActionedTask( [ ...recentlyActionedTasks, event.task.id ] );
	if ( event.task.actionUrl ) {
		navigateTo( { url: event.task.actionUrl } );
	} else {
		navigateTo( {
			url: getNewPath( { task: event.task.id }, '/', {} ),
		} );
	}
}

export const CompletedTaskItem: React.FC< {
	task: TaskType;
	classNames?: string;
} > = ( { task, classNames } ) => (
	<SidebarNavigationItem
		className={ classnames( task.id, 'is-complete', classNames ) }
		icon={ taskCompleteIcon }
		disabled={ true }
	>
		{ task.title }
	</SidebarNavigationItem>
);

export const IncompleteTaskItem: React.FC< {
	task: TaskType;
	classNames?: string;
	onClick: () => void;
} > = ( { task, classNames, onClick } ) => (
	<SidebarNavigationItem
		className={ classnames( task.id, classNames ) }
		icon={ taskIcons[ task.id ] }
		withChevron
		onClick={ onClick }
	>
		{ task.title }
	</SidebarNavigationItem>
);
