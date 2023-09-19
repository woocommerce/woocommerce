/**
 * External dependencies
 */
import { __experimentalRequestJetpackToken as requestJetpackToken } from '@woocommerce/ai';
import apiFetch from '@wordpress/api-fetch';
import { recordEvent } from '@woocommerce/tracks';
import { Sender, assign, createMachine } from 'xstate';

/**
 * Internal dependencies
 */
import { designWithAiStateMachineContext } from './types';
import { lookAndTone } from './prompts';

const browserPopstateHandler =
	() => ( sendBack: Sender< { type: 'EXTERNAL_URL_UPDATE' } > ) => {
		const popstateHandler = () => {
			sendBack( { type: 'EXTERNAL_URL_UPDATE' } );
		};
		window.addEventListener( 'popstate', popstateHandler );
		return () => {
			window.removeEventListener( 'popstate', popstateHandler );
		};
	};

export const getCompletion = async < ValidResponseObject >( {
	queryId,
	prompt,
	version,
	responseValidation,
	retryCount,
}: {
	queryId: string;
	prompt: string;
	version: string;
	responseValidation: ( arg0: string ) => ValidResponseObject;
	retryCount: number;
} ) => {
	const { token } = await requestJetpackToken();
	let data: {
		completion: string;
	};
	let parsedCompletionJson;
	try {
		const url = new URL(
			'https://public-api.wordpress.com/wpcom/v2/text-completion'
		);

		url.searchParams.append( 'feature', 'woo_cys' );

		data = await apiFetch( {
			url: url.toString(),
			method: 'POST',
			data: {
				token,
				prompt,
				_fields: 'completion',
			},
		} );
	} catch ( error ) {
		recordEvent( 'customize_your_store_ai_completion_api_error', {
			query_id: queryId,
			version,
			retry_count: retryCount,
			error_type: 'api_request_error',
		} );
		throw error;
	}

	try {
		parsedCompletionJson = JSON.parse( data.completion );
	} catch {
		recordEvent( 'customize_your_store_ai_completion_response_error', {
			query_id: queryId,
			version,
			retry_count: retryCount,
			error_type: 'json_parse_error',
			response: data.completion,
		} );
		throw new Error(
			`Error validating Jetpack AI text completions response for ${ queryId }`
		);
	}

	try {
		const validatedResponse = responseValidation( parsedCompletionJson );
		recordEvent( 'customize_your_store_ai_completion_success', {
			query_id: queryId,
			version,
			retry_count: retryCount,
		} );
		return validatedResponse;
	} catch ( error ) {
		recordEvent( 'customize_your_store_ai_completion_response_error', {
			query_id: queryId,
			version,
			retry_count: retryCount,
			error_type: 'valid_json_invalid_values',
			response: data.completion,
		} );
		throw error;
	}
};

export const getLookAndTone = async (
	context: designWithAiStateMachineContext
) => {
	return getCompletion( {
		...lookAndTone,
		prompt: lookAndTone.prompt(
			context.businessInfoDescription.descriptionText
		),
		retryCount: 0,
	} );
};

export const queryAiEndpoint = createMachine(
	{
		id: 'query-ai-endpoint',
		predictableActionArguments: true,
		initial: 'init',
		context: {
			// these values are all overwritten by incoming parameters
			prompt: '',
			queryId: '',
			version: '',
			responseValidation: () => true,
			retryCount: 0,
			validatedResponse: {} as unknown,
		},
		states: {
			init: {
				always: 'querying',
				entry: [ 'setRetryCount' ],
			},
			querying: {
				invoke: {
					src: 'getCompletion',
					onDone: {
						target: 'success',
						actions: [ 'handleAiResponse' ],
					},
					onError: {
						target: 'error',
					},
				},
			},
			error: {
				always: [
					{
						cond: ( context ) => context.retryCount >= 3,
						target: 'failed',
					},
					{
						target: 'querying',
						actions: assign( {
							retryCount: ( context ) => context.retryCount + 1,
						} ),
					},
				],
			},
			failed: {
				type: 'final',
				data: {
					result: 'failed',
				},
			},
			success: {
				type: 'final',
				data: ( context ) => {
					return {
						result: 'success',
						response: context.validatedResponse,
					};
				},
			},
		},
	},
	{
		actions: {
			handleAiResponse: assign( {
				validatedResponse: ( _context, event: unknown ) =>
					( event as { data: unknown } ).data,
			} ),
			setRetryCount: assign( {
				retryCount: 0,
			} ),
		},
		services: {
			getCompletion,
		},
	}
);

export const services = {
	getLookAndTone,
	browserPopstateHandler,
	queryAiEndpoint,
};
