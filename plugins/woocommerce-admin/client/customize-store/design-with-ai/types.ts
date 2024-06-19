/**
 * External dependencies
 */
import { z } from 'zod';
import { spawn } from 'xstate';
/**
 * Internal dependencies
 */
import {
	colorPaletteValidator,
	fontChoiceValidator,
	headerValidator,
	footerValidator,
	colorPaletteResponseValidator,
	homepageTemplateValidator,
} from './prompts';

export type designWithAiStateMachineContext = {
	startLoadingTime: number | null;
	businessInfoDescription: {
		descriptionText: string;
	};
	lookAndFeel: {
		aiRecommended?: Look;
		choice: Look | '';
	};
	toneOfVoice: {
		aiRecommended?: Tone;
		choice: Tone | '';
	};
	aiSuggestions: {
		defaultColorPalette: ColorPaletteResponse;
		fontPairing: FontPairing[ 'pair_name' ];
		homepageTemplate: HomepageTemplate[ 'homepage_template' ];
	};
	apiCallLoader: {
		hasErrors: boolean;
	};
	// If we require more data from options, previously provided core profiler details,
	// we can retrieve them in preBusinessInfoDescription and then assign them here
	spawnSaveDescriptionToOptionRef?: ReturnType< typeof spawn >;
	aiOnline: boolean;
	isBlockTheme: boolean;
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
export type ColorPaletteResponse = z.infer<
	typeof colorPaletteResponseValidator
>;

export type FontPairing = z.infer< typeof fontChoiceValidator >;

export type Header = z.infer< typeof headerValidator >;

export type Footer = z.infer< typeof footerValidator >;

export type HomepageTemplate = z.infer< typeof homepageTemplateValidator >;
