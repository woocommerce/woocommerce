/**
 * Internal dependencies
 */
import {
	Look,
	Tone,
	VALID_LOOKS,
	VALID_TONES,
	LookAndToneCompletionResponse,
} from '../types';

export const isLookAndToneCompletionResponse = (
	obj: unknown
): obj is LookAndToneCompletionResponse => {
	return (
		obj !== undefined &&
		obj !== null &&
		typeof obj === 'object' &&
		'look' in obj &&
		VALID_LOOKS.includes( obj.look as Look ) &&
		'tone' in obj &&
		VALID_TONES.includes( obj.tone as Tone )
	);
};

export const lookAndTone = {
	queryId: 'look_and_tone',
	// make sure version is updated every time the prompt is changed
	version: '2023-09-18',
	prompt: ( businessInfoDescription: string ) => {
		return [
			'You are a WordPress theme expert.',
			'Analyze the following store description and determine the look and tone of the theme.',
			`For look, you can choose between ${ VALID_LOOKS.join( ',' ) }.`,
			`For tone of the description, you can choose between ${ VALID_TONES.join(
				','
			) }.`,
			'Your response should be in json with look and tone values.',
			'\n',
			businessInfoDescription,
		].join( '\n' );
	},
	responseValidation: ( response: unknown ) => {
		if ( isLookAndToneCompletionResponse( response ) ) {
			return response;
		}
		throw new Error(
			'Invalid values in Look and Tone completion response'
		);
	},
};
