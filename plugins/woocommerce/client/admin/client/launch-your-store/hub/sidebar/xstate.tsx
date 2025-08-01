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
import clsx from 'clsx';
import { getQuery, navigateTo } from '@woocommerce/navigation';
import {
	optionsStore,
	settingsStore,
	TaskListType,
	TaskType,
	PaymentGateway,
	paymentGatewaysStore,
} from '@woocommerce/data';
import { dispatch, resolveSelect } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { LaunchYourStoreHubSidebar } from './components/launch-store-hub';
import { PaymentsSidebar } from './components/payments-sidebar';
import { LaunchStoreHubMobileHeader } from './components/mobile-header';
import { PaymentsMobileHeader } from './components/payments-mobile-header';
import type {
	LaunchYourStoreComponentProps,
	LaunchYourStoreQueryParams,
} from '..';
import type { mainContentMachine } from '../main-content/xstate';
import {
	updateQueryParams,
	createQueryParamsListener,
} from '~/utils/xstate/url-handling';
import { taskClickedAction, getLysTasklist } from './tasklist';
import { fetchCongratsData } from '../main-content/pages/launch-store-success/services';
import { getTimeFrame } from '~/utils';
import { isWooPayments } from '~/settings-payments/utils';

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
	onMobileClose?: () => void;
	onToggle?: () => void;
	isMobileSidebarOpen?: boolean;
};
export type SidebarMachineEvents =
	| { type: 'EXTERNAL_URL_UPDATE' }
	| { type: 'TASK_CLICKED'; task: TaskType }
	| { type: 'OPEN_WC_ADMIN_URL'; url: string }
	| { type: 'OPEN_WC_ADMIN_URL_IN_CONTENT_AREA'; url: string }
	| { type: 'LAUNCH_STORE'; removeTestOrders: boolean }
	| { type: 'LAUNCH_STORE_SUCCESS' }
	| { type: 'SHOW_PAYMENTS' }
	| { type: 'POP_BROWSER_STACK' }
	| { type: 'RETURN_FROM_PAYMENTS' }
	| { type: 'REFRESH_TASKLIST' };

const sidebarQueryParamListener = fromCallback( ( { sendBack } ) => {
	return createQueryParamsListener( 'sidebar', sendBack );
} );

