/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { Sender, createMachine } from 'xstate';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { useMachine, useSelector } from '@xstate/react';
import {
	getNewPath,
	getQuery,
	updateQueryString,
	getHistory,
	getPersistedQuery,
} from '@woocommerce/navigation';
import { optionsStore } from '@woocommerce/data';
import { dispatch, resolveSelect } from '@wordpress/data';
import { Spinner } from '@woocommerce/components';
import { PluginArea } from '@wordpress/plugins';
import { accessTaskReferralStorage } from '@woocommerce/onboarding';

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
import { DesignWithoutAi } from './design-without-ai';

import { AssemblerHub, events as assemblerHubEvents } from './assembler-hub';
import { services as transitionalServices } from './transitional';
import { findComponentMeta } from '~/utils/xstate/find-component';
import {
	CustomizeStoreComponentMeta,
	CustomizeStoreComponent,
	customizeStoreStateMachineContext,
} from './types';
import './style.scss';
import {
	navigateOrParent,
	attachParentListeners,
	isIframe,
	redirectToThemes,
} from './utils';
import useBodyClass from './hooks/use-body-class';
import { useXStateInspect } from '~/xstate';

export type customizeStoreStateMachineEvents =
	| introEvents
	| assemblerHubEvents
	| { type: 'EXTERNAL_URL_UPDATE' }
	| { type: 'INSTALL_FONTS' }
	| { type: 'NO_AI_FLOW_ERROR'; payload: { hasError: boolean } }
	| { type: 'IS_FONT_LIBRARY_AVAILABLE'; payload: boolean };

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
			const newPath = `/customize-store/${ step }`;

			// Since CYS also runs inside an iframe and because the getHistory
			// creates an instance per window context, making a push to the
			// history only alters that instance. Here we need to alter the
			// browser's (window.top) history and not the history of the iframe.
			if ( isIframe( window ) && window.top ) {
				// window.location.href does not fit in this case since it produces
				// a hard refresh to the page.
				window.top.history.pushState(
					{},
					'',
					getNewPath( {}, newPath )
				);
				return;
			}

			updateQueryString( {}, newPath );
		}
	}
};

const redirectToWooHome = () => {
	const url = getNewPath( getPersistedQuery(), '/', {} );
	navigateOrParent( window, url );
};

const goBack = () => {
	const history = getHistory();
	if (
		history.__experimentalLocationStack.length >= 2 &&
		! history.__experimentalLocationStack[
			history.__experimentalLocationStack.length - 2
		].search.includes( 'customize-store' )
	) {
		// If the previous location is not a customize-store step, go back in history.
		history.back();
		return;
	}

	redirectToWooHome();
};

