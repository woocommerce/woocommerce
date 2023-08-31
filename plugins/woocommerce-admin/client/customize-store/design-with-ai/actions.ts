/**
 * External dependencies
 */
import { DoneInvokeEvent, assign } from 'xstate';

/**
 * Internal dependencies
 */
import {
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents,
	AIEndpointResponse,
} from './types';
import {
	businessInfoDescriptionCompleteEvent,
	lookAndFeelCompleteEvent,
	toneOfVoiceCompleteEvent,
} from './pages';

const assignBusinessInfoDescription = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	businessInfoDescription: ( context, event: unknown ) => {
		return {
			descriptionText: ( event as businessInfoDescriptionCompleteEvent )
				.payload,
		};
	},
} );

const assignLookAndFeel = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	lookAndFeel: ( context, event: unknown ) => {
		return {
			choice: ( event as lookAndFeelCompleteEvent ).payload,
		};
	},
} );

const assignToneOfVoice = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	toneOfVoice: ( context, event: unknown ) => {
		return {
			choice: ( event as toneOfVoiceCompleteEvent ).payload,
		};
	},
} );

const logAiWizardClosedBeforeCompletion = () => {
	// track
};

const assignLookAndTone = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	lookAndFeel: ( context, event: unknown ) => {
		return {
			// @ts-expect-error -- temp
			choice: event.data.look,
		};
	},
	toneOfVoice: ( context, event: unknown ) => {
		return {
			// @ts-expect-error -- temp
			choice: event.data.tone,
		};
	},
} );

export const actions = {
	assignBusinessInfoDescription,
	assignLookAndFeel,
	assignToneOfVoice,
	logAiWizardClosedBeforeCompletion,
	assignLookAndTone,
};
