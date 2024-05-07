/**
 * External dependencies
 */
import {
	ActorRefFrom,
	sendTo,
	setup,
	fromCallback,
	fromPromise,
	assign,
	spawnChild,
} from 'xstate5';
import React from 'react';
import classnames from 'classnames';
import { getQuery, navigateTo } from '@woocommerce/navigation';
import { OPTIONS_STORE_NAME, TaskListType, TaskType } from '@woocommerce/data';
import { dispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { LaunchYourStoreHubSidebar } from './components/launch-store-hub';
import type { LaunchYourStoreComponentProps } from '..';
import type { mainContentMachine } from '../main-content/xstate';
import { updateQueryParams, createQueryParamsListener } from '../common';
import { taskClickedAction, getLysTasklist } from './tasklist';
import { fetchCongratsData } from '../main-content/pages/launch-store-success/services';
import { getTimeFrame } from '~/utils';

export type LYSAugmentedTaskListType = TaskListType & {
	recentlyActionedTasks: string[];
	fullLysTaskList: TaskType[];
};

export type SidebarMachineContext = {
	externalUrl: string | null;
	mainContentMachineRef: ActorRefFrom< typeof mainContentMachine >;
	tasklist?: LYSAugmentedTaskListType;
	testOrderCount: number;
	removeTestOrders?: boolean;
	launchStoreAttemptTimestamp?: number;
	launchStoreError?: {
		message: string;
	};
};
export type SidebarComponentProps = LaunchYourStoreComponentProps & {
	context: SidebarMachineContext;
};
export type SidebarMachineEvents =
	| { type: 'EXTERNAL_URL_UPDATE' }
	| { type: 'OPEN_EXTERNAL_URL'; url: string }
	| { type: 'TASK_CLICKED'; task: TaskType }
	| { type: 'OPEN_WC_ADMIN_URL'; url: string }
	| { type: 'OPEN_WC_ADMIN_URL_IN_CONTENT_AREA'; url: string }
	| { type: 'LAUNCH_STORE'; removeTestOrders: boolean }
	| { type: 'LAUNCH_STORE_SUCCESS' }
	| { type: 'POP_BROWSER_STACK' };

const sidebarQueryParamListener = fromCallback( ( { sendBack } ) => {
	return createQueryParamsListener( 'sidebar', sendBack );
} );

const launchStoreAction = async () => {
	const results = await dispatch( OPTIONS_STORE_NAME ).updateOptions( {
		woocommerce_coming_soon: 'no',
	} );
	if ( results.success ) {
		return results;
	}
	throw new Error( JSON.stringify( results ) );
};

const recordStoreLaunchAttempt = ( {
	context,
}: {
	context: SidebarMachineContext;
} ) => {
	const total_count = context.tasklist?.fullLysTaskList.length || 0;
	const incomplete_tasks =
		context.tasklist?.tasks
			.filter( ( task ) => ! task.isComplete )
			.map( ( task ) => task.id ) || [];

	const completed =
		context.tasklist?.fullLysTaskList
			.filter( ( task ) => task.isComplete )
			.map( ( task ) => task.id ) || [];

	const tasks_completed_in_lys = completed.filter( ( task ) =>
		context.tasklist?.recentlyActionedTasks.includes( task )
	); // recently actioned tasks can include incomplete tasks

	recordEvent( 'launch_your_store_hub_store_launch_attempted', {
		tasks_total_count: total_count, // all lys eligible tasks
		tasks_completed: completed, // all lys eligible tasks that are completed
		tasks_completed_count: completed.length,
		tasks_completed_in_lys,
		tasks_completed_in_lys_count: tasks_completed_in_lys.length,
		incomplete_tasks,
		incomplete_tasks_count: incomplete_tasks.length,
		delete_test_orders: context.removeTestOrders || false,
	} );
	return performance.now();
};

const recordStoreLaunchResults = ( timestamp: number, success: boolean ) => {
	recordEvent( 'launch_your_store_hub_store_launch_results', {
		success,
		duration: getTimeFrame( performance.now() - timestamp ),
	} );
};
export const sidebarMachine = setup( {
	types: {} as {
		context: SidebarMachineContext;
		events: SidebarMachineEvents;
		input: {
			mainContentMachineRef: ActorRefFrom< typeof mainContentMachine >;
		};
	},
	actions: {
		openExternalUrl: ( { event } ) => {
			if ( event.type === 'OPEN_EXTERNAL_URL' ) {
				navigateTo( { url: event.url } );
			}
		},
		showLaunchStoreSuccessPage: sendTo(
			( { context } ) => context.mainContentMachineRef,
			{ type: 'SHOW_LAUNCH_STORE_SUCCESS' }
		),
		showLoadingPage: sendTo(
			( { context } ) => context.mainContentMachineRef,
			{ type: 'SHOW_LOADING' }
		),
		updateQueryParams: (
			_,
			params: { sidebar?: string; content?: string }
		) => {
			updateQueryParams( params );
		},
		taskClicked: ( { event } ) => {
			if ( event.type === 'TASK_CLICKED' ) {
				taskClickedAction( event );
			}
		},
		openWcAdminUrl: ( { event } ) => {
			if ( event.type === 'OPEN_WC_ADMIN_URL' ) {
				navigateTo( { url: event.url } );
			}
		},
		windowHistoryBack: () => {
			window.history.back();
		},
		recordStoreLaunchAttempt: assign( {
			launchStoreAttemptTimestamp: recordStoreLaunchAttempt,
		} ),
		recordStoreLaunchResults: (
			{ context },
			{ success }: { success: boolean }
		) => {
			recordStoreLaunchResults(
				context.launchStoreAttemptTimestamp || 0,
				success
			);
		},
	},
	guards: {
		hasSidebarLocation: (
			_,
			{ sidebarLocation }: { sidebarLocation: string }
		) => {
			const { sidebar } = getQuery() as { sidebar?: string };
			return !! sidebar && sidebar === sidebarLocation;
		},
	},
	actors: {
		sidebarQueryParamListener,
		getTasklist: fromPromise( getLysTasklist ),
		updateLaunchStoreOptions: fromPromise( launchStoreAction ),
		fetchCongratsData,
	},
} ).createMachine( {
	id: 'sidebar',
	initial: 'navigate',
	context: ( { input } ) => ( {
		externalUrl: null,
		testOrderCount: 0,
		mainContentMachineRef: input.mainContentMachineRef,
	} ),
	invoke: {
		id: 'sidebarQueryParamListener',
		src: 'sidebarQueryParamListener',
	},
	states: {
		navigate: {
			always: [
				{
					guard: {
						type: 'hasSidebarLocation',
						params: { sidebarLocation: 'hub' },
					},
					target: 'launchYourStoreHub',
				},
				{
					guard: {
						type: 'hasSidebarLocation',
						params: { sidebarLocation: 'launch-success' },
					},
					target: 'storeLaunchSuccessful',
				},
				{
					target: 'launchYourStoreHub',
				},
			],
		},
		launchYourStoreHub: {
			initial: 'preLaunchYourStoreHub',
			states: {
				preLaunchYourStoreHub: {
					entry: [
						spawnChild( 'fetchCongratsData', {
							id: 'prefetch-congrats-data ',
						} ),
					],
					invoke: {
						src: 'getTasklist',
						onDone: {
							actions: assign( {
								tasklist: ( { event } ) => event.output,
							} ),
							target: 'launchYourStoreHub',
						},
					},
				},
				launchYourStoreHub: {
					id: 'launchYourStoreHub',
					tags: 'sidebar-visible',
					meta: {
						component: LaunchYourStoreHubSidebar,
					},
					on: {
						LAUNCH_STORE: {
							target: '#storeLaunching',
						},
					},
				},
			},
		},
		storeLaunching: {
			id: 'storeLaunching',
			initial: 'launching',
			states: {
				launching: {
					entry: [
						assign( { launchStoreError: undefined } ), // clear the errors if any from previously
						'recordStoreLaunchAttempt',
					],
					invoke: {
						src: 'updateLaunchStoreOptions',
						onDone: {
							target: '#storeLaunchSuccessful',
							actions: [
								{
									type: 'recordStoreLaunchResults',
									params: { success: true },
								},
							],
						},
						onError: {
							actions: [
								assign( {
									launchStoreError: ( { event } ) => {
										return {
											message: JSON.stringify(
												event.error
											), // for some reason event.error is an empty object, worth investigating if we decide to use the error message somewhere
										};
									},
								} ),
								{
									type: 'recordStoreLaunchResults',
									params: {
										success: false,
									},
								},
							],
							target: '#launchYourStoreHub',
						},
					},
				},
			},
		},
		storeLaunchSuccessful: {
			id: 'storeLaunchSuccessful',
			tags: 'fullscreen',
			entry: [
				{
					type: 'updateQueryParams',
					params: {
						sidebar: 'launch-success',
						content: 'launch-store-success',
					},
				},
				{ type: 'showLaunchStoreSuccessPage' },
			],
		},
		openExternalUrl: {
			id: 'openExternalUrl',
			tags: 'sidebar-visible', // unintuitive but it prevents a layout shift just before leaving
			entry: [ 'openExternalUrl' ],
		},
	},
	on: {
		EXTERNAL_URL_UPDATE: {
			target: '.navigate',
		},
		OPEN_EXTERNAL_URL: {
			target: '#openExternalUrl',
		},
		TASK_CLICKED: {
			actions: 'taskClicked',
		},
		OPEN_WC_ADMIN_URL: {
			actions: 'openWcAdminUrl',
		},
		POP_BROWSER_STACK: {
			actions: 'windowHistoryBack',
		},
		OPEN_WC_ADMIN_URL_IN_CONTENT_AREA: {},
	},
} );
export const SidebarContainer = ( {
	children,
	className,
}: {
	children: React.ReactNode;
	className?: string;
} ) => {
	return (
		<div
			className={ classnames(
				'launch-your-store-layout__sidebar',
				className
			) }
		>
			{ children }
		</div>
	);
};
