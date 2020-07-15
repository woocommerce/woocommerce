/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, cloneElement, Fragment } from '@wordpress/element';
import { isEqual } from 'lodash';
import { compose } from '@wordpress/compose';
import classNames from 'classnames';
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	Modal,
	__experimentalText as Text,
} from '@wordpress/components';
import { withDispatch } from '@wordpress/data';
import { Icon, check, chevronRight } from '@wordpress/icons';

/**
 * WooCommerce dependencies
 */
import { List, EllipsisMenu } from '@woocommerce/components';
import { updateQueryString } from '@woocommerce/navigation';
import {
	PLUGINS_STORE_NAME,
	OPTIONS_STORE_NAME,
	ONBOARDING_STORE_NAME,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './style.scss';
import CartModal from 'dashboard/components/cart-modal';
import { getAllTasks, recordTaskViewEvent } from './tasks';
import { recordEvent } from 'lib/tracks';
import withSelect from 'wc-api/with-select';

class TaskDashboard extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			isCartModalOpen: false,
			isWelcomeModalOpen: ! props.modalDismissed,
		};
	}

	componentDidMount() {
		const { incompleteTasks, updateOptions } = this.props;
		document.body.classList.add( 'woocommerce-onboarding' );
		document.body.classList.add( 'woocommerce-task-dashboard__body' );

		this.recordTaskView();
		this.recordTaskListView();

		if ( ! incompleteTasks.length ) {
			updateOptions( {
				woocommerce_task_list_complete: 'yes',
			} );
		}

		this.possiblyTrackCompletedTasks();
	}

	componentDidUpdate( prevProps ) {
		const {
			completedTaskKeys,
			incompleteTasks,
			query,
			updateOptions,
		} = this.props;
		const {
			completedTaskKeys: prevCompletedTaskKeys,
			incompleteTasks: prevIncompleteTasks,
			query: prevQuery,
		} = prevProps;
		const { task: prevTask } = prevQuery;
		const { task } = query;

		if ( prevTask !== task ) {
			window.document.documentElement.scrollTop = 0;
			this.recordTaskView();
		}

		if ( ! incompleteTasks.length && prevIncompleteTasks.length ) {
			updateOptions( {
				woocommerce_task_list_complete: 'yes',
			} );
		}

		if ( ! isEqual( prevCompletedTaskKeys, completedTaskKeys ) ) {
			this.possiblyTrackCompletedTasks();
		}
	}

	possiblyTrackCompletedTasks() {
		const {
			completedTaskKeys,
			trackedCompletedTasks,
			updateOptions,
		} = this.props;

		if ( ! isEqual( trackedCompletedTasks, completedTaskKeys ) ) {
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

	getTasks() {
		const {
			dismissedTasks,
			profileItems,
			query,
			taskListPayments,
			activePlugins,
			installedPlugins,
			installAndActivatePlugins,
			createNotice,
			isJetpackConnected,
		} = this.props;

		return getAllTasks( {
			profileItems,
			taskListPayments,
			query,
			toggleCartModal: this.toggleCartModal.bind( this ),
			activePlugins,
			installedPlugins,
			installAndActivatePlugins,
			createNotice,
			isJetpackConnected,
		} ).filter(
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
		const tasks = this.getTasks();

		recordEvent( 'tasklist_view', {
			number_tasks: tasks.length,
			store_connected: profileItems.wccom_connected,
		} );
	}

	keepTaskCard() {
		recordEvent( 'tasklist_completed', {
			action: 'keep_card',
		} );

		this.props.updateOptions( {
			woocommerce_task_list_prompt_shown: true,
		} );
	}

	hideTaskCard( action ) {
		recordEvent( 'tasklist_completed', {
			action,
		} );
		this.props.updateOptions( {
			woocommerce_task_list_hidden: 'yes',
			woocommerce_task_list_prompt_shown: true,
		} );
	}

	getCurrentTask() {
		const {
			query,
			profileItems,
			payments,
			activePlugins,
			installedPlugins,
			createNotice,
			isJetpackConnected,
		} = this.props;
		const allTasks = getAllTasks( {
			profileItems,
			options: payments,
			query,
			activePlugins,
			installedPlugins,
			createNotice,
			isJetpackConnected,
		} );
		const { task } = query;
		
		const currentTask = allTasks.find( ( s ) => s.key === task );

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

	closeWelcomeModal() {
		// Prevent firing this event before the modal is seen.
		if (
			document.body.classList.contains( 'woocommerce-admin-is-loading' )
		) {
			return;
		}

		this.setState( { isWelcomeModalOpen: false } );
		this.props.updateOptions( {
			woocommerce_task_list_welcome_modal_dismissed: true,
		} );
	}

	renderWelcomeModal() {
		return (
			<Modal
				title={
					<Fragment>
						<span
							role="img"
							aria-hidden="true"
							focusable="false"
							className="woocommerce-task-dashboard__welcome-modal-icon"
						>
							🚀
						</span>
						{ __(
							"Woo hoo - you're almost there!",
							'woocommerce-admin'
						) }
					</Fragment>
				}
				onRequestClose={ () => this.closeWelcomeModal() }
				className="woocommerce-task-dashboard__welcome-modal"
			>
				<div className="woocommerce-task-dashboard__welcome-modal-wrapper">
					<div className="woocommerce-task-dashboard__welcome-modal-message">
						<p>
							{ __(
								'Based on the information you provided we’ve prepared some final set up tasks for you to perform.',
								'woocommerce-admin'
							) }
						</p>
						<p>
							{ __(
								'Once complete your store will be ready for launch - exciting!',
								'woocommerce-admin'
							) }
						</p>
					</div>
					<Button
						isPrimary
						onClick={ () => this.closeWelcomeModal() }
					>
						{ __( 'Continue', 'woocommerce-admin' ) }
					</Button>
				</div>
			</Modal>
		);
	}

	render() {
		const { query } = this.props;
		const { isCartModalOpen, isWelcomeModalOpen } = this.state;
		const currentTask = this.getCurrentTask();
		const listTasks = this.getTasks().map( ( task ) => {
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
					{ task.time && ! task.completed && (
						<span className="woocommerce-task__estimated-time">
							{ task.time }
						</span>
					) }
				</Text>
			);

			if ( ! task.completed ) {
				task.after = task.isDismissable ? (
					<Button
						isTertiary
						onClick={ ( event ) => {
							event.stopPropagation();
							this.dismissTask( task.key );
						} }
					>
						{ __( 'Dismiss', 'woocommerce-admin' ) }
					</Button>
				) : (
					<Icon icon={ chevronRight } />
				);
			}

			if ( ! task.onClick ) {
				task.onClick = () => updateQueryString( { task: task.key } );
			}

			return task;
		} );
		const numCompleteTasks = listTasks.filter( ( task ) => task.completed )
			.length;
		const progressBarClass = classNames(
			'woocommerce-task-card__progress-bar',
			{
				completed: listTasks.length === numCompleteTasks,
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
									value={ numCompleteTasks }
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
							{ isWelcomeModalOpen && this.renderWelcomeModal() }
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
	withSelect( ( select, props ) => {
		const { getProfileItems } = select( ONBOARDING_STORE_NAME );
		const { getOption } = select( OPTIONS_STORE_NAME );
		const {
			getActivePlugins,
			getInstalledPlugins,
			isJetpackConnected,
		} = select( PLUGINS_STORE_NAME );
		const profileItems = getProfileItems();

		const modalDismissed =
			getOption( 'woocommerce_task_list_welcome_modal_dismissed' ) ||
			false;
		const taskListPayments = getOption( 'woocommerce_task_list_payments' );
		const trackedCompletedTasks =
			getOption( 'woocommerce_task_list_tracked_completed_tasks' ) || [];
		const payments = getOption( 'woocommerce_task_list_payments' );
		const dismissedTasks =
			getOption( 'woocommerce_task_list_dismissed_tasks' ) || [];

		const activePlugins = getActivePlugins();
		const installedPlugins = getInstalledPlugins();
		const { createNotice } = props;
		const tasks = getAllTasks( {
			profileItems,
			options: payments,
			query: props.query,
			activePlugins,
			installedPlugins,
			createNotice,
			isJetpackConnected: isJetpackConnected(),
		} );
		const completedTaskKeys = tasks
			.filter( ( task ) => task.completed )
			.map( ( task ) => task.key );
		const incompleteTasks = tasks.filter(
			( task ) => task.visible && ! task.completed
		);

		return {
			dismissedTasks,
			modalDismissed,
			profileItems,
			taskListPayments,
			isJetpackConnected: isJetpackConnected(),
			incompleteTasks,
			trackedCompletedTasks,
			completedTaskKeys,
			activePlugins,
			installedPlugins,
			payments,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateOptions } = dispatch( OPTIONS_STORE_NAME );
		const { installAndActivatePlugins } = dispatch( PLUGINS_STORE_NAME );

		return {
			createNotice,
			updateOptions,
			installAndActivatePlugins,
		};
	} )
)( TaskDashboard );
