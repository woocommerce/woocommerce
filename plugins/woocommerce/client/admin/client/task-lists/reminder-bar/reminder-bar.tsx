/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	ONBOARDING_STORE_NAME,
	OPTIONS_STORE_NAME,
	TaskType,
	getVisibleTasks,
} from '@woocommerce/data';
import { Button } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/settings';
import { close as closeIcon } from '@wordpress/icons';
import interpolateComponents from '@automattic/interpolate-components';
import { useEffect } from '@wordpress/element';
import { getQuery } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './reminder-bar.scss';

type ReminderBarProps = {
	taskListId: string;
	updateBodyMargin: () => void;
};

type ReminderTextProps = {
	remainingCount: number | null;
	tracksProps: {
		completed: number;
		is_homescreen: boolean;
		is_active_task_page: boolean;
	};
};

const REMINDER_BAR_HIDDEN_OPTION = 'woocommerce_task_list_reminder_bar_hidden';

const ReminderText: React.FC< ReminderTextProps > = ( {
	remainingCount,
	tracksProps,
} ) => {
	const translationText =
		remainingCount === 1
			? /* translators: 1: remaining tasks count */
			  __(
					'🎉 Almost there. Only {{strongText}}%1$d step left{{/strongText}} get your store up and running. {{setupLink}}Finish setup{{/setupLink}}',
					'woocommerce'
			  )
			: /* translators: 1: remaining tasks count */
			  __(
					'🚀 You’re doing great! {{strongText}}%1$d steps left{{/strongText}} to get your store up and running. {{setupLink}}Continue setup{{/setupLink}}',
					'woocommerce'
			  );

	return (
		<p>
			{ interpolateComponents( {
				mixedString: sprintf( translationText, remainingCount ),
				components: {
					strongText: <strong />,
					setupLink: (
						<Link
							href={ getAdminLink( 'admin.php?page=wc-admin' ) }
							onClick={ () =>
								recordEvent(
									'tasklist_reminder_bar_continue',
									tracksProps
								)
							}
							type="wp-admin"
						>
							<></>
						</Link>
					),
				},
			} ) }
		</p>
	);
};

export const TasksReminderBar: React.FC< ReminderBarProps > = ( {
	taskListId,
	updateBodyMargin,
} ) => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const {
		remainingCount,
		loading,
		taskListHidden,
		taskListComplete,
		reminderBarHidden,
		completedTasksCount,
	} = useSelect( ( select ) => {
		const {
			getTaskList,
			hasFinishedResolution: onboardingHasFinishedResolution,
		} = select( ONBOARDING_STORE_NAME );
		const {
			getOption,
			hasFinishedResolution: optionHasFinishedResolution,
		} = select( OPTIONS_STORE_NAME );
		const reminderBarHiddenOption = getOption( REMINDER_BAR_HIDDEN_OPTION );
		const taskList = getTaskList( taskListId );
		const taskListIsResolved = onboardingHasFinishedResolution(
			'getTaskList',
			[ taskListId ]
		);
		const optionIsResolved = optionHasFinishedResolution( 'getOption', [
			REMINDER_BAR_HIDDEN_OPTION,
		] );

		const visibleTasks = getVisibleTasks( taskList?.tasks || [] );

		const completedTasks =
			visibleTasks.filter( ( task: TaskType ) => task.isComplete ) || [];

		const isResolved = taskListIsResolved && optionIsResolved;

		return {
			reminderBarHidden: reminderBarHiddenOption === 'yes',
			taskListHidden: isResolved ? taskList?.isHidden : false,
			taskListComplete: isResolved ? taskList?.isComplete : false,
			loading: ! isResolved,
			completedTasksCount: completedTasks.length,
			remainingCount: isResolved
				? visibleTasks?.length - completedTasks.length
				: null,
		};
	} );

	const query = getQuery() as { [ key: string ]: string };
	const isHomescreen =
		query.page && query.page === 'wc-admin' && ! query.path;
	const isActiveTaskPage = Boolean( query.wc_onboarding_active_task );

	const isHidden =
		loading ||
		taskListHidden ||
		taskListComplete ||
		reminderBarHidden ||
		completedTasksCount === 0 ||
		isHomescreen ||
		isActiveTaskPage;

	useEffect( () => {
		updateBodyMargin();
	}, [ isHidden, updateBodyMargin ] );

	const tracksProps = {
		completed: completedTasksCount,
		is_homescreen: !! isHomescreen,
		is_active_task_page: isActiveTaskPage,
	};

	useEffect( () => {
		if ( loading || isHidden ) {
			return;
		}

		recordEvent( 'tasklist_reminder_bar_view', tracksProps );
	}, [ isHidden, loading ] );

	const onClose = () => {
		updateOptions( {
			[ REMINDER_BAR_HIDDEN_OPTION ]: 'yes',
		} );
		recordEvent( 'tasklist_reminder_bar_close', tracksProps );
	};

	if ( isHidden ) {
		return null;
	}

	return (
		<div className="woocommerce-layout__header-tasks-reminder-bar">
			<ReminderText
				remainingCount={ remainingCount }
				tracksProps={ tracksProps }
			/>
			<Button isSmall onClick={ onClose } icon={ closeIcon } />
		</div>
	);
};
