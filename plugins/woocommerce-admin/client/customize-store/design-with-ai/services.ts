/**
 * External dependencies
 */
import { __experimentalRequestJetpackToken as requestJetpackToken } from '@woocommerce/ai';
import apiFetch from '@wordpress/api-fetch';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import {
	Look,
	Tone,
	VALID_LOOKS,
	VALID_TONES,
	designWithAiStateMachineContext,
} from './types';

export interface LookAndToneCompletionResponse {
	look: Look;
	tone: Tone;
}

interface MaybeLookAndToneCompletionResponse {
	completion: string;
}

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

export const parseLookAndToneCompletionResponse = (
	obj: MaybeLookAndToneCompletionResponse
): LookAndToneCompletionResponse => {
	try {
		const o = JSON.parse( obj.completion );
		if ( isLookAndToneCompletionResponse( o ) ) {
			return o;
		}
	} catch {
		recordEvent(
			'customize_your_store_look_and_tone_ai_completion_response_error',
			{ error_type: 'json_parse_error', response: JSON.stringify( obj ) }
		);
	}
	recordEvent(
		'customize_your_store_look_and_tone_ai_completion_response_error',
		{
			error_type: 'valid_json_invalid_values',
			response: JSON.stringify( obj ),
		}
	);
	throw new Error( 'Could not parse Look and Tone completion response.' );
};

export const getLookAndTone = async (
	context: designWithAiStateMachineContext
) => {
	const prompt = [
		'You are a WordPress theme expert.',
		'Analyze the following store description and determine the look and tone of the theme.',
		`For look, you can choose between ${ VALID_LOOKS.join( ',' ) }.`,
		`For tone of the description, you can choose between ${ VALID_TONES.join(
			','
		) }.`,
		'Your response should be in json with look and tone values.',
		'\n',
		context.businessInfoDescription.descriptionText,
	];

	const { token } = await requestJetpackToken();

	const url = new URL(
		'https://public-api.wordpress.com/wpcom/v2/text-completion'
	);

	url.searchParams.append( 'feature', 'woo_cys' );

	const data: {
		completion: string;
	} = await apiFetch( {
		url: url.toString(),
		method: 'POST',
		data: {
			token,
			prompt: prompt.join( '\n' ),
			_fields: 'completion',
		},
	} );

	return parseLookAndToneCompletionResponse( data );
};

export const services = {
	getLookAndTone,
};
