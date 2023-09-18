/**
 * External dependencies
 */
import { z } from 'zod';
/**
 * Internal dependencies
 */
import { colorPaletteValidator, fontChoiceValidator } from './prompts';

export type designWithAiStateMachineContext = {
	businessInfoDescription: {
		descriptionText: string;
	};
	lookAndFeel: {
		choice: Look | '';
	};
	toneOfVoice: {
		choice: Tone | '';
	};
	aiSuggestions: {
		defaultColorPalette: ColorPalette;
		fontPairing: FontPairing[ 'pair_name' ];
	};
	// If we require more data from options, previously provided core profiler details,
	// we can retrieve them in preBusinessInfoDescription and then assign them here
};
export type designWithAiStateMachineEvents =
	| { type: 'AI_WIZARD_CLOSED_BEFORE_COMPLETION'; payload: { step: string } }
	| {
			type: 'BUSINESS_INFO_DESCRIPTION_COMPLETE';
			payload: string;
	  }
	| {
			type: 'LOOK_AND_FEEL_COMPLETE';
	  }
	| {
			type: 'TONE_OF_VOICE_COMPLETE';
	  }
	| {
			type: 'EXTERNAL_URL_UPDATE';
	  };

export const VALID_LOOKS = [ 'Contemporary', 'Classic', 'Bold' ] as const;
export const VALID_TONES = [ 'Informal', 'Neutral', 'Formal' ] as const;
export type Look = ( typeof VALID_LOOKS )[ number ];
export type Tone = ( typeof VALID_TONES )[ number ];

export interface LookAndToneCompletionResponse {
	look: Look;
	tone: Tone;
}

export type ColorPalette = z.infer< typeof colorPaletteValidator >;

export type FontPairing = z.infer< typeof fontChoiceValidator >;
