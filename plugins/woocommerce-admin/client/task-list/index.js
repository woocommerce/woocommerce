/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, cloneElement, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import classNames from 'classnames';
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	__experimentalText as Text,
} from '@wordpress/components';
import { withDispatch, withSelect } from '@wordpress/data';
import { Icon, check } from '@wordpress/icons';
import { xor } from 'lodash';
import { List, EllipsisMenu } from '@woocommerce/components';
import { updateQueryString } from '@woocommerce/navigation';
import {
	PLUGINS_STORE_NAME,
	OPTIONS_STORE_NAME,
	ONBOARDING_STORE_NAME,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './style.scss';
import CartModal from '../dashboard/components/cart-modal';
import { getAllTasks, recordTaskViewEvent } from './tasks';
import { getCountryCode } from '../dashboard/utils';
import sanitizeHTML from '../lib/sanitize-html';

class TaskDashboard extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			isCartModalOpen: false,
		};
	}

	componentDidMount() {
		document.body.classList.add( 'woocommerce-onboarding' );
		document.body.classList.add( 'woocommerce-task-dashboard__body' );

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
		const { isTaskListComplete, updateOptions } = this.props;

		if ( ! this.getIncompleteTasks().length && ! isTaskListComplete ) {
			updateOptions( {
				woocommerce_task_list_complete: 'yes',
			} );
		}
	}

	getCompletedTaskKeys() {
		return this.getVisibleTasks()
			.filter( ( task ) => task.completed )
			.map( ( task ) => task.key );
	}

	getIncompleteTasks() {
		return this.getAllTasks().filter(
			( task ) => task.visible && ! task.completed
		);
	}

	possiblyTrackCompletedTasks() {
		const { trackedCompletedTasks, updateOptions } = this.props;
		const completedTaskKeys = this.getCompletedTaskKeys();

		if ( xor( trackedCompletedTasks, completedTaskKeys ).length !== 0 ) {
			updateOptions( {
				woocommerce_task_list_tracked_completed_tasks: completedTaskKeys,
			} );
		}
	}

	dismissTask( key ) {
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

	componentWillUnmount() {
		document.body.classList.remove( 'woocommerce-onboarding' );
		document.body.classList.remove( 'woocommerce-task-dashboard__body' );
	}

	getAllTasks() {
		const {
			activePlugins,
			countryCode,
			createNotice,
			installAndActivatePlugins,
			installedPlugins,
			isJetpackConnected,
			onboardingStatus,
			profileItems,
			query,
		} = this.props;

		return getAllTasks( {
			activePlugins,
			countryCode,
			createNotice,
			installAndActivatePlugins,
			installedPlugins,
			isJetpackConnected,
			onboardingStatus,
			profileItems,
			query,
			toggleCartModal: this.toggleCartModal.bind( this ),
		} );
	}

	getVisibleTasks() {
		const { dismissedTasks } = this.props;

		return this.getAllTasks().filter(
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

		const { profileItems } = this.props;
		const tasks = this.getVisibleTasks();

		recordEvent( 'tasklist_view', {
			number_tasks: tasks.length,
			store_connected: profileItems.wccom_connected,
		} );
	}

	hideTaskCard( action ) {
		recordEvent( 'tasklist_completed', {
			action,
			completed_task_count: this.getCompletedTaskKeys().length,
			incomplete_task_count: this.getIncompleteTasks().length,
		} );
		this.props.updateOptions( {
			woocommerce_task_list_hidden: 'yes',
			woocommerce_task_list_prompt_shown: true,
		} );
	}

	getCurrentTask() {
		const { query } = this.props;
		const { task } = query;
		const currentTask = this.getAllTasks().find( ( s ) => s.key === task );

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

	toggleCartModal() {
		const { isCartModalOpen } = this.state;

		if ( ! isCartModalOpen ) {
			recordEvent( 'tasklist_purchase_extensions' );
		}

		this.setState( { isCartModalOpen: ! isCartModalOpen } );
	}

	render() {
		const { query } = this.props;
		const { isCartModalOpen } = this.state;
		const currentTask = this.getCurrentTask();
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
							this.dismissTask( task.key );
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
		const progressBarClass = classNames(
			'woocommerce-task-card__progress-bar',
			{
				completed:
					listTasks.length === this.getCompletedTaskKeys().length,
			}
		);

		return (
			<Fragment>
				<div className="woocommerce-task-dashboard__container">
					{ currentTask ? (
						cloneElement( currentTask.container, {
							query,
						} )
					) : (
						<Fragment>
							<Card
								size="large"
								className="woocommerce-task-card woocommerce-dashboard-card"
							>
								<progress
									className={ progressBarClass }
									max={ listTasks.length }
									value={ this.getCompletedTaskKeys().length }
								/>
								<CardHeader size="medium">
									<Text variant="title.small">
										{ __(
											'Finish setup',
											'woocommerce-admin'
										) }
									</Text>
									{ this.renderMenu() }
								</CardHeader>
								<CardBody>
									<List items={ listTasks } />
								</CardBody>
							</Card>
						</Fragment>
					) }
				</div>
				{ isCartModalOpen && (
					<CartModal
						onClose={ () => this.toggleCartModal() }
						onClickPurchaseLater={ () => this.toggleCartModal() }
					/>
				) }
			</Fragment>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const { getProfileItems, getTasksStatus } = select(
			ONBOARDING_STORE_NAME
		);
		const { getOption } = select( OPTIONS_STORE_NAME );
		const { getSettings } = select( SETTINGS_STORE_NAME );
		const {
			getActivePlugins,
			getInstalledPlugins,
			isJetpackConnected,
		} = select( PLUGINS_STORE_NAME );
		const profileItems = getProfileItems();

		const isTaskListComplete =
			getOption( 'woocommerce_task_list_complete' ) === 'yes';
		const trackedCompletedTasks =
			getOption( 'woocommerce_task_list_tracked_completed_tasks' ) || [];
		const dismissedTasks =
			getOption( 'woocommerce_task_list_dismissed_tasks' ) || [];

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
			dismissedTasks,
			isJetpackConnected: isJetpackConnected(),
			installedPlugins,
			isTaskListComplete,
			onboardingStatus,
			profileItems,
			trackedCompletedTasks,
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
)( TaskDashboard );
