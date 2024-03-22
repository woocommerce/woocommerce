/**
 * External dependencies
 */
import { ActorRefFrom, sendTo, setup } from 'xstate5';
import React from 'react';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { LaunchYourStoreHubSidebar } from './components/launch-store-hub';
import type { LaunchYourStoreComponentProps } from '..';
import type { mainContentMachine } from '../main-content/xstate';

export type SidebarMachineContext = {
	externalUrl: string | null;
	mainContentMachineRef: ActorRefFrom< typeof mainContentMachine >;
};
export type SidebarComponentProps = LaunchYourStoreComponentProps & {
	context: SidebarMachineContext;
};
export type SidebarMachineEvents =
	| { type: 'OPEN_EXTERNAL_URL'; url: string }
	| { type: 'OPEN_WC_ADMIN_URL'; url: string }
	| { type: 'OPEN_WC_ADMIN_URL_IN_CONTENT_AREA'; url: string }
	| { type: 'LAUNCH_STORE' }
	| { type: 'LAUNCH_STORE_SUCCESS' };
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
				window.open( event.url, '_self' );
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
	},
} ).createMachine( {
	id: 'sidebar',
	initial: 'init',
	context: ( { input } ) => ( {
		externalUrl: null,
		mainContentMachineRef: input.mainContentMachineRef,
	} ),
	states: {
		init: {
			always: {
				target: 'launchYourStoreHub',
			},
		},
		launchYourStoreHub: {
			initial: 'preLaunchYourStoreHub',
			states: {
				preLaunchYourStoreHub: {
					always: 'launchYourStoreHub',
					// do async stuff here such as retrieving task statuses
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
							actions: { type: 'showLaunchStoreSuccessPage' },
						},
					},
				},
			},
		},
		storeLaunchSuccessful: {
			id: 'storeLaunchSuccessful',
			tags: 'fullscreen',
		},
		openExternalUrl: {
			id: 'openExternalUrl',
			tags: 'sidebar-visible', // unintuitive but it prevents a layout shift just before leaving
			entry: [ 'openExternalUrl' ],
		},
	},
	on: {
		OPEN_EXTERNAL_URL: {
			target: '#openExternalUrl',
		},
		OPEN_WC_ADMIN_URL: {},
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
