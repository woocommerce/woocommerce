/**
 * External dependencies
 */
import { createMachine } from 'xstate';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { useMachine, useSelector } from '@xstate/react';

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
import { events as assemblerHubEvents } from './assembler-hub';
import { findComponentMeta } from '~/utils/xstate/find-component';
import {
	CustomizeStoreComponentMeta,
	CustomizeStoreComponent,
	customizeStoreStateMachineContext,
} from './types';
import { ThemeCard } from './intro/theme-cards';

export type customizeStoreStateMachineEvents =
	| introEvents
	| designWithAiEvents
	| assemblerHubEvents;

export const customizeStoreStateMachineServices = {
	...introServices,
};

export const customizeStoreStateMachineActions = {
	...introActions,
};
export const customizeStoreStateMachineDefinition = createMachine( {
	id: 'customizeStore',
	initial: 'intro',
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
	states: {
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
					target: '? Appearance Task ?',
				},
				SELECTED_BROWSE_ALL_THEMES: {
					target: '? Appearance Task ?',
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
				},
			},
			on: {
				THEME_SUGGESTED: {
					target: 'assemblerHub',
				},
			},
		},
		assemblerHub: {
			on: {
				FINISH_CUSTOMIZATION: {
					target: 'backToHomescreen',
				},
			},
		},
		backToHomescreen: {},
		'? Appearance Task ?': {},
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
			guards: {},
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
