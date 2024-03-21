/**
 * External dependencies
 */
import { fromCallback, setup } from 'xstate5';
import React from 'react';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { LoadingPage } from './pages/loading';
import { LaunchYourStoreSuccess } from './pages/launch-store-success';
import { SitePreviewPage } from './pages/site-preview';
import type { LaunchYourStoreComponentProps } from '..';
import { createQueryParamsListener, updateQueryParams } from '../common';

export type MainContentMachineContext = {
	placeholder?: string; // remove this when we have some types to put here
};

export type MainContentComponentProps = LaunchYourStoreComponentProps & {
	context: MainContentMachineContext;
};
export type MainContentMachineEvents =
	| { type: 'SHOW_LAUNCH_STORE_SUCCESS' }
	| { type: 'SHOW_LOADING' };

const contentQueryParamListener = fromCallback( ( { sendBack } ) => {
	return createQueryParamsListener( 'content' )( sendBack );
} );
export const mainContentMachine = setup( {
	types: {} as {
		context: MainContentMachineContext;
		events: MainContentMachineEvents;
	},
	actions: {
		updateQueryParams: (
			_,
			{ sidebar, content }: { sidebar?: string; content?: string }
		) => {
			updateQueryParams( { sidebar, content } );
		},
	},
	guards: {
		hasContentLocation: (
			_,
			{ contentLocation }: { contentLocation: string }
		) => {
			const { content } = getQuery() as { content?: string };
			return !! content && content === contentLocation;
		},
	},
	actors: {
		contentQueryParamListener,
	},
} ).createMachine( {
	id: 'mainContent',
	initial: 'navigate',
	context: {},
	states: {
		navigate: {
			always: [
				{
					guard: {
						type: 'hasContentLocation',
						params: { contentLocation: 'site-preview' },
					},
				},
				{
					guard: {
						type: 'hasContentLocation',
						params: { contentLocation: 'launch-store-success' },
					},
					target: 'launchStoreSuccess',
				},
				{
					target: '#sitePreview',
				},
			],
		},
		sitePreview: {
			id: 'sitePreview',
			entry: [
				{
					type: 'updateQueryParams',
					params: { content: 'site-preview' },
				},
			],
			meta: {
				component: SitePreviewPage,
			},
		},
		launchStoreSuccess: {
			id: 'launchStoreSuccess',
			entry: [
				{
					type: 'updateQueryParams',
					params: { content: 'launch-store-success' },
				},
			],
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
		EXTERNAL_URL_UPDATE: {
			target: '.navigate',
		},
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
