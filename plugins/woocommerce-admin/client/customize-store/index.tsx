// @ts-expect-error -- No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { store as coreStore } from '@wordpress/core-data';
/**
 * External dependencies
 */
import { Sender, createMachine } from 'xstate';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { useMachine, useSelector } from '@xstate/react';
import {
	getNewPath,
	getQuery,
	updateQueryString,
} from '@woocommerce/navigation';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { dispatch, resolveSelect } from '@wordpress/data';
import { Spinner } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/settings';
import { PluginArea } from '@wordpress/plugins';
/**
 * Internal dependencies
 */
import { useFullScreen } from '~/utils';
import {
	Intro,
	events as introEvents,
	services as introServices,
	actions as introActions,
} from './intro';
import { DesignWithAi, events as designWithAiEvents } from './design-with-ai';
import { DesignWithoutAi } from './design-without-ai';

import { AssemblerHub, events as assemblerHubEvents } from './assembler-hub';
import {
	events as transitionalEvents,
	services as transitionalServices,
	actions as transitionalActions,
} from './transitional';
import { findComponentMeta } from '~/utils/xstate/find-component';
import {
	CustomizeStoreComponentMeta,
	CustomizeStoreComponent,
	customizeStoreStateMachineContext,
	FlowType,
} from './types';
import { ThemeCard } from './intro/types';
import './style.scss';
import { navigateOrParent, attachParentListeners } from './utils';
import useBodyClass from './hooks/use-body-class';
import { isWooExpress } from '~/utils/is-woo-express';

export type customizeStoreStateMachineEvents =
	| introEvents
	| designWithAiEvents
	| assemblerHubEvents
	| transitionalEvents
	| { type: 'AI_WIZARD_CLOSED_BEFORE_COMPLETION'; payload: { step: string } }
	| { type: 'EXTERNAL_URL_UPDATE' }
	| { type: 'NO_AI_FLOW_ERROR'; payload: { hasError: boolean } };

const updateQueryStep = (
	_context: unknown,
	_evt: unknown,
	{ action }: { action: unknown }
) => {
	const { path } = getQuery() as { path: string };
	const step = ( action as { step: string } ).step;
	const pathFragments = path.split( '/' ); // [0] '', [1] 'customize-store', [2] step slug [3] design-with-ai, assembler-hub path fragments
	if ( pathFragments[ 1 ] === 'customize-store' ) {
		if ( pathFragments[ 2 ] !== step ) {
			// this state machine is only concerned with [2], so we ignore changes to [3]
			// [1] is handled by router at root of wc-admin
			updateQueryString( {}, `/customize-store/${ step }` );
		}
	}
};

const redirectToWooHome = () => {
	const url = getNewPath( {}, '/', {} );
	navigateOrParent( window, url );
};

const redirectToThemes = ( _context: customizeStoreStateMachineContext ) => {
	window.location.href =
		_context?.intro?.themeData?._links?.browse_all?.href ??
		getAdminLink( 'themes.php' );
};

const markTaskComplete = async () => {
	const currentTemplate = await resolveSelect(
		coreStore
		// @ts-expect-error No types for this exist yet.
	).__experimentalGetTemplateForLink( '/' );
	return dispatch( OPTIONS_STORE_NAME ).updateOptions( {
		woocommerce_admin_customize_store_completed: 'yes',
		// we use this on the intro page to determine if this same theme was used in the last customization
		woocommerce_admin_customize_store_completed_theme_id:
			currentTemplate.id ?? undefined,
	} );
};

const browserPopstateHandler =
	() => ( sendBack: Sender< { type: 'EXTERNAL_URL_UPDATE' } > ) => {
		const popstateHandler = () => {
			sendBack( { type: 'EXTERNAL_URL_UPDATE' } );
		};
		window.addEventListener( 'popstate', popstateHandler );
		return () => {
			window.removeEventListener( 'popstate', popstateHandler );
		};
	};

const CYSSpinner = () => (
	<div className="woocommerce-customize-store__loading">
		<Spinner />
	</div>
);

export const machineActions = {
	updateQueryStep,
	redirectToWooHome,
	redirectToThemes,
};

