/* eslint-disable @typescript-eslint/ban-ts-comment */
/* eslint-disable @woocommerce/dependency-group */
/**
 * External dependencies
 */
import { onboardingStore, TaskType } from '@woocommerce/data';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { resolveSelect } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import clsx from 'clsx';
// @ts-ignore No types for this exist yet.
import SidebarNavigationItem from '@wordpress/edit-site/build-module/components/sidebar-navigation-item';

/**
 * Internal dependencies
 */
import { taskCompleteIcon, taskIcons } from './components/icons';
import { recordEvent } from '@woocommerce/tracks';
import {
	accessTaskReferralStorage,
	createStorageUtils,
} from '@woocommerce/onboarding';
import { getAdminLink } from '@woocommerce/settings';
import { recordPaymentsOnboardingEvent } from '~/settings-payments/utils';
import { wooPaymentsOnboardingSessionEntryLYS } from '~/settings-payments/constants';

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

	const tasklist = await resolveSelect( onboardingStore ).getTaskListsByIds( [
		'setup',
	] );

	const recentlyActionedTasks = getRecentlyActionedTasks() ?? [];

	/**
	 * Show tasks that fulfill all the following conditions:
	 * 1. part of the whitelist of tasks to show in LYS
	 * 2. either not completed or recently actioned
	 */
	const visibleTasks = tasklist[ 0 ].tasks.filter(
		( task: TaskType ) =>
			filteredTasks.includes( task.id ) &&
			( ! task.isComplete || recentlyActionedTasks.includes( task.id ) )
	);

	return {
		...tasklist[ 0 ],
		tasks: visibleTasks,
		recentlyActionedTasks,
		fullLysTaskList: tasklist[ 0 ].tasks.filter( ( task: TaskType ) =>
			filteredTasks.includes( task.id )
		),
	};
};

/**
 * Helper function to fetch the payments task from the LYS tasklist.
 * Handles validation and error logging.
 *
 * @return {Promise<TaskType | undefined>} The payments task or undefined if not found or on error.
 */
export const getPaymentsTaskFromLysTasklist = async (): Promise<
	TaskType | undefined
> => {
	try {
		const tasklist = await getLysTasklist();

		// Validate that fullLysTaskList is an array
		if ( ! Array.isArray( tasklist?.fullLysTaskList ) ) {
			// eslint-disable-next-line no-console
			console.error(
				'Invalid tasklist data: fullLysTaskList is not an array'
			);
			return undefined;
		}

		return tasklist.fullLysTaskList.find(
			( task ) => task.id === 'payments'
		);
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( 'Error fetching payments task:', error );
		return undefined;
	}
};

export function taskClickedAction( event: {
	type: 'TASK_CLICKED';
	task: TaskType;
} ) {
	const recentlyActionedTasks = getRecentlyActionedTasks() ?? [];
	saveRecentlyActionedTask( [ ...recentlyActionedTasks, event.task.id ] );
	window.sessionStorage.setItem( 'lysWaiting', 'yes' );

	const { setWithExpiry: saveTaskReferral } = accessTaskReferralStorage(
		{ taskId: event.task.id, referralLifetime: 60 * 60 * 24 } // 24 hours
	);

	saveTaskReferral( {
		referrer: 'launch-your-store',
		returnUrl: getAdminLink(
			'admin.php?page=wc-admin&path=/launch-your-store'
		),
	} );

	recordEvent( 'launch_your_store_hub_task_clicked', {
		task: event.task.id,
	} );

	if ( event.task.id === 'payments' ) {
		const {
			wooPaymentsIsActive,
			wooPaymentsSettingsCountryIsSupported,
			wooPaymentsIsOnboarded,
			wooPaymentsHasTestAccount,
			wooPaymentsHasOtherProvidersEnabled,
			wooPaymentsHasOtherProvidersNeedSetup,
		} = event.task?.additionalData ?? {};

		if (
			// Only show the NOX if the store is in a WooPayments-supported geo, and:
			wooPaymentsSettingsCountryIsSupported &&
			// Use case 1: Merchant has no payment extensions installed, and their store is in a WooPayments-supported geo.
			( ( ! wooPaymentsIsActive &&
				! wooPaymentsHasOtherProvidersEnabled ) ||
				// Use case 2: Merchant has the WooPayments extension installed, but they have not completed setup.
				( wooPaymentsIsActive && ! wooPaymentsIsOnboarded ) ||
				// Use case 3: Merchant has the WooPayments extension installed and configured with a test account.
				( wooPaymentsIsActive && wooPaymentsHasTestAccount ) ||
				// Use case 4: Merchant has multiple payment extensions installed but not set up, and the WooPayments extension is one of them.
				( wooPaymentsIsActive &&
					wooPaymentsHasOtherProvidersNeedSetup ) )
		) {
			// Record the "modal" being opened to keep consistency with the Payments Settings flow.
			recordPaymentsOnboardingEvent(
				'woopayments_onboarding_modal_opened',
				{
					from: 'lys_sidebar_task',
					source: wooPaymentsOnboardingSessionEntryLYS,
				}
			);

			return { type: 'SHOW_PAYMENTS' };
		}
		// Otherwise, we navigate to the task's action URL - this will generally be the Payments Settings page.
	}

	if ( event.task.actionUrl ) {
		navigateTo( { url: event.task.actionUrl } );
	} else {
		navigateTo( {
			url: getNewPath( { task: event.task.id }, '/', {} ),
		} );
	}
}

export const CompletedTaskItem = ( {
	task,
	classNames,
}: {
	task: TaskType;
	classNames?: string;
} ) => (
	<SidebarNavigationItem
		className={ clsx( task.id, 'is-complete', classNames ) }
		icon={ taskCompleteIcon }
		disabled={ true }
	>
		{ task.title }
	</SidebarNavigationItem>
);

export const IncompleteTaskItem = ( {
	task,
	classNames,
	onClick,
}: {
	task: TaskType;
	classNames?: string;
	onClick: () => void;
} ) => (
	<SidebarNavigationItem
		className={ clsx( task.id, classNames ) }
		icon={ taskIcons[ task.id ] }
		withChevron
		onClick={ onClick }
	>
		{ task.title }
	</SidebarNavigationItem>
);
