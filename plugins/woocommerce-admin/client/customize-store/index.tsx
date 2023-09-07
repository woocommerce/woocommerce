/**
 * External dependencies
 */
import { Sender, createMachine } from 'xstate';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { useMachine, useSelector } from '@xstate/react';
import { getQuery, updateQueryString } from '@woocommerce/navigation';

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
import { AssemblerHub, events as assemblerHubEvents } from './assembler-hub';
import { findComponentMeta } from '~/utils/xstate/find-component';
import {
	CustomizeStoreComponentMeta,
	CustomizeStoreComponent,
	customizeStoreStateMachineContext,
} from './types';
import { ThemeCard } from './intro/theme-cards';
import './style.scss';

export type customizeStoreStateMachineEvents =
	| introEvents
	| designWithAiEvents
	| assemblerHubEvents
	| { type: 'AI_WIZARD_CLOSED_BEFORE_COMPLETION'; payload: { step: string } }
	| { type: 'EXTERNAL_URL_UPDATE' };

const updateQueryStep = ( _context: unknown, _evt: unknown, meta: unknown ) => {
	const { path } = getQuery() as { path: string };
	const step = ( meta as { step: string } ).step;
	const pathFragments = path.split( '/' ); // [0] '', [1] 'customize-store', [2] step slug [3] design-with-ai, assembler-hub path fragments
	if ( pathFragments[ 1 ] === 'customize-store' ) {
		if ( pathFragments[ 2 ] !== step ) {
			// this state machine is only concerned with [2], so we ignore changes to [3]
			// [1] is handled by router at root of wc-admin
			updateQueryString( {}, `/customize-store/${ step }` );
		}
	}
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

export const machineActions = {
	updateQueryStep,
};

export const customizeStoreStateMachineActions = {
	...introActions,
	...machineActions,
};

export const customizeStoreStateMachineServices = {
	...introServices,
	browserPopstateHandler,
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
			themeCards: [] as ThemeCard[],
			activeTheme: '',
		},
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
					target: 'assemblerHub',
					cond: {
						type: 'hasStepInUrl',
						step: 'assembler-hub',
					},
				},
				{
					target: 'intro',
				},
			],
		},
		intro: {
			id: 'intro',
			initial: 'preIntro',
			states: {
				preIntro: {
					invoke: {
						src: 'fetchThemeCards',
						onDone: {
							target: 'intro',
							actions: [ 'assignThemeCards' ],
						},
					},
				},
				intro: {
					meta: {
						component: Intro,
					},
				},
			},
			on: {
				DESIGN_WITH_AI: {
					target: 'designWithAi',
				},
				SELECTED_ACTIVE_THEME: {
					target: 'assemblerHub',
				},
				CLICKED_ON_BREADCRUMB: {
					target: 'backToHomescreen',
				},
				SELECTED_NEW_THEME: {
					target: 'appearanceTask',
				},
				SELECTED_BROWSE_ALL_THEMES: {
					target: 'appearanceTask',
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
			meta: {
				component: AssemblerHub,
			},
			on: {
				FINISH_CUSTOMIZATION: {
					target: 'backToHomescreen',
				},
				GO_BACK_TO_DESIGN_WITH_AI: {
					target: 'designWithAi',
				},
			},
		},
		backToHomescreen: {},
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
				hasStepInUrl: ( _ctx, _evt, { cond }: { cond: unknown } ) => {
					const { path = '' } = getQuery() as { path: string };
					const pathFragments = path.split( '/' );
					return (
						pathFragments[ 2 ] === // [0] '', [1] 'customize-store', [2] step slug
						( cond as { step: string | undefined } ).step
					);
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

	const currentNodeCssLabel =
		state.value instanceof Object
			? Object.keys( state.value )[ 0 ]
			: state.value;

	return (
		<>
			<div
				className={ `woocommerce-profile-wizard__container woocommerce-profile-wizard__step-${ currentNodeCssLabel }` }
			>
				{ CurrentComponent ? (
					<CurrentComponent
						parentMachine={ service }
						sendEvent={ send }
						context={ state.context }
					/>
				) : (
					<div />
				) }
			</div>
		</>
	);
};

export default CustomizeStoreController;
