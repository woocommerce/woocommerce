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
			type: 'API_CALL_TO_AI_SUCCCESSFUL';
	  }
	| {
			type: 'API_CALL_TO_AI_FAILED';
	  };

export const VALID_LOOKS = [ 'Contemporary', 'Classic', 'Bold' ] as const;
export const VALID_TONES = [ 'Informal', 'Neutral', 'Formal' ] as const;
export type Look = ( typeof VALID_LOOKS )[ number ];
export type Tone = ( typeof VALID_TONES )[ number ];