export const customizeStoreStateMachineActions = {
	...introActions,
	...transitionalActions,
	...machineActions,
};

export const customizeStoreStateMachineServices = {
	...introServices,
	...transitionalServices,
	browserPopstateHandler,
	markTaskComplete,
};
export const customizeStoreStateMachineDefinition = createMachine( {
	id: 'customizeStore',
	initial: 'navigate',
	predictableActionArguments: true,
	preserveActionOrder: true,
	schema: {
		context: {} as customizeStoreStateMachineContext,
		events: {} as customizeStoreStateMachineEvents,
		services: {} as {
			fetchThemeCards: { data: ThemeCard[] };
		},
	},
	context: {
		intro: {
			hasErrors: false,
			themeData: {
				themes: [] as ThemeCard[],
				_links: {
					browse_all: {
						href: getAdminLink( 'themes.php' ),
					},
				},
			},
			activeTheme: '',
			activeThemeHasMods: undefined,
			customizeStoreTaskCompleted: false,
			currentThemeIsAiGenerated: false,
		},
		transitionalScreen: {
			hasCompleteSurvey: false,
		},
		flowType: FlowType.noAI,
	} as customizeStoreStateMachineContext,
	invoke: {
		src: 'browserPopstateHandler',
	},
	on: {
		EXTERNAL_URL_UPDATE: {
			target: 'navigate',
		},
		AI_WIZARD_CLOSED_BEFORE_COMPLETION: {
			target: 'intro',
			actions: [ { type: 'updateQueryStep', step: 'intro' } ],
		},
		NO_AI_FLOW_ERROR: {
			target: 'intro',
			actions: [
				{ type: 'assignNoAIFlowError' },
				{ type: 'updateQueryStep', step: 'intro' },
			],
		},
	},
	states: {
		navigate: {
			always: [
				{
					target: 'intro',
					cond: {
						type: 'hasStepInUrl',
						step: 'intro',
					},
				},
				{
					target: 'designWithAi',
					cond: {
						type: 'hasStepInUrl',
						step: 'design-with-ai',
					},
				},
				{
					target: 'designWithoutAi',
					cond: {
						type: 'hasStepInUrl',
						step: 'design',
					},
				},
				{
					target: 'assemblerHub',
					cond: {
						type: 'hasStepInUrl',
						step: 'assembler-hub',
					},
				},
				{
					target: 'transitionalScreen',
					cond: {
						type: 'hasStepInUrl',
						step: 'transitional',
					},
				},
				{
					target: 'intro',
				},
			],
		},
		intro: {
			id: 'intro',
			initial: 'flowType',
			states: {
				flowType: {
					always: [
						{
							target: 'fetchIntroData',
							cond: 'isNotWooExpress',
							actions: 'assignNoAI',
						},
						{
							target: 'checkAiStatus',
							cond: 'isWooExpress',
						},
					],
				},
				checkAiStatus: {
					initial: 'pending',
					states: {
						pending: {
							invoke: {
								src: 'fetchAiStatus',
								onDone: {
									actions: 'assignAiStatus',
									target: 'success',
								},
								onError: {
									actions: 'assignAiOffline',
									target: 'success',
								},
							},
						},
						success: { type: 'final' },
					},
					onDone: 'fetchIntroData',
				},
				fetchIntroData: {
					initial: 'pending',
					states: {
						pending: {
							invoke: {
								src: 'fetchIntroData',
								onError: {
									actions: 'assignFetchIntroDataError',
									target: 'success',
								},
								onDone: {
									target: 'success',
									actions: [
										'assignThemeData',
										'assignActiveThemeHasMods',
										'assignCustomizeStoreCompleted',
										'assignCurrentThemeIsAiGenerated',
									],
								},
							},
						},
						success: { type: 'final' },
					},
					onDone: 'intro',
				},
				intro: {
					meta: {
						component: Intro,
					},
				},
			},
			on: {
				CLICKED_ON_BREADCRUMB: {
					actions: 'redirectToWooHome',
				},
				DESIGN_WITH_AI: {
					actions: [ 'recordTracksDesignWithAIClicked' ],
					target: 'designWithAi',
				},
				DESIGN_WITHOUT_AI: {
					actions: [ 'recordTracksDesignWithAIClicked' ],
					target: 'designWithoutAi',
				},
				SELECTED_NEW_THEME: {
					actions: [ 'recordTracksThemeSelected' ],
					target: 'appearanceTask',
				},
				SELECTED_ACTIVE_THEME: {
					actions: [ 'recordTracksThemeSelected' ],
					target: 'appearanceTask',
				},
				SELECTED_BROWSE_ALL_THEMES: {
					actions: [
						'recordTracksBrowseAllThemesClicked',
						'redirectToThemes',
					],
				},
			},
		},
		designWithoutAi: {
			initial: 'preDesignWithoutAi',
			states: {
				preDesignWithoutAi: {
					always: {
						cond: { type: 'log', state: 'designWithoutAi' },
						target: 'designWithoutAi',
					},
				},
				designWithoutAi: {
					entry: [ { type: 'updateQueryStep', step: 'design' } ],
					meta: {
						component: DesignWithoutAi,
					},
				},
			},
		},
		designWithAi: {
			initial: 'preDesignWithAi',
			states: {
				preDesignWithAi: {
					always: {
						target: 'designWithAi',
					},
				},
				designWithAi: {
					meta: {
						component: DesignWithAi,
					},
					entry: [
						{ type: 'updateQueryStep', step: 'design-with-ai' },
					],
				},
			},
			on: {
				THEME_SUGGESTED: {
					target: 'assemblerHub',
				},
			},
		},
		assemblerHub: {
			initial: 'shouldFetchActiveThemeHasMods',
			states: {
				shouldFetchActiveThemeHasMods: {
					always: [
						{
							cond: { type: 'log', state: 'shouldFetch' },
							target: 'fetchActiveThemeHasMods',
						},
					],
				},
				fetchActiveThemeHasMods: {
					invoke: {
						src: 'fetchActiveThemeHasMods',
						onDone: {
							target: 'checkActiveThemeHasMods',
							actions: [ 'assignActiveThemeHasMods' ],
						},
					},
				},
				checkActiveThemeHasMods: {
					always: [
						{
							cond: 'activeThemeIsNotModified',
							actions: [
								{ type: 'updateQueryStep', step: 'intro' },
							],
							target: '#customizeStore.intro',
						},
						{
							cond: 'activeThemeHasMods',
							target: 'preCheckAiStatus',
						},
					],
				},
				preCheckAiStatus: {
					always: [
						{
							cond: 'isWooExpress',
							target: 'checkAiStatus',
						},
						{ cond: 'isNotWooExpress', target: 'assemblerHub' },
					],
				},
				checkAiStatus: {
					invoke: {
						src: 'fetchAiStatus',
						onDone: {
							actions: 'assignAiStatus',
							target: 'assemblerHub',
						},
						onError: {
							actions: 'assignAiOffline',
							target: 'assemblerHub',
						},
					},
				},
				assemblerHub: {
					entry: [
						{ type: 'updateQueryStep', step: 'assembler-hub' },
					],
					meta: {
						component: AssemblerHub,
					},
				},
				postAssemblerHub: {
					invoke: [
						{
							src: 'markTaskComplete',
							// eslint-disable-next-line xstate/no-ondone-outside-compound-state
							onDone: {
								target: '#customizeStore.transitionalScreen',
							},
						},
						{
							// Pre-fetch survey completed option so we can show the screen immediately.
							src: 'fetchSurveyCompletedOption',
						},
					],
				},
			},
			on: {
				FINISH_CUSTOMIZATION: {
					target: '.postAssemblerHub',
				},
				GO_BACK_TO_DESIGN_WITH_AI: {
					target: 'designWithAi',
				},
				GO_BACK_TO_DESIGN_WITHOUT_AI: {
					target: 'intro',
				},
			},
		},
		transitionalScreen: {
			initial: 'preTransitional',
			states: {
				preTransitional: {
					meta: {
						component: CYSSpinner,
					},
					invoke: {
						src: 'fetchSurveyCompletedOption',
						onError: {
							target: 'transitional', // leave it as initialised default on error
						},
						onDone: {
							target: 'transitional',
							actions: [ 'assignHasCompleteSurvey' ],
						},
					},
				},
				transitional: {
					entry: [
						{ type: 'updateQueryStep', step: 'transitional' },
					],
					meta: {
						component: AssemblerHub,
					},
					on: {
						GO_BACK_TO_HOME: {
							actions: 'redirectToWooHome',
						},
						COMPLETE_SURVEY: {
							actions: 'completeSurvey',
						},
					},
				},
			},
		},
		appearanceTask: {},
	},
} );

