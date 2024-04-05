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
} from 'xstate5';
import React from 'react';
import classnames from 'classnames';
import { getQuery, navigateTo } from '@woocommerce/navigation';
import { TaskListType, TaskType } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { LaunchYourStoreHubSidebar } from './components/launch-store-hub';
import type { LaunchYourStoreComponentProps } from '..';
import type { mainContentMachine } from '../main-content/xstate';
import { updateQueryParams, createQueryParamsListener } from '../common';
import { taskClickedAction, getLysTasklist } from './tasklist';

export type SidebarMachineContext = {
	externalUrl: string | null;
	mainContentMachineRef: ActorRefFrom< typeof mainContentMachine >;
	tasklist?: TaskListType;
	testOrderCount: number;
	removeTestOrders?: boolean;
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
					tags: 'fullscreen',
					entry: { type: 'showLoadingPage' },
					on: {
						LAUNCH_STORE_SUCCESS: {
							target: '#storeLaunchSuccessful',
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