const launchStoreAction = async () => {
	const results = await dispatch( optionsStore ).updateOptions( {
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
	const paymentGateways: PaymentGateway[] = await resolveSelect(
		paymentGatewaysStore
	).getPaymentGateways();
	const enabledPaymentGateways = paymentGateways.filter(
		( gateway ) => gateway.enabled
	);
	// Return true when WooPayments is the only enabled gateway.
	return (
		enabledPaymentGateways.length === 1 &&
		isWooPayments( enabledPaymentGateways[ 0 ].id )
	);
};

export const getSiteCachedStatus = async () => {
	const settings = await resolveSelect( settingsStore ).getSettings(
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
		showSitePreview: sendTo(
			( { context } ) => context.mainContentMachineRef,
			{ type: 'EXTERNAL_URL_UPDATE' }
		),
		updateQueryParams: ( _, params: LaunchYourStoreQueryParams ) => {
			updateQueryParams< LaunchYourStoreQueryParams >( params );
		},
		taskClicked: ( { event, self } ) => {
			if ( event.type === 'TASK_CLICKED' ) {
				const result = taskClickedAction( event );

				// If taskClickedAction returns an event object, handle it
				if (
					result &&
					typeof result === 'object' &&
					'type' in result
				) {
					// If SHOW_PAYMENTS is returned, transition to the payments sub-steps
					if ( result.type === 'SHOW_PAYMENTS' ) {
						self.send( { type: 'SHOW_PAYMENTS' } );
					}
				}
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
		showPaymentsContent: sendTo(
			( { context } ) => context.mainContentMachineRef,
			{ type: 'SHOW_PAYMENTS' }
		),
		triggerTasklistRefresh: ( { self } ) => {
			// Send refresh event to self to trigger background data refresh
			self.send( { type: 'REFRESH_TASKLIST' } );
		},
		navigateToWcAdmin: () => {
			// Navigate directly to WC Admin home
			const adminUrl = '/wp-admin/admin.php?page=wc-admin';
			window.location.href = adminUrl;
		},
	},
	guards: {
		hasSidebarLocation: (
			_,
			{ sidebar: sidebarLocation }: LaunchYourStoreQueryParams
		) => {
			const { sidebar } = getQuery() as LaunchYourStoreQueryParams;
			return !! sidebar && sidebar === sidebarLocation;
		},
		hasPaymentsContent: () => {
			const { content } = getQuery() as LaunchYourStoreQueryParams;
			return content === 'payments';
		},
		hasWooPaymentsOnboardingPath: () => {
			const query = getQuery() as LaunchYourStoreQueryParams & {
				path?: string;
			};
			return (
				!! query.path &&
				query.path.includes( '/woopayments/onboarding' )
			);
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
					guard: { type: 'hasWooPaymentsOnboardingPath' },
					target: 'payments',
				},
				{
					guard: { type: 'hasPaymentsContent' },
					target: 'payments',
				},
				{
					guard: {
						type: 'hasSidebarLocation',
						params: { sidebar: 'hub' },
					},
					target: 'launchYourStoreHub',
				},
				{
					guard: {
						type: 'hasSidebarLocation',
						params: { sidebar: 'launch-success' },
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
						mobileHeader: LaunchStoreHubMobileHeader,
					},
					on: {
						LAUNCH_STORE: {
							target: '#storeLaunching',
						},
						POP_BROWSER_STACK: {
							actions: [ 'navigateToWcAdmin' ],
						},
						REFRESH_TASKLIST: {
							// Stay in current state but trigger background refresh
							target: 'backgroundRefresh',
						},
					},
				},
				backgroundRefresh: {
					id: 'backgroundRefresh',
					tags: 'sidebar-visible',
					meta: {
						component: LaunchYourStoreHubSidebar,
						mobileHeader: LaunchStoreHubMobileHeader,
					},
					invoke: [
						{
							src: 'getTasklist',
							onDone: {
								actions: assign( {
									tasklist: ( { event } ) => event.output,
								} ),
								target: 'backgroundCheckWooPayments',
							},
							onError: {
								// If refresh fails, just stay in the hub with old data
								target: 'launchYourStoreHub',
							},
						},
					],
					on: {
						LAUNCH_STORE: {
							target: '#storeLaunching',
						},
						POP_BROWSER_STACK: {
							actions: [ 'navigateToWcAdmin' ],
						},
					},
				},
				backgroundCheckWooPayments: {
					tags: 'sidebar-visible',
					meta: {
						component: LaunchYourStoreHubSidebar,
						mobileHeader: LaunchStoreHubMobileHeader,
					},
					invoke: {
						src: 'getWooPaymentsStatus',
						onDone: {
							actions: assign( {
								hasWooPayments: ( { event } ) => event.output,
							} ),
							target: 'backgroundMaybeCountTestOrders',
						},
						onError: {
							target: 'backgroundMaybeCountTestOrders',
						},
					},
					on: {
						LAUNCH_STORE: {
							target: '#storeLaunching',
						},
						POP_BROWSER_STACK: {
							actions: [ 'navigateToWcAdmin' ],
						},
					},
				},
				backgroundMaybeCountTestOrders: {
					tags: 'sidebar-visible',
					meta: {
						component: LaunchYourStoreHubSidebar,
						mobileHeader: LaunchStoreHubMobileHeader,
					},
					always: [
						{
							guard: 'hasWooPayments',
							target: 'backgroundCountTestOrders',
						},
						{
							target: 'launchYourStoreHub',
						},
					],
				},
				backgroundCountTestOrders: {
					tags: 'sidebar-visible',
					meta: {
						component: LaunchYourStoreHubSidebar,
						mobileHeader: LaunchStoreHubMobileHeader,
					},
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
					on: {
						LAUNCH_STORE: {
							target: '#storeLaunching',
						},
						POP_BROWSER_STACK: {
							actions: [ 'navigateToWcAdmin' ],
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
		payments: {
			id: 'payments',
			meta: {
				component: PaymentsSidebar,
				mobileHeader: PaymentsMobileHeader,
			},
			entry: [
				'showPaymentsContent',
				{
					type: 'updateQueryParams',
					params: { sidebar: 'hub', content: 'payments' },
				},
			],
			on: {
				POP_BROWSER_STACK: {
					actions: [
						{
							type: 'navigateToWcAdmin',
						},
					],
				},
				RETURN_FROM_PAYMENTS: {
					target: '#launchYourStoreHub',
					actions: [
						{
							type: 'updateQueryParams',
							params: { sidebar: 'hub', content: 'site-preview' },
						},
						// Force the main content to reset completely
						sendTo(
							( { context } ) => context.mainContentMachineRef,
							{ type: 'RETURN_FROM_PAYMENTS' }
						),
						// Trigger background refresh of tasklist data
						'triggerTasklistRefresh',
					],
				},
			},
		},
	},
	on: {
		EXTERNAL_URL_UPDATE: {
			target: '.navigate',
		},
		TASK_CLICKED: {
			actions: 'taskClicked',
		},
		OPEN_WC_ADMIN_URL: {
			actions: 'openWcAdminUrl',
		},
		OPEN_WC_ADMIN_URL_IN_CONTENT_AREA: {},
		SHOW_PAYMENTS: {
			target: '.payments',
		},
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