export const CustomizeStoreController = ( {
	actionOverrides,
	servicesOverrides,
}: {
	actionOverrides: Partial< typeof customizeStoreStateMachineActions >;
	servicesOverrides: Partial< typeof customizeStoreStateMachineServices >;
} ) => {
	useFullScreen( [ 'woocommerce-customize-store' ] );

	const augmentedStateMachine = useMemo( () => {
		return customizeStoreStateMachineDefinition.withConfig( {
			services: {
				...customizeStoreStateMachineServices,
				...servicesOverrides,
			},
			actions: {
				...customizeStoreStateMachineActions,
				...actionOverrides,
			},
			guards: {
				log: ( _ctx, _evt, { cond } ) => {
					console.log( 'log', _ctx.intro, cond );
					return true;
				},
				hasStepInUrl: ( _ctx, _evt, { cond }: { cond: unknown } ) => {
					const { path = '' } = getQuery() as { path: string };
					const pathFragments = path.split( '/' );
					return (
						pathFragments[ 2 ] === // [0] '', [1] 'customize-store', [2] step slug
						( cond as { step: string | undefined } ).step
					);
				},
				isAiOnline: ( _ctx ) => {
					return _ctx.flowType === FlowType.AIOnline;
				},
				isAiOffline: ( _ctx ) => {
					return _ctx.flowType === FlowType.AIOffline;
				},
				isWooExpress: () => isWooExpress(),
				isNotWooExpress: () => ! isWooExpress(),
				activeThemeHasMods: ( _ctx ) => {
					return !! _ctx.intro.activeThemeHasMods;
				},
				activeThemeIsNotModified: ( _ctx ) => {
					return ! _ctx.intro.activeThemeHasMods;
				},
			},
		} );
	}, [ actionOverrides, servicesOverrides ] );

	const [ state, send, service ] = useMachine( augmentedStateMachine, {
		devTools: process.env.NODE_ENV === 'development',
	} );
	// eslint-disable-next-line react-hooks/exhaustive-deps -- false positive due to function name match, this isn't from react std lib
	const currentNodeMeta = useSelector( service, ( currentState ) =>
		findComponentMeta< CustomizeStoreComponentMeta >(
			currentState?.meta ?? undefined
		)
	);

	const [ CurrentComponent, setCurrentComponent ] =
		useState< CustomizeStoreComponent | null >( null );
	useEffect( () => {
		if ( currentNodeMeta?.component ) {
			setCurrentComponent( () => currentNodeMeta?.component );
		}
	}, [ CurrentComponent, currentNodeMeta?.component ] );

	// Run listeners for parent window.
	useEffect( () => {
		const removeListener = attachParentListeners();
		return removeListener;
	}, [] );

	useBodyClass( 'is-fullscreen-mode' );

	const currentNodeCssLabel =
		state.value instanceof Object
			? Object.keys( state.value )[ 0 ]
			: state.value;

	return (
		<>
			<div
				className={ `woocommerce-customize-store__container woocommerce-customize-store__step-${ currentNodeCssLabel }` }
			>
				{ CurrentComponent ? (
					<CurrentComponent
						parentMachine={ service }
						sendEvent={ send }
						context={ state.context }
						currentState={ state.value }
					/>
				) : (
					<CYSSpinner />
				) }
			</div>
			{ /* @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated. */ }
			<PluginArea scope="woocommerce-customize-store" />
		</>
	);
};

export default CustomizeStoreController;
