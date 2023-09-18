/**
 * External dependencies
 */
import { assign } from 'xstate';
import { getQuery, updateQueryString } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import {
	ColorPalette,
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents,
	FontPairing,
	LookAndToneCompletionResponse,
} from './types';
import { aiWizardClosedBeforeCompletionEvent } from './events';
import {
	businessInfoDescriptionCompleteEvent,
	lookAndFeelCompleteEvent,
	toneOfVoiceCompleteEvent,
} from './pages';

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

const assignDefaultColorPalette = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	aiSuggestions: ( context, event: unknown ) => {
		return {
			...context.aiSuggestions,
			defaultColorPalette: (
				event as {
					data: {
						response: ColorPalette;
					};
				}
			 ).data.response,
		};
	},
} );

const assignFontPairing = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	aiSuggestions: ( context, event: unknown ) => {
		return {
			...context.aiSuggestions,
			fontPairing: (
				event as {
					data: {
						response: FontPairing;
					};
				}
			 ).data.response.pair_name,
		};
	},
} );

const logAIAPIRequestError = () => {
	// log AI API request error
	// eslint-disable-next-line no-console
	console.log( 'API Request error' );
};

const updateQueryStep = (
	_context: unknown,
	_evt: unknown,
	{ action }: { action: unknown }
) => {
	const { path } = getQuery() as { path: string };
	const step = ( action as { step: string } ).step;
	const pathFragments = path.split( '/' ); // [0] '', [1] 'customize-store', [2] cys step slug [3] design-with-ai step slug
	if (
		pathFragments[ 1 ] === 'customize-store' &&
		pathFragments[ 2 ] === 'design-with-ai'
	) {
		if ( pathFragments[ 3 ] !== step ) {
			// this state machine is only concerned with [2], so we ignore changes to [3]
			// [1] is handled by router at root of wc-admin
			updateQueryString(
				{},
				`/customize-store/design-with-ai/${ step }`
			);
		}
	}
};

const recordTracksStepViewed = (
	_context: unknown,
	_event: unknown,
	{ action }: { action: unknown }
) => {
	const { step } = action as { step: string };
	recordEvent( 'customize_your_store_ai_wizard_step_view', {
		step,
	} );
};

const recordTracksStepClosed = (
	_context: unknown,
	event: aiWizardClosedBeforeCompletionEvent
) => {
	const { step } = event.payload;
	recordEvent( `customize_your_store_ai_wizard_step_close`, {
		step: step.replaceAll( '-', '_' ),
	} );
};

const recordTracksStepCompleted = (
	_context: unknown,
	_event: unknown,
	{ action }: { action: unknown }
) => {
	const { step } = action as { step: string };
	recordEvent( 'customize_your_store_ai_wizard_step_complete', {
		step,
	} );
};

export const actions = {
	assignBusinessInfoDescription,
	assignLookAndFeel,
	assignToneOfVoice,
	assignLookAndTone,
	assignDefaultColorPalette,
	assignFontPairing,
	logAIAPIRequestError,
	updateQueryStep,
	recordTracksStepViewed,
	recordTracksStepClosed,
	recordTracksStepCompleted,
};
