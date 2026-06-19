/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { saveOption } from '../../../settings/data/actions';
import type {
	WooPaymentsOverviewTask,
	WooPaymentsOverviewTasksVisibility,
} from '../types';
import { getVisibleOverviewTasks } from './overview-tasks';

const DAY_IN_MS = 24 * 60 * 60 * 1000;

const normalizeVisibility = (
	visibility: WooPaymentsOverviewTasksVisibility
): WooPaymentsOverviewTasksVisibility => ( {
	dismissed_todo_tasks: [ ...visibility.dismissed_todo_tasks ],
	deleted_todo_tasks: [ ...visibility.deleted_todo_tasks ],
	remind_me_later_todo_tasks: {
		...visibility.remind_me_later_todo_tasks,
	},
} );

export const OverviewTaskList = ( {
	tasks,
	visibility,
}: {
	tasks: WooPaymentsOverviewTask[];
	visibility: WooPaymentsOverviewTasksVisibility;
} ) => {
	const [ localVisibility, setLocalVisibility ] = useState( () =>
		normalizeVisibility( visibility )
	);
	const [ announcement, setAnnouncement ] = useState( '' );
	const sectionRef = useRef< HTMLElement | null >( null );
	const headingRef = useRef< HTMLHeadingElement | null >( null );
	const shouldMoveFocusRef = useRef( false );
	const visibleTasks = useMemo(
		() => getVisibleOverviewTasks( tasks, localVisibility ),
		[ tasks, localVisibility ]
	);

	useEffect( () => {
		if ( ! shouldMoveFocusRef.current ) {
			return;
		}

		shouldMoveFocusRef.current = false;

		const focusTarget =
			sectionRef.current?.querySelector< HTMLElement >(
				'.woocommerce-woopayments-overview-task__actions a[href], .woocommerce-woopayments-overview-task__actions button:not([disabled])'
			) ?? headingRef.current;

		focusTarget?.focus();
	}, [ visibleTasks ] );

	const createUndoNotice = ( message: string, undo: () => void ) => {
		dispatch( 'core/notices' ).createSuccessNotice( message, {
			actions: [
				{
					label: __( 'Undo', 'woocommerce' ),
					onClick: undo,
				},
			],
		} );
	};

	const dismissTask = (
		task: WooPaymentsOverviewTask,
		kind: 'dismissed_todo_tasks' | 'deleted_todo_tasks',
		optionName: string,
		message: string
	) => {
		const previousVisibility = localVisibility;
		const nextItems = Array.from(
			new Set( [ ...localVisibility[ kind ], task.key ] )
		);
		const nextVisibility = {
			...localVisibility,
			[ kind ]: nextItems,
		};

		shouldMoveFocusRef.current = true;
		setLocalVisibility( nextVisibility );
		setAnnouncement( message );
		saveOption( optionName, nextItems );
		createUndoNotice( message, () => {
			shouldMoveFocusRef.current = true;
			setLocalVisibility( previousVisibility );
			setAnnouncement( __( 'Task restored.', 'woocommerce' ) );
			saveOption( optionName, previousVisibility[ kind ] );
		} );
	};

	const snoozeTask = ( task: WooPaymentsOverviewTask ) => {
		const previousVisibility = localVisibility;
		const nextReminders = {
			...localVisibility.remind_me_later_todo_tasks,
			[ task.key ]: Date.now() + DAY_IN_MS,
		};
		const nextVisibility = {
			...localVisibility,
			remind_me_later_todo_tasks: nextReminders,
		};
		const message = __( 'Task postponed until tomorrow.', 'woocommerce' );

		shouldMoveFocusRef.current = true;
		setLocalVisibility( nextVisibility );
		setAnnouncement( message );
		saveOption( 'woocommerce_remind_me_later_todo_tasks', nextReminders );
		createUndoNotice( message, () => {
			shouldMoveFocusRef.current = true;
			setLocalVisibility( previousVisibility );
			setAnnouncement( __( 'Task restored.', 'woocommerce' ) );
			saveOption(
				'woocommerce_remind_me_later_todo_tasks',
				previousVisibility.remind_me_later_todo_tasks
			);
		} );
	};

	if ( visibleTasks.length === 0 && ! announcement ) {
		return null;
	}

	return (
		<section
			ref={ sectionRef }
			className="woocommerce-woopayments-overview__tasks"
			aria-labelledby="woocommerce-woopayments-overview-tasks-heading"
		>
			<h2
				id="woocommerce-woopayments-overview-tasks-heading"
				ref={ headingRef }
				tabIndex={ -1 }
			>
				{ __( 'Things to do', 'woocommerce' ) }
			</h2>
			<p className="screen-reader-text" role="status" aria-live="polite">
				{ announcement }
			</p>
			{ visibleTasks.length > 0 && (
				<ul className="woocommerce-woopayments-overview__task-list">
					{ visibleTasks.map( ( task ) => (
						<li
							key={ task.key }
							className="woocommerce-woopayments-overview-task"
						>
							<div className="woocommerce-woopayments-overview-task__body">
								<h3>{ task.title }</h3>
								{ task.content && <p>{ task.content }</p> }
								{ task.additionalInfo && (
									<p>{ task.additionalInfo }</p>
								) }
							</div>
							<div className="woocommerce-woopayments-overview-task__actions">
								{ task.actionLabel &&
									task.showActionButton !== false &&
									( task.href ? (
										<a
											className="button button-primary"
											href={ task.href }
										>
											{ task.actionLabel }
										</a>
									) : (
										<button
											type="button"
											className="button button-primary"
											onClick={ task.onClick }
										>
											{ task.actionLabel }
										</button>
									) ) }
								{ task.showActionButton === false &&
									task.onClick && (
										<button
											type="button"
											className="button button-secondary"
											onClick={ task.onClick }
										>
											{ task.title }
										</button>
									) }
								{ task.allowSnooze && (
									<button
										type="button"
										className="button button-link"
										onClick={ () => snoozeTask( task ) }
										aria-label={ sprintf(
											/* translators: %s: Task title. */
											__(
												'Remind me later %s',
												'woocommerce'
											),
											task.title
										) }
									>
										{ __(
											'Remind me later',
											'woocommerce'
										) }
									</button>
								) }
								{ task.isDismissable && (
									<button
										type="button"
										className="button button-link"
										onClick={ () =>
											dismissTask(
												task,
												'dismissed_todo_tasks',
												'woocommerce_dismissed_todo_tasks',
												__(
													'Task dismissed.',
													'woocommerce'
												)
											)
										}
										aria-label={ sprintf(
											/* translators: %s: Task title. */
											__( 'Dismiss %s', 'woocommerce' ),
											task.title
										) }
									>
										{ __( 'Dismiss', 'woocommerce' ) }
									</button>
								) }
								{ task.isDeletable && (
									<button
										type="button"
										className="button button-link-delete"
										onClick={ () =>
											dismissTask(
												task,
												'deleted_todo_tasks',
												'woocommerce_deleted_todo_tasks',
												__(
													'Task deleted.',
													'woocommerce'
												)
											)
										}
										aria-label={ sprintf(
											/* translators: %s: Task title. */
											__( 'Delete %s', 'woocommerce' ),
											task.title
										) }
									>
										{ __( 'Delete', 'woocommerce' ) }
									</button>
								) }
							</div>
						</li>
					) ) }
				</ul>
			) }
		</section>
	);
};
