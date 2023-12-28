/**
 * External dependencies
 */
import { useCallback } from 'react';
import apiFetch from '@wordpress/api-fetch';
/**
 * Internal dependencies
 */
import {
	prepareFormData,
	recordWooAIAssistantTracks,
} from '../woo-ai-assistant/utils';
import makeWCRestApiCall from '../utils/wcRestApi';

interface ApiResponse {
	answer: any; // Replace 'any' with a more specific type if available
	function_id: string;
	run_id: string;
	function_name: string;
	function_args: any; // Replace 'any' with a more specific type if available
	status: string;
}

interface HandleRequiresActionProps {
	setMessages: React.Dispatch< React.SetStateAction< Message[] > >;
	setLoading: React.Dispatch< React.SetStateAction< boolean > >;
	generateInformationalMessage: ( message: string ) => void;
	handleError: ( message: string ) => void;
	isResponseError: boolean;
}

interface Message {
	sender: 'assistant' | 'user';
	text: string;
	timestamp: number;
	type: 'actionable' | 'informational';
	userQueryIndex?: number;
}

const useHandleRequiresAction = ( {
	setMessages,
	setLoading,
	generateInformationalMessage,
	handleError,
	isResponseError,
}: HandleRequiresActionProps ) => {
	const handleRequiresAction = useCallback(
		async (
			response: ApiResponse,
			token: string,
			tempthreadID: string,
			userQuery: string
		) => {
			const answer = response.answer;
			const functionID: string = answer.function_id;
			const runID: string = response.run_id;

			generateInformationalMessage(
				"I'm trying to accomplish that for you now..."
			);
			let message = '';

			if ( answer.function_name === 'makeWCRestApiCall' ) {
				const functionArguments = answer.function_args;
				try {
					if (
						[ 'DELETE', 'PUT' ].includes(
							functionArguments.httpVerb
						)
					) {
						recordWooAIAssistantTracks( 'rest_api_delete', {
							message: userQuery,
							functionArguments,
						} );
						throw new Error(
							"I'm sorry, but I cannot delete or modify data as I'm still an experimental feature."
						);
					}

					const responseBody = await makeWCRestApiCall(
						functionArguments
					);
					const isStatusIndicatingFailure = new RegExp( /[45]\d{2}/ );
					if (
						isStatusIndicatingFailure.test( responseBody.status )
					) {
						throw new Error(
							`Unsuccessful request: ${ responseBody.status }`
						);
					}

					message = responseBody.message;
				} catch ( error: any ) {
					recordWooAIAssistantTracks( 'rest_api_error', {
						error: error?.message,
						message: userQuery,
					} );
					handleError( error?.message );
				}

				try {
					const formData = prepareFormData(
						message,
						token,
						tempthreadID
					);
					formData.append( 'run_id', runID );
					formData.append( 'tool_call_id', functionID );
					formData.append( 'output', message );
					formData.append( 'thread_id', tempthreadID );

					await apiFetch( {
						url: 'https://public-api.wordpress.com/wpcom/v2/woo-wizard/submit-tool-output',
						method: 'POST',
						body: formData,
					} );
				} catch ( error ) {
					handleError(
						"I believe that I was able to accomplish the task you requested, but I'm having trouble updating our conversation."
					);
				}

				if ( isResponseError ) {
					setMessages( ( prevMessages ) => [
						...prevMessages,
						{
							sender: 'assistant',
							text: message,
							timestamp: new Date().getTime(),
							type: 'actionable',
						},
					] );
					setLoading( false );
					return Promise.reject();
				}

				// Additional logic for handling summary can be added here if needed
			}
			return message;
		},
		[
			setMessages,
			setLoading,
			generateInformationalMessage,
			handleError,
			isResponseError,
		]
	);

	return handleRequiresAction;
};

export default useHandleRequiresAction;
