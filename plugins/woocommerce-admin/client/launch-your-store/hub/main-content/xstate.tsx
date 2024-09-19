/**
 * External dependencies
 */
import { assertEvent, assign, fromCallback, fromPromise, setup } from 'xstate5';
import React from 'react';
import { getQuery, navigateTo } from '@woocommerce/navigation';
import { ONBOARDING_STORE_NAME, type TaskListType } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { dispatch } from '@wordpress/data';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { LoadingPage } from './pages/loading';
import { SitePreviewPage } from './pages/site-preview';
import type {
	LaunchYourStoreComponentProps,
	LaunchYourStoreQueryParams,
} from '..';
import {
	updateQueryParams,
	createQueryParamsListener,
} from '~/utils/xstate/url-handling';
import {
	services as congratsServices,
	events as congratsEvents,
	actions as congratsActions,
	LaunchYourStoreSuccess,
} from './pages/launch-store-success';
import { getSiteCachedStatus } from '../sidebar/xstate';

export type MainContentMachineContext = {
	congratsScreen: {
		hasLoadedCongratsData: boolean;
		hasCompleteSurvey: boolean;
		allTasklists: TaskListType[];
		activePlugins: string[];
	};
	siteIsShowingCachedContent: boolean | undefined;
};

export type MainContentComponentProps = LaunchYourStoreComponentProps & {
	context: MainContentMachineContext;
};
export type MainContentMachineEvents =
	| { type: 'SHOW_LAUNCH_STORE_SUCCESS' }
	| { type: 'SHOW_LAUNCH_STORE_PENDING_CACHE' }
	| { type: 'EXTERNAL_URL_UPDATE' }
	| { type: 'SHOW_LOADING' }
	| congratsEvents;

const contentQueryParamListener = fromCallback( ( { sendBack } ) => {
	return createQueryParamsListener( 'content', sendBack );
} );

export const mainContentMachine = setup( {
	types: {} as {
		context: MainContentMachineContext;
		events: MainContentMachineEvents;
	},
	actions: {
		updateQueryParams: ( _, params: LaunchYourStoreQueryParams ) => {
			updateQueryParams< LaunchYourStoreQueryParams >( params );
		},
		assignSiteCachedStatus: assign( {
			siteIsShowingCachedContent: true,
		} ),
		recordSurveyResults: ( { event } ) => {
			assertEvent( event, 'COMPLETE_SURVEY' );
			recordEvent( 'launch_your_store_congrats_survey_complete', {
				action: event.payload.action,
				score: event.payload.score,
				comments: event.payload.comments,
			} );
		},
		recordBackToHomeClick: () => {
			recordEvent( 'launch_your_store_congrats_back_to_home_click' );
		},
		recordPreviewStoreClick: () => {
			recordEvent( 'launch_your_store_congrats_preview_store_click' );
		},
		navigateToPreview: () => {
			const homeUrl: string = getSetting( 'homeUrl', '' );
			window.open( homeUrl, '_blank' );
		},
		navigateToHome: () => {
			const { invalidateResolutionForStoreSelector } = dispatch(
				ONBOARDING_STORE_NAME
			);
			invalidateResolutionForStoreSelector( 'getTaskLists' );
			navigateTo( { url: '/' } );
		},
	},
	guards: {
		hasContentLocation: (
			_,
			{ content: contentLocation }: LaunchYourStoreQueryParams
		) => {
			const { content } = getQuery() as LaunchYourStoreQueryParams;
			return !! content && content === contentLocation;
		},
	},
	actors: {
		contentQueryParamListener,
		fetchCongratsData: congratsServices.fetchCongratsData,
		getSiteCachedStatus: fromPromise( getSiteCachedStatus ),
	},
} ).createMachine( {
	id: 'mainContent',
	initial: 'navigate',
	context: {
		congratsScreen: {
			hasLoadedCongratsData: false,
			hasCompleteSurvey: false,
			allTasklists: [],
			activePlugins: [],
		},
		siteIsShowingCachedContent: undefined,
	},
	invoke: {
		id: 'contentQueryParamListener',
		src: 'contentQueryParamListener',
	},
	states: {
		navigate: {
			always: [
				{
					guard: {
						type: 'hasContentLocation',
						params: { content: 'site-preview' },
					},
				},
				{
					guard: {
						type: 'hasContentLocation',
						params: { content: 'launch-store-success' },
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
			meta: {
				component: SitePreviewPage,
			},
		},
		launchStoreSuccess: {
			id: 'launchStoreSuccess',
			initial: 'loading',
			states: {
				loading: {
					invoke: [
						{
							src: 'fetchCongratsData',
							onDone: {
								actions: assign(
									congratsActions.assignCongratsData
								),
							},
						},
						{
							src: 'getSiteCachedStatus',
							onDone: {
								actions: assign( {
									siteIsShowingCachedContent: ( { event } ) =>
										event.output,
								} ),
							},
							onError: {
								actions: assign( {
									siteIsShowingCachedContent: false,
								} ),
							},
						},
					],
					always: {
						guard: ( { context } ) => {
							return (
								context.congratsScreen.hasLoadedCongratsData &&
								context.siteIsShowingCachedContent !== undefined
							);
						},
						target: 'congrats',
					},
					meta: {
						component: LoadingPage,
					},
				},
				congrats: {
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
			},

			on: {
				COMPLETE_SURVEY: {
					actions: [
						assign( congratsActions.assignCompleteSurvey ),
						'recordSurveyResults',
					],
				},
				PREVIEW_STORE: {
					actions: [ 'recordPreviewStoreClick', 'navigateToPreview' ],
				},
				BACK_TO_HOME: {
					actions: [ 'recordBackToHomeClick', 'navigateToHome' ],
				},
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
		SHOW_LAUNCH_STORE_PENDING_CACHE: {
			actions: [ 'assignSiteCachedStatus' ],
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
