/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { MenuGroup, MenuItem } from '@wordpress/components';
import { check } from '@wordpress/icons';
import { Fragment, useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { ONBOARDING_STORE_NAME, OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useExperiment } from '@woocommerce/explat';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { DisplayOption } from '../header/activity-panel/display-options';
import { Task } from './task';
import { TaskList } from './task-list';
import { TasksPlaceholder } from './placeholder';

export type TasksProps = {
	query: { task: string };
};

export const Tasks: React.FC< TasksProps > = ( { query } ) => {
	const { task } = query;
	const { hideTaskList } = useDispatch( ONBOARDING_STORE_NAME );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const [ isLoadingExperiment, experimentAssignment ] = useExperiment(
		'woocommerce_tasklist_progression'
	);

	const { isResolving, taskLists } = useSelect( ( select ) => {
		return {
			isResolving: select( ONBOARDING_STORE_NAME ).isResolving(
				'getTaskLists'
			),
			taskLists: select( ONBOARDING_STORE_NAME ).getTaskLists(),
		};
	} );

	const getCurrentTask = () => {
		if ( ! task ) {
			return null;
		}

		const tasks = taskLists.reduce(
			( acc, taskList ) => [ ...acc, ...taskList.tasks ],
			[]
		);

		const currentTask = tasks.find( ( t ) => t.id === task );

		if ( ! currentTask ) {
			return null;
		}

		return currentTask;
	};

	const toggleTaskList = ( taskList ) => {
		const { id, isHidden } = taskList;
		const newValue = ! isHidden;

		recordEvent(
			newValue ? `${ id }_tasklist_hide` : `${ id }_tasklist_show`,
			{}
		);

		hideTaskList();
	};

	useEffect( () => {
		document.body.classList.add( 'woocommerce-onboarding' );
		document.body.classList.add( 'woocommerce-task-dashboard__body' );
	}, [] );

	useEffect( () => {
		// @todo Update this when all task lists have been hidden or completed.
		const taskListsFinished = false;
		updateOptions( {
			woocommerce_task_list_prompt_shown: true,
			woocommerce_default_homepage_layout: 'two_columns',
		} );
	}, [ taskLists, isResolving ] );

	const currentTask = getCurrentTask();

	if ( task && ! currentTask ) {
		return null;
	}

	if ( isResolving ) {
		return <TasksPlaceholder query={ query } />;
	}

	if ( currentTask ) {
		return (
			<div className="woocommerce-task-dashboard__container">
				<Task query={ query } />
			</div>
		);
	}

	if ( isLoadingExperiment ) {
		return <TasksPlaceholder query={ query } />;
	}

	return taskLists.map( ( taskList ) => {
		const {
			id,
			isComplete,
			isHidden,
			isToggleable,
			title,
			tasks,
		} = taskList;

		if ( isHidden ) {
			return null;
		}

		return (
			<Fragment key={ id }>
				<TaskList
					id={ id }
					isComplete={ isComplete }
					isExpandable={
						experimentAssignment?.variationName === 'treatment'
					}
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
								onClick={ () => toggleTaskList( taskList ) }
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
	} );
};
