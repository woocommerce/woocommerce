/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, cloneElement } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import classNames from 'classnames';
import { Button, Card, CardBody, CardHeader } from '@wordpress/components';
import { withDispatch, withSelect } from '@wordpress/data';
import { Icon, check } from '@wordpress/icons';
import { List, EllipsisMenu, Badge } from '@woocommerce/components';
import { updateQueryString } from '@woocommerce/navigation';
import {
	PLUGINS_STORE_NAME,
	OPTIONS_STORE_NAME,
	ONBOARDING_STORE_NAME,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import { recordTaskViewEvent } from './tasks';
import { getCountryCode } from '../dashboard/utils';
import sanitizeHTML from '../lib/sanitize-html';

export class TaskList extends Component {
	componentDidMount() {
		this.recordTaskView();
		this.recordTaskListView();
		this.possiblyCompleteTaskList();
		this.possiblyTrackCompletedTasks();
	}

	componentDidUpdate( prevProps ) {
		const { query } = this.props;
		const { query: prevQuery } = prevProps;
		const { task: prevTask } = prevQuery;
		const { task } = query;

		if ( prevTask !== task ) {
			window.document.documentElement.scrollTop = 0;
			this.recordTaskView();
		}

		this.possiblyCompleteTaskList();
		this.possiblyTrackCompletedTasks();
	}

	possiblyCompleteTaskList() {
		const { isComplete, name = 'task_list', updateOptions } = this.props;
		const taskListVariableName = `woocommerce_${ name }_complete`;
		const taskListToComplete = isComplete
			? { [ taskListVariableName ]: 'no' }
			: { [ taskListVariableName ]: 'yes' };
		if ( name === 'task_list' ) {
			taskListToComplete.woocommerce_default_homepage_layout =
				'two_columns';
		}

		if (
			( ! this.getIncompleteTasks().length && ! isComplete ) ||
			( this.getIncompleteTasks().length && isComplete )
		) {
			updateOptions( {
				...taskListToComplete,
			} );
		}
	}

	getCompletedTaskKeys() {
		return this.getVisibleTasks()
			.filter( ( task ) => task.completed )
			.map( ( task ) => task.key );
	}

	getIncompleteTasks() {
		const { dismissedTasks, tasks } = this.props;
		return tasks.filter(
			( task ) =>
				task.visible &&
				! task.completed &&
				! dismissedTasks.includes( task.key )
		);
	}

	shouldUpdateCompletedTasks( tasks, untrackedTasks, completedTasks ) {
		if ( untrackedTasks.length > 0 ) {
			return true;
		}
		if ( completedTasks.length === 0 ) {
			return false;
		}
		return ! completedTasks.every(
			( taskName ) => tasks.indexOf( taskName ) >= 0
		);
	}

	getTrackedCompletedTasks( completedTasks, trackedTasks ) {
		if ( ! trackedTasks ) {
			return [];
		}
		return completedTasks.filter( ( taskName ) =>
			trackedTasks.includes( taskName )
		);
	}

	getTrackedIncompletedTasks( partialCompletedTasks, allTrackedTask ) {
		return this.getVisibleTasks()
			.filter(
				( task ) =>
					allTrackedTask.includes( task.key ) &&
					! partialCompletedTasks.includes( task.key )
			)
			.map( ( task ) => task.key );
	}

	getTasksForUpdate(
		completedTaskKeys,
		totalTrackedCompletedTasks,
		trackedIncompleteTasks
	) {
		const mergedLists = [
			...new Set( [
				...completedTaskKeys,
				...totalTrackedCompletedTasks,
			] ),
		];
		return mergedLists.filter(
			( taskName ) => ! trackedIncompleteTasks.includes( taskName )
		);
	}

	possiblyTrackCompletedTasks() {
		const {
			trackedCompletedTasks: totalTrackedCompletedTasks,
			updateOptions,
		} = this.props;
		const completedTaskKeys = this.getCompletedTaskKeys();
		const trackedCompletedTasks = this.getTrackedCompletedTasks(
			completedTaskKeys,
			totalTrackedCompletedTasks
		);

		const trackedIncompleteTasks = this.getTrackedIncompletedTasks(
			trackedCompletedTasks,
			totalTrackedCompletedTasks
		);

		if (
			this.shouldUpdateCompletedTasks(
				trackedCompletedTasks,
				trackedIncompleteTasks,
				completedTaskKeys
			)
		) {
			updateOptions( {
				woocommerce_task_list_tracked_completed_tasks: this.getTasksForUpdate(
					completedTaskKeys,
					totalTrackedCompletedTasks,
					trackedIncompleteTasks
				),
			} );
		}
	}

	dismissTask( { key, onDismiss } ) {
		const { createNotice, dismissedTasks, updateOptions } = this.props;

		createNotice( 'success', __( 'Task dismissed' ), {
			actions: [
				{
					label: __( 'Undo', 'woocommerce-admin' ),
					onClick: () => this.undoDismissTask( key ),
				},
			],
		} );

		recordEvent( 'tasklist_dismiss_task', { task_name: key } );

		updateOptions( {
			woocommerce_task_list_dismissed_tasks: [ ...dismissedTasks, key ],
		} );
		if ( onDismiss ) {
			onDismiss();
		}
	}

	undoDismissTask( key ) {
		const { dismissedTasks, updateOptions } = this.props;

		const updatedDismissedTasks = dismissedTasks.filter(
			( task ) => task !== key
		);

		updateOptions( {
			woocommerce_task_list_dismissed_tasks: updatedDismissedTasks,
		} );
	}

	getVisibleTasks() {
		const { dismissedTasks, tasks } = this.props;
		return tasks.filter(
			( task ) => task.visible && ! dismissedTasks.includes( task.key )
		);
	}

	recordTaskView() {
		const {
			isJetpackConnected,
			activePlugins,
			installedPlugins,
			query,
		} = this.props;
		const { task: taskName } = query;

		if ( ! taskName ) {
			return;
		}

		recordTaskViewEvent(
			taskName,
			isJetpackConnected,
			activePlugins,
			installedPlugins
		);
	}

	recordTaskListView() {
		if ( this.getCurrentTask() ) {
			return;
		}

		const { name = 'task_list', profileItems } = this.props;
		const isCoreTaskList = name === 'task_list';
		const taskListName = isCoreTaskList ? 'tasklist' : 'extended_tasklist';

		const tasks = this.getVisibleTasks();

		recordEvent( `${ taskListName }_view`, {
			number_tasks: tasks.length,
			store_connected: profileItems.wccom_connected,
		} );
	}

	hideTaskCard( action ) {
		const { name = 'task_list', updateOptions } = this.props;
		const isCoreTaskList = name === 'task_list';
		const taskListName = isCoreTaskList ? 'tasklist' : 'extended_tasklist';
		const updateOptionsParams = {
			[ `woocommerce_${ name }_hidden` ]: 'yes',
		};
		if ( isCoreTaskList ) {
			updateOptionsParams.woocommerce_task_list_prompt_shown = true;
			updateOptionsParams.woocommerce_default_homepage_layout =
				'two_columns';
		}

		recordEvent( `${ taskListName }_completed`, {
			action,
			completed_task_count: this.getCompletedTaskKeys().length,
			incomplete_task_count: this.getIncompleteTasks().length,
		} );
		updateOptions( {
			...updateOptionsParams,
		} );
	}

	getCurrentTask() {
		const { query, tasks } = this.props;
		const { task } = query;
		const currentTask = tasks.find( ( s ) => s.key === task );

		if ( ! currentTask ) {
			return null;
		}

		return currentTask;
	}

	renderMenu() {
		return (
			<div className="woocommerce-card__menu woocommerce-card__header-item">
				<EllipsisMenu
					label={ __( 'Task List Options', 'woocommerce-admin' ) }
					renderContent={ () => (
						<div className="woocommerce-task-card__section-controls">
							<Button
								onClick={ () =>
									this.hideTaskCard( 'remove_card' )
								}
							>
								{ __( 'Hide this', 'woocommerce-admin' ) }
							</Button>
						</div>
					) }
				/>
			</div>
		);
	}

	render() {
		const { query, title: listTitle } = this.props;
		const { task: theTask } = query;
		const currentTask = this.getCurrentTask();

		if ( theTask && ! currentTask ) {
			return null;
		}

		const listTasks = this.getVisibleTasks().map( ( task ) => {
			task.className = classNames(
				task.completed ? 'is-complete' : null,
				task.className
			);
			task.before = (
				<div className="woocommerce-task__icon">
					{ task.completed && <Icon icon={ check } /> }
				</div>
			);

			task.title = (
				<Text
					as="div"
					variant={ task.completed ? 'body.small' : 'button' }
				>
					{ task.title }
					{ task.additionalInfo && (
						<div
							className="woocommerce-task__additional-info"
							dangerouslySetInnerHTML={ sanitizeHTML(
								task.additionalInfo
							) }
						></div>
					) }
					{ task.time && ! task.completed && (
						<div className="woocommerce-task__estimated-time">
							{ task.time }
						</div>
					) }
				</Text>
			);

			if ( ! task.completed && task.isDismissable ) {
				task.after = (
					<Button
						data-testid={ `${ task.key }-dismiss-button` }
						isTertiary
						onClick={ ( event ) => {
							event.stopPropagation();
							this.dismissTask( task );
						} }
					>
						{ __( 'Dismiss', 'woocommerce-admin' ) }
					</Button>
				);
			}

			if ( ! task.onClick ) {
				task.onClick = ( e ) => {
					if ( e.target.nodeName === 'A' ) {
						// This is a nested link, so don't activate this task.
						return false;
					}

					updateQueryString( { task: task.key } );
				};
			}

			return task;
		} );

		if ( ! listTasks.length ) {
			return (
				<div className="woocommerce-task-dashboard__container"></div>
			);
		}

		return (
			<>
				<div className="woocommerce-task-dashboard__container">
					{ currentTask ? (
						cloneElement( currentTask.container, {
							query,
						} )
					) : (
						<>
							<Card
								size="large"
								className="woocommerce-task-card woocommerce-homescreen-card"
							>
								<CardHeader size="medium">
									<div className="wooocommerce-task-card__header">
										<Text variant="title.small">
											{ listTitle }
										</Text>
										<Badge
											count={
												this.getIncompleteTasks().length
											}
										/>
									</div>
									{ this.renderMenu() }
								</CardHeader>
								<CardBody>
									<List items={ listTasks } />
								</CardBody>
							</Card>
						</>
					) }
				</div>
			</>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const { getProfileItems, getTasksStatus } = select(
			ONBOARDING_STORE_NAME
		);
		const { getSettings } = select( SETTINGS_STORE_NAME );
		const {
			getActivePlugins,
			getInstalledPlugins,
			isJetpackConnected,
		} = select( PLUGINS_STORE_NAME );
		const profileItems = getProfileItems();
		const { general: generalSettings = {} } = getSettings( 'general' );
		const countryCode = getCountryCode(
			generalSettings.woocommerce_default_country
		);

		const activePlugins = getActivePlugins();
		const installedPlugins = getInstalledPlugins();
		const onboardingStatus = getTasksStatus();

		return {
			activePlugins,
			countryCode,
			isJetpackConnected: isJetpackConnected(),
			installedPlugins,
			onboardingStatus,
			profileItems,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateOptions } = dispatch( OPTIONS_STORE_NAME );
		const { installAndActivatePlugins } = dispatch( PLUGINS_STORE_NAME );

		return {
			createNotice,
			installAndActivatePlugins,
			updateOptions,
		};
	} )
)( TaskList );
