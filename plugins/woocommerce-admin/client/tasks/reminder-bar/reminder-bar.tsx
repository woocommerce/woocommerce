/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	ONBOARDING_STORE_NAME,
	OPTIONS_STORE_NAME,
	TaskListType,
} from '@woocommerce/data';
import { Button } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/settings';
import { close as closeIcon } from '@wordpress/icons';
import interpolateComponents from '@automattic/interpolate-components';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './reminder-bar.scss';

type ReminderBarProps = {
	taskListId: string;
	pageTitle: string;
	updateBodyMargin: () => void;
};

type ReminderTextProps = {
	remainingCount: number;
};

const REMINDER_BAR_HIDDEN_OPTION = 'woocommerce_task_list_reminder_bar_hidden';

const ReminderText: React.FC< ReminderTextProps > = ( { remainingCount } ) => {
	const translationText =
		remainingCount === 1
			? /* translators: 1: remaining tasks count */
			  __(
					'🎉 Almost there. Only {{strongText}}%1$d step left{{/strongText}} get your store up and running. {{setupLink}}Finish setup{{/setupLink}}',
					'woocommerce-admin'
			  )
			: /* translators: 1: remaining tasks count */
			  __(
					"🚀 You're doing great! {{strongText}}%1$d steps left{{/strongText}} to get your store up and running. {{setupLink}}Continue setup{{/setupLink}}",
					'woocommerce-admin'
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
							type="wp-admin"
						/>
					),
				},
			} ) }
		</p>
	);
};

export const TasksReminderBar: React.FC< ReminderBarProps > = ( {
	taskListId = 'setup_experiment_1',
	pageTitle,
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
		const taskList: TaskListType = getTaskList( taskListId );
		const taskListIsResolved = onboardingHasFinishedResolution(
			'getTaskList',
			[ taskListId ]
		);
		const optionIsResolved = optionHasFinishedResolution( 'getOption', [
			REMINDER_BAR_HIDDEN_OPTION,
		] );

		const visibleTasks = taskList?.tasks.filter(
			( task ) =>
				! task.isDismissed &&
				( ! task.isSnoozed || task.snoozedUntil < Date.now() )
		);

		const completedTasks = visibleTasks?.filter(
			( task ) => task.isComplete
		).length;

		const isResolved = taskListIsResolved && optionIsResolved;

		return {
			reminderBarHidden: reminderBarHiddenOption === 'yes',
			taskListHidden: isResolved ? taskList.isHidden : false,
			taskListComplete: isResolved ? taskList.isComplete : false,
			loading: ! isResolved,
			completedTasksCount: completedTasks,
			remainingCount: isResolved
				? visibleTasks?.length - completedTasks
				: null,
		};
	} );

	const hideReminderBar =
		loading ||
		taskListHidden ||
		taskListComplete ||
		reminderBarHidden ||
		completedTasksCount === 0 ||
		[ 'Home', 'Shipping', 'Tax', 'Payments' ].includes( pageTitle );

	useEffect( () => {
		updateBodyMargin();
	}, [ hideReminderBar, updateBodyMargin ] );

	if ( hideReminderBar ) {
		return null;
	}

	return (
		<div className="woocommerce-layout__header-tasks-reminder-bar">
			<ReminderText remainingCount={ remainingCount } />
			<Button
				isSmall
				onClick={ () =>
					updateOptions( {
						[ REMINDER_BAR_HIDDEN_OPTION ]: 'yes',
					} )
				}
				icon={ closeIcon }
			/>
		</div>
	);
};
