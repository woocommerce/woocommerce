/**
 * External dependencies
 */
import { useMachine, useSelector } from '@xstate/react';
import { useEffect, useState } from '@wordpress/element';
import { AnyInterpreter, Sender } from 'xstate';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import {
	CustomizeStoreComponent,
	FlowType,
	customizeStoreStateMachineContext,
} from '../types';
import { designWithAiStateMachineDefinition } from './state-machine';
import { findComponentMeta } from '~/utils/xstate/find-component';
import {
	BusinessInfoDescription,
	ApiCallLoader,
	LookAndFeel,
	ToneOfVoice,
} from './pages';
import { customizeStoreStateMachineEvents } from '..';
import './style.scss';
import { isAIFlow } from '../guards';
import { navigateOrParent } from '../utils';

export type events = { type: 'THEME_SUGGESTED' };
export type DesignWithAiComponent =
	| typeof BusinessInfoDescription
	| typeof ApiCallLoader
	| typeof LookAndFeel
	| typeof ToneOfVoice;
export type DesignWithAiComponentMeta = {
	component: DesignWithAiComponent;
};

export const DesignWithAiController = ( {
	parentMachine,
	parentContext,
}: {
	parentMachine?: AnyInterpreter;
	sendEventToParent?: Sender< customizeStoreStateMachineEvents >;
	parentContext?: customizeStoreStateMachineContext;
} ) => {
	// Assign aiOnline value from the parent context if it exists. Otherwise, ai is online by default.
	designWithAiStateMachineDefinition.context.aiOnline =
		parentContext?.flowType === FlowType.AIOnline;
	const [ state, send, service ] = useMachine(
		designWithAiStateMachineDefinition,
		{
			devTools: process.env.NODE_ENV === 'development',
			parent: parentMachine,
		}
	);

	// eslint-disable-next-line react-hooks/exhaustive-deps -- false positive due to function name match, this isn't from react std lib
	const currentNodeMeta = useSelector( service, ( currentState ) =>
		findComponentMeta< DesignWithAiComponentMeta >(
			currentState?.meta ?? undefined
		)
	);

	const [ CurrentComponent, setCurrentComponent ] =
		useState< DesignWithAiComponent | null >( null );
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
				className={ `woocommerce-design-with-ai__container woocommerce-design-with-ai-wizard__step-${ currentNodeCssLabel }` }
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

//loader should send event 'THEME_SUGGESTED' when it's done
export const DesignWithAi: CustomizeStoreComponent = ( {
	parentMachine,
	context,
} ) => {
	const assemblerUrl = getNewPath( {}, '/customize-store', {} );

	if ( ! isAIFlow( context.flowType ) ) {
		navigateOrParent( window, assemblerUrl );
		return null;
	}
	return (
		<>
			<DesignWithAiController
				parentMachine={ parentMachine }
				parentContext={ context }
			/>
		</>
	);
};
