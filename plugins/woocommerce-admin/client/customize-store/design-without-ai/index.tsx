/**
 * External dependencies
 */
import { useMachine, useSelector } from '@xstate/react';
import { AnyInterpreter, Sender } from 'xstate';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { customizeStoreStateMachineEvents } from '..';
import {
	CustomizeStoreComponent,
	customizeStoreStateMachineContext,
} from '../types';
import { designWithNoAiStateMachineDefinition } from './state-machine';
import { findComponentMeta } from '~/utils/xstate/find-component';
import { AssembleHubLoader } from './pages/ApiCallLoader';
import { useXStateInspect } from '~/xstate';

export type DesignWithoutAiComponent = typeof AssembleHubLoader;
export type DesignWithoutAiComponentMeta = {
	component: DesignWithoutAiComponent;
};

export const DesignWithNoAiController = ( {
	parentMachine,
	parentContext,
}: {
	parentMachine?: AnyInterpreter;
	sendEventToParent?: Sender< customizeStoreStateMachineEvents >;
	parentContext?: customizeStoreStateMachineContext;
} ) => {
	const { versionEnabled } = useXStateInspect();
	const [ , send, service ] = useMachine(
		designWithNoAiStateMachineDefinition,
		{
			devTools: versionEnabled === 'V4',
			parent: parentMachine,
			context: {
				...designWithNoAiStateMachineDefinition.context,
				isFontLibraryAvailable:
					parentContext?.isFontLibraryAvailable ?? false,
			},
		}
	);

	// eslint-disable-next-line react-hooks/exhaustive-deps -- false positive due to function name match, this isn't from react std lib
	const currentNodeMeta = useSelector( service, ( currentState ) =>
		findComponentMeta< DesignWithoutAiComponentMeta >(
			currentState?.meta ?? undefined
		)
	);

	const [ CurrentComponent, setCurrentComponent ] =
		useState< DesignWithoutAiComponent | null >( null );

	useEffect( () => {
		if ( currentNodeMeta?.component ) {
			setCurrentComponent( () => currentNodeMeta?.component );
		}
	}, [ CurrentComponent, currentNodeMeta?.component ] );

	return (
		<>
			<div className={ `woocommerce-design-without-ai__container` }>
				{ CurrentComponent ? (
					<CurrentComponent sendEvent={ send } />
				) : (
					<div />
				) }
			</div>
		</>
	);
};

//loader should send event 'THEME_SUGGESTED' when it's done
export const DesignWithoutAi: CustomizeStoreComponent = ( {
	parentMachine,
	context,
} ) => {
	return (
		<>
			<DesignWithNoAiController
				parentMachine={ parentMachine }
				parentContext={ context }
			/>
		</>
	);
};
