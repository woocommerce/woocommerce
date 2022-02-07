/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { MenuGroup, MenuItem } from '@wordpress/components';
import { check } from '@wordpress/icons';
import { Fragment, useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { ONBOARDING_STORE_NAME, OPTIONS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { DisplayOption } from '~/activity-panel/display-options';
import { TaskList } from '../tasks/task-list';
import { TasksPlaceholder } from '../tasks/placeholder';
import '../tasks/tasks.scss';
import allowedTasks from './allowed-tasks';

export type TasksProps = {
	query: { task?: string };
};

const ExtendedTask: React.FC< TasksProps > = ( { query } ) => {
	const { hideTaskList } = useDispatch( ONBOARDING_STORE_NAME );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { task } = query;

	const { isResolving, taskLists } = useSelect( ( select ) => {
		return {
			isResolving: select( ONBOARDING_STORE_NAME ).isResolving(
				'getTaskLists'
			),
			taskLists: select( ONBOARDING_STORE_NAME ).getTaskLists(),
		};
	} );

	const toggleTaskList = ( taskList ) => {
		const { id, isHidden } = taskList;
		const newValue = ! isHidden;

		recordEvent(
			newValue ? `${ id }_tasklist_hide` : `${ id }_tasklist_show`,
			{}
		);

		hideTaskList( id );
	};

	useEffect( () => {
		updateOptions( {
			woocommerce_task_list_prompt_shown: true,
		} );
	}, [ taskLists, isResolving ] );

	// If a task detail is being shown, we shouldn't show the extended tasklist.
	if ( task ) {
		return null;
	}

	if ( isResolving ) {
		return <TasksPlaceholder query={ query } />;
	}

	const extendedTaskList = taskLists.find( ( list ) => {
		return list.id === 'extended';
	} );

	// See if we need to move any of the main tasks to the extended task list
	const setupTaskList = taskLists.find( ( list ) => {
		return list.id === 'setup';
	} );

	extendedTaskList.tasks = [
		...new Set(
			extendedTaskList.tasks.concat(
				setupTaskList?.tasks.filter( ( unallowedTask ) => {
					return (
						! allowedTasks.includes( unallowedTask.id ) &&
						unallowedTask.id !== 'store_details'
					);
				} ) || []
			)
		),
	];

	if ( extendedTaskList.tasks.length === 0 ) {
		return null;
	}

	const completedTasks = extendedTaskList.tasks.filter(
		( extendedTaskListTask ) => {
			return extendedTaskListTask.completed;
		}
	);

	// Use custom isComplete since we're manually adding tasks
	// to the extended task list.
	const isComplete = completedTasks.length === extendedTaskList.tasks.length;

	const {
		id,
		isHidden,
		isVisible,
		isToggleable,
		title,
		tasks,
	} = extendedTaskList;

	if ( ! isVisible ) {
		return null;
	}

	return (
		<Fragment key={ id }>
			<TaskList
				id={ id }
				isComplete={ isComplete }
				query={ query }
				tasks={ tasks }
				title={ title }
			/>
			{ isToggleable && (
				<DisplayOption>
					<MenuGroup
						className="woocommerce-layout__homescreen-display-options"
						label={ __( 'Display', 'woocommerce-admin' ) }
					>
						<MenuItem
							className="woocommerce-layout__homescreen-extension-tasklist-toggle"
							icon={ ! isHidden && check }
							isSelected={ ! isHidden }
							role="menuitemcheckbox"
							onClick={ () => toggleTaskList( extendedTaskList ) }
						>
							{ __(
								'Show things to do next',
								'woocommerce-admin'
							) }
						</MenuItem>
					</MenuGroup>
				</DisplayOption>
			) }
		</Fragment>
	);
};

export default ExtendedTask;