const markTaskComplete = async () => {
	const currentTemplateId: string | undefined = await resolveSelect(
		coreStore
	).getDefaultTemplateId( { slug: 'home' } );
	return dispatch( optionsStore ).updateOptions( {
		woocommerce_admin_customize_store_completed: 'yes',
		// We use this on the intro page to determine if this same theme was used in the last customization.
		woocommerce_admin_customize_store_completed_theme_id: currentTemplateId,
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

const redirectToReferrer = () => {
	const { getWithExpiry: getCYSTaskReferral, remove: removeCYSTaskReferral } =
		accessTaskReferralStorage( { taskId: 'customize-store' } );

	const taskReferral = getCYSTaskReferral();

	if ( taskReferral ) {
		removeCYSTaskReferral();
		window.location.href = taskReferral.returnUrl;
	}
};

export const machineActions = {
	updateQueryStep,
	redirectToWooHome,
	redirectToThemes,
	redirectToReferrer,
	goBack,
};

export const customizeStoreStateMachineActions = {
	...introActions,
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
	initial: 'setFlags',
	predictableActionArguments: true,
	preserveActionOrder: true,
	schema: {
		context: {} as customizeStoreStateMachineContext,
		events: {} as customizeStoreStateMachineEvents,
	},
	context: {
		intro: {
			hasErrors: false,
			activeTheme: '',
			customizeStoreTaskCompleted: false,
		},
		isFontLibraryAvailable: null,
		isPTKPatternsAPIAvailable: null,
		activeThemeHasMods: undefined,
	} as customizeStoreStateMachineContext,
	invoke: {
		src: 'browserPopstateHandler',
	},
	on: {
		GO_BACK_TO_DESIGN_WITHOUT_AI: {
			target: 'intro',
			actions: [ { type: 'updateQueryStep', step: 'intro' } ],
		},
		EXTERNAL_URL_UPDATE: {
			target: 'navigate',
		},
		NO_AI_FLOW_ERROR: {
			target: 'intro',
			actions: [
				{ type: 'assignNoAIFlowError' },
				{ type: 'updateQueryStep', step: 'intro' },
			],
		},
		INSTALL_FONTS: {
			target: 'designWithoutAi.installFonts',
		},
	},
	states: {
		setFlags: {
			invoke: {
				src: 'setFlags',
				onDone: {
					actions: 'assignFlags',
					target: 'navigate',
				},
			},
		},
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
			initial: 'fetchIntroData',
			states: {
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
										'assignActiveTheme',
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
					actions: 'goBack',
				},
				DESIGN_WITHOUT_AI: {
					actions: [ 'recordTracksDesignWithoutAIClicked' ],
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
						target: 'designWithoutAi',
					},
				},
				designWithoutAi: {
					entry: [ { type: 'updateQueryStep', step: 'design' } ],
					meta: {
						component: DesignWithoutAi,
					},
				},
				// This state is used to install fonts and then redirect to the assembler hub.
				installFonts: {
					entry: [
						{
							type: 'updateQueryStep',
							step: 'design/install-fonts',
						},
					],
					meta: {
						component: DesignWithoutAi,
					},
				},
				// This state is used to install patterns and then redirect to the assembler hub.
				installPatterns: {
					entry: [
						{
							type: 'updateQueryStep',
							step: 'design/install-patterns',
						},
					],
					meta: {
						component: DesignWithoutAi,
					},
				},
			},
		},
		assemblerHub: {
			initial: 'fetchCustomizeStoreCompleted',
			states: {
				fetchCustomizeStoreCompleted: {
					invoke: {
						src: 'fetchCustomizeStoreCompleted',
						onDone: {
							actions: 'assignCustomizeStoreCompleted',
							target: 'checkCustomizeStoreCompleted',
						},
					},
				},
				checkCustomizeStoreCompleted: {
					always: [
						{
							// Redirect to the "intro step" if the active theme has no modifications.
							cond: 'customizeTaskIsNotCompleted',
							actions: [
								{ type: 'updateQueryStep', step: 'intro' },
							],
							target: '#customizeStore.intro',
						},
						{
							// Otherwise, proceed to the next step.
							cond: 'customizeTaskIsCompleted',
							target: 'assemblerHub',
						},
					],
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
			},
		},
		transitionalScreen: {
			initial: 'fetchCustomizeStoreCompleted',
			states: {
				fetchCustomizeStoreCompleted: {
					invoke: {
						src: 'fetchCustomizeStoreCompleted',
						onDone: {
							actions: 'assignCustomizeStoreCompleted',
							target: 'checkCustomizeStoreCompleted',
						},
					},
				},
				checkCustomizeStoreCompleted: {
					always: [
						{
							// Redirect to the "intro step" if the active theme has no modifications.
							cond: 'customizeTaskIsNotCompleted',
							actions: [
								{ type: 'updateQueryStep', step: 'intro' },
							],
							target: '#customizeStore.intro',
						},
						{
							cond: 'hasTaskReferral',
							target: 'skipTransitional',
						},
						{
							// Otherwise, proceed to the next step.
							cond: 'customizeTaskIsCompleted',
							target: 'preTransitional',
						},
					],
				},
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
						},
					},
				},
				skipTransitional: {
					entry: [ 'redirectToReferrer' ],
				},
				transitional: {
					entry: [
						{ type: 'updateQueryStep', step: 'transitional' },
					],
					meta: {
						component: AssemblerHub,
					},
				},
			},
		},
		appearanceTask: {},
	},
} );

declare global {
	interface Window {
		__wcCustomizeStore: {
			isFontLibraryAvailable: boolean | null;
			isPTKPatternsAPIAvailable: boolean | null;
			activeThemeHasMods: boolean | undefined;
			sendEventToIntroMachine: (
				typeEvent: customizeStoreStateMachineEvents
			) => void;
		};
	}
}

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
				hasStepInUrl: ( _ctx, _evt, { cond }: { cond: unknown } ) => {
					const { path = '' } = getQuery() as { path: string };
					const pathFragments = path.split( '/' );
					return (
						pathFragments[ 2 ] === // [0] '', [1] 'customize-store', [2] step slug
						( cond as { step: string | undefined } ).step
					);
				},
				activeThemeHasMods: ( _ctx ) => {
					return !! _ctx.activeThemeHasMods;
				},
				activeThemeHasNoMods: ( _ctx ) => {
					return ! _ctx.activeThemeHasMods;
				},
				customizeTaskIsCompleted: ( _ctx ) => {
					return _ctx.intro.customizeStoreTaskCompleted;
				},
				customizeTaskIsNotCompleted: ( _ctx ) => {
					return ! _ctx.intro.customizeStoreTaskCompleted;
				},
				hasTaskReferral: () => {
					const { getWithExpiry: getCYSTaskReferral } =
						accessTaskReferralStorage( {
							taskId: 'customize-store',
						} );
					return getCYSTaskReferral() !== null;
				},
			},
		} );
	}, [ actionOverrides, servicesOverrides ] );

	const { versionEnabled } = useXStateInspect();

	const [ state, send, service ] = useMachine( augmentedStateMachine, {
		devTools: versionEnabled === 'V4',
	} );

	useEffect( () => {
		if ( isIframe( window ) ) {
			return;
		}
		window.__wcCustomizeStore = {
			...window.__wcCustomizeStore,
			// This is needed because the iframe loads the entire Customize Store app.
			// This means that the iframe instance will have different state machines
			// than the parent window.
			// Check https://github.com/woocommerce/woocommerce/issues/45278 for more details.
			sendEventToIntroMachine: (
				typeEvent: customizeStoreStateMachineEvents
			) => send( typeEvent ),
		};
	}, [ send ] );

	window.__wcCustomizeStore = {
		...window.__wcCustomizeStore,
	};

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
			<PluginArea scope="woocommerce-customize-store" />
		</>
	);
};

export default CustomizeStoreController;
