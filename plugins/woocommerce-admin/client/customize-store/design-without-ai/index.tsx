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
import { AssembleHubLoader } from '../design-with-ai/pages';

export type DesignWithoutAiComponent = typeof AssembleHubLoader;
export type DesignWithoutAiComponentMeta = {
	component: DesignWithoutAiComponent;
};

export const DesignWithNoAiController = ( {
	parentMachine,
}: {
	parentMachine?: AnyInterpreter;
	sendEventToParent?: Sender< customizeStoreStateMachineEvents >;
	parentContext?: customizeStoreStateMachineContext;
} ) => {
	const [ , , service ] = useMachine( designWithNoAiStateMachineDefinition, {
		devTools: process.env.NODE_ENV === 'development',
		parent: parentMachine,
	} );

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
				{ CurrentComponent ? <CurrentComponent /> : <div /> }
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
