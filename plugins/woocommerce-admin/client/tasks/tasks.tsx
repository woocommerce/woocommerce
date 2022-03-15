/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { MenuGroup, MenuItem } from '@wordpress/components';
import { check } from '@wordpress/icons';
import { Fragment, useEffect, lazy, Suspense } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { ONBOARDING_STORE_NAME, OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useExperiment } from '@woocommerce/explat';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { DisplayOption } from '~/activity-panel/display-options';
import { Task } from './task';
import { TasksPlaceholder } from './placeholder';
import './tasks.scss';
import { TaskListProps } from './task-list';
import '../two-column-tasks/style.scss';

export type TasksProps = {
	query: { task?: string };
};

const TaskList = lazy(
	() => import( /* webpackChunkName: "task-list" */ './task-list' )
);

const TwoColumnTaskList = lazy(
	() =>
		import(
			/* webpackChunkName: "two-column-task-list" */ '../two-column-tasks/task-list'
		)
);

function getTaskListComponent(
	taskListId: string
): React.LazyExoticComponent< React.FC< TaskListProps > > {
	switch ( taskListId ) {
		case 'setup_experiment_1':
			return TwoColumnTaskList;
		default:
			return TaskList;
	}
}

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
		const { id, eventPrefix, isHidden } = taskList;
		const newValue = ! isHidden;

		recordEvent(
			newValue ? `${ eventPrefix }hide` : `${ eventPrefix }show`,
			{}
		);

		hideTaskList( id );
	};

	useEffect( () => {
		// @todo Update this when all task lists have been hidden or completed.
		const taskListsFinished = false;
		updateOptions( {
			woocommerce_task_list_prompt_shown: true,
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
				<Task query={ query } task={ currentTask } />
			</div>
		);
	}

	if ( isLoadingExperiment ) {
		return <TasksPlaceholder query={ query } />;
	}

	return taskLists
		.filter( ( { id } ) =>
			experimentAssignment?.variationName === 'treatment'
				? id.endsWith( 'two_column' )
				: ! id.endsWith( 'two_column' )
		)
		.map( ( taskList ) => {
			const {
				id,
				eventPrefix,
				isComplete,
				isHidden,
				isVisible,
				isToggleable,
				title,
				tasks,
			} = taskList;

			if ( ! isVisible ) {
				return null;
			}

			const TaskListComponent = getTaskListComponent( id );

			return (
				<Fragment key={ id }>
					<Suspense fallback={ null }>
						<TaskListComponent
							id={ id }
							eventPrefix={ eventPrefix }
							isComplete={ isComplete }
							isExpandable={
								experimentAssignment?.variationName ===
								'treatment'
							}
							query={ query }
							tasks={ tasks }
							title={ title }
							twoColumns={ false }
						/>
					</Suspense>
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
