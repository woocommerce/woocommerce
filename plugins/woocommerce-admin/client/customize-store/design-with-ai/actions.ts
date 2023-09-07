/**
 * External dependencies
 */
import { assign } from 'xstate';

/**
 * Internal dependencies
 */
import {
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents,
} from './types';
import {
	businessInfoDescriptionCompleteEvent,
	lookAndFeelCompleteEvent,
	toneOfVoiceCompleteEvent,
} from './pages';
import { LookAndToneCompletionResponse } from './services';

const assignBusinessInfoDescription = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	businessInfoDescription: ( _context, event: unknown ) => {
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
	lookAndFeel: ( _context, event: unknown ) => {
		return {
			choice: ( event as lookAndFeelCompleteEvent ).payload,
		};
	},
} );

const assignToneOfVoice = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	toneOfVoice: ( _context, event: unknown ) => {
		return {
			choice: ( event as toneOfVoiceCompleteEvent ).payload,
		};
	},
} );

const assignLookAndTone = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	lookAndFeel: ( _context, event: unknown ) => {
		return {
			choice: ( event as { data: LookAndToneCompletionResponse } ).data
				.look,
		};
	},
	toneOfVoice: ( _context, event: unknown ) => {
		return {
			choice: ( event as { data: LookAndToneCompletionResponse } ).data
				.tone,
		};
	},
} );

const logAIAPIRequestError = () => {
	// log AI API request error
	// eslint-disable-next-line no-console
	console.log( 'API Request error' );
};

export const actions = {
	assignBusinessInfoDescription,
	assignLookAndFeel,
	assignToneOfVoice,
	assignLookAndTone,
	logAIAPIRequestError,
};
