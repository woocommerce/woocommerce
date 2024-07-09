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
	enqueueActions,
} from 'xstate5';
import React from 'react';
import clsx from 'clsx';
import { getQuery, navigateTo } from '@woocommerce/navigation';
import {
	OPTIONS_STORE_NAME,
	PAYMENT_GATEWAYS_STORE_NAME,
	SETTINGS_STORE_NAME,
	TaskListType,
	TaskType,
} from '@woocommerce/data';
import { dispatch, resolveSelect } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
import apiFetch from '@wordpress/api-fetch';

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
	hasWooPayments?: boolean;
	testOrderCount: number;
	removeTestOrders?: boolean;
	launchStoreAttemptTimestamp?: number;
	launchStoreError?: {
		message: string;
	};
	siteIsShowingCachedContent?: boolean;
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

const getTestOrderCount = async () => {
	const result = ( await apiFetch( {
		path: '/wc-admin/launch-your-store/woopayments/test-orders/count',
		method: 'GET',
	} ) ) as { count: number };

	return result.count;
};

export const pageHasComingSoonMetaTag = async ( {
	url,
}: {
	url: string;
} ): Promise< boolean > => {
	try {
		const response = await fetch( url, {
			method: 'GET',
			credentials: 'omit',
			cache: 'no-store',
		} );
		if ( ! response.ok ) {
			throw new Error( `Failed to fetch ${ url }` );
		}
		const html = await response.text();
		const parser = new DOMParser();
		const doc = parser.parseFromString( html, 'text/html' );
		const metaTag = doc.querySelector(
			'meta[name="woo-coming-soon-page"]'
		);

		if ( metaTag ) {
			return true;
		}
		return false;
	} catch ( error ) {
		throw new Error( `Error fetching ${ url }: ${ error }` );
	}
};

export const getWooPaymentsStatus = async () => {
	// Quick (performant) check for the plugin.
	if (
		window?.wcSettings?.admin?.plugins?.activePlugins.includes(
			'woocommerce-payments'
		) === false
	) {
		return false;
	}

	// Check the gateway is installed
	const paymentGateways = await resolveSelect(
		PAYMENT_GATEWAYS_STORE_NAME
	).getPaymentGateways();
	const enabledPaymentGateways = paymentGateways.filter(
		( gateway ) => gateway.enabled
	);
	// Return true when WooPayments is the only enabled gateway.
	return (
		enabledPaymentGateways.length === 1 &&
		enabledPaymentGateways[ 0 ].id === 'woocommerce_payments'
	);
};

export const getSiteCachedStatus = async () => {
	const settings = await resolveSelect( SETTINGS_STORE_NAME ).getSettings(
		'wc_admin'
	);

	// if store URL exists, check both storeUrl and siteUrl otherwise only check siteUrl
	// we want to check both because there's a chance that caching is especially disabled for woocommerce pages, e.g WPEngine
	const requests = [] as Promise< boolean >[];
	if ( settings?.shopUrl ) {
		requests.push(
			pageHasComingSoonMetaTag( {
				url: settings.shopUrl,
			} )
		);
	}

	if ( settings?.siteUrl ) {
		requests.push(
			pageHasComingSoonMetaTag( {
				url: settings.siteUrl,
			} )
		);
	}

	const results = await Promise.all( requests );
	return results.some( ( result ) => result );
};

const deleteTestOrders = async ( {
	input,
}: {
	input: {
		removeTestOrders: boolean;
	};
} ) => {
	if ( ! input.removeTestOrders ) {
		return null;
	}
	return await apiFetch( {
		path: '/wc-admin/launch-your-store/woopayments/test-orders',
		method: 'DELETE',
	} );
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
		showLaunchStorePendingCache: sendTo(
			( { context } ) => context.mainContentMachineRef,
			{ type: 'SHOW_LAUNCH_STORE_PENDING_CACHE' }
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
		recordStoreLaunchCachedContentDetected: () => {
			recordEvent(
				'launch_your_store_hub_store_launch_cached_content_detected'
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
		hasWooPayments: ( { context } ) => {
			return !! context.hasWooPayments;
		},
		siteIsShowingCachedContent: ( { context } ) => {
			return !! context.siteIsShowingCachedContent;
		},
	},
	actors: {
		sidebarQueryParamListener,
		getTasklist: fromPromise( getLysTasklist ),
		getTestOrderCount: fromPromise( getTestOrderCount ),
		getSiteCachedStatus: fromPromise( getSiteCachedStatus ),
		updateLaunchStoreOptions: fromPromise( launchStoreAction ),
		deleteTestOrders: fromPromise( deleteTestOrders ),
		fetchCongratsData,
		getWooPaymentsStatus: fromPromise( getWooPaymentsStatus ),
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
							target: 'checkWooPayments',
						},
					},
				},
				checkWooPayments: {
					invoke: {
						src: 'getWooPaymentsStatus',
						onDone: {
							actions: assign( {
								hasWooPayments: ( { event } ) => event.output,
							} ),
							target: 'maybeCountTestOrders',
						},
						onError: {
							target: 'maybeCountTestOrders',
						},
					},
				},
				maybeCountTestOrders: {
					always: [
						{
							guard: 'hasWooPayments',
							target: 'countTestOrders',
						},
						{
							target: 'launchYourStoreHub',
						},
					],
				},
				countTestOrders: {
					invoke: {
						src: 'getTestOrderCount',
						onDone: {
							actions: assign( {
								testOrderCount: ( { event } ) => event.output,
							} ),
							target: 'launchYourStoreHub',
						},
						onError: {
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
					invoke: [
						{
							src: 'updateLaunchStoreOptions',
							onDone: {
								actions: [
									{
										type: 'recordStoreLaunchResults',
										params: { success: true },
									},
								],
								target: 'checkingForCachedContent',
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
						{
							src: 'deleteTestOrders',
							input: ( { event } ) => {
								return {
									removeTestOrders: (
										event as {
											removeTestOrders: boolean;
										}
									 ).removeTestOrders,
								};
							},
						},
					],
				},
				checkingForCachedContent: {
					invoke: [
						{
							src: 'getSiteCachedStatus',
							onDone: {
								target: '#storeLaunchSuccessful',
								actions: assign( {
									siteIsShowingCachedContent: ( { event } ) =>
										event.output,
								} ),
							},
							onError: {
								target: '#storeLaunchSuccessful',
							},
						},
					],
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
				enqueueActions( ( { check, enqueue } ) => {
					if ( check( 'siteIsShowingCachedContent' ) ) {
						enqueue( {
							type: 'showLaunchStorePendingCache',
						} );
						enqueue( {
							type: 'recordStoreLaunchCachedContentDetected',
						} );
						return;
					}
					enqueue( { type: 'showLaunchStoreSuccessPage' } );
				} ),
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
			className={ clsx( 'launch-your-store-layout__sidebar', className ) }
		>
			{ children }
		</div>
	);
};
