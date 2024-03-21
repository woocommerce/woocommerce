/**
 * External dependencies
 */
import { setup } from 'xstate5';
import React from 'react';
/**
 * Internal dependencies
 */
import { LoadingPage } from './pages/loading';
import { LaunchYourStoreSuccess } from './pages/launch-store-success';
import { SitePreviewPage } from './pages/site-preview';
import type { LaunchYourStoreComponentProps } from '..';

export type MainContentMachineContext = {
	placeholder?: string; // remove this when we have some types to put here
};

export type MainContentComponentProps = LaunchYourStoreComponentProps & {
	context: MainContentMachineContext;
};
export type MainContentMachineEvents =
	| { type: 'SHOW_LAUNCH_STORE_SUCCESS' }
	| { type: 'SHOW_LOADING' };

export const mainContentMachine = setup( {
	types: {} as {
		context: MainContentMachineContext;
		events: MainContentMachineEvents;
	},
} ).createMachine( {
	id: 'mainContent',
	initial: 'init',
	context: {},
	states: {
		init: {
			always: [
				{
					target: '#sitePreview',
				},
			],
		},
		sitePreview: {
			id: 'sitePreview',
			meta: {
				component: SitePreviewPage,
			},
		},
		launchStoreSuccess: {
			id: 'launchStoreSuccess',
			meta: {
				component: LaunchYourStoreSuccess,
			},
		},
		loading: {
			id: 'loading',
			meta: {
				component: LoadingPage,
			},
		},
	},
	on: {
		SHOW_LAUNCH_STORE_SUCCESS: {
			target: '#launchStoreSuccess',
		},
		SHOW_LOADING: {
			target: '#loading',
		},
	},
} );
export const MainContentContainer = ( {
	children,
}: {
	children: React.ReactNode;
} ) => {
	return (
		<div className="launch-your-store-layout__content">{ children }</div>
	);
};
