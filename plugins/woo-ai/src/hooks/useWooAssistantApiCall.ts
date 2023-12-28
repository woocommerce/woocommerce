/**
 * External dependencies
 */
import { useCallback } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __experimentalRequestJetpackToken as requestJetpackToken } from '@woocommerce/ai';
/**
 * Internal dependencies
 */
import {
	prepareFormData,
	recordWooAIAssistantTracks,
} from '../woo-ai-assistant/utils';
import useHandleRequiresAction from './useHandleRequiresAction';
import { Message } from '../woo-ai-assistant/chat-modal';
import useSetAndStoreMessages from './useSetAndStoreMessages';

interface UseWooAssistantApiCallProps {
	setAndStorethreadID: ( threadID: string ) => void;
	generateInformationalMessage: ( message: string ) => void;
	messages: Message[];
	setMessages: React.Dispatch< React.SetStateAction< Message[] > >;
	setLoading: React.Dispatch< React.SetStateAction< boolean > >;
	handleError: ( message: string ) => void;
	isResponseError: boolean;
	threadID: string;
}

const useWooAssistantApiCall = ( {
	setAndStorethreadID,
	generateInformationalMessage,
	setMessages,
	setLoading,
	handleError,
	isResponseError,
	threadID,
}: UseWooAssistantApiCallProps ) => {
	const handleRequiresAction = useHandleRequiresAction( {
		setMessages,
		setLoading,
		generateInformationalMessage,
		handleError,
		isResponseError,
	} );
	const setAndStoreMessages = useSetAndStoreMessages( { setMessages } );

	const makeWooAssistantApiCall = useCallback(
		async ( userQuery: string ) => {
			recordWooAIAssistantTracks( 'send_message', {
				message: userQuery,
			} );
			setLoading( true );

			try {
				const { token } = await requestJetpackToken();
				const formData = prepareFormData( userQuery, token );
				const response = ( await apiFetch( {
					url: 'https://public-api.wordpress.com/wpcom/v2/woo-wizard',
					method: 'POST',
					body: formData,
				} ) ) as any;

				let answer = '';
				const newthreadID = response.thread_id;
				if ( ! threadID && newthreadID ) {
					setAndStorethreadID( newthreadID );
				}

				if ( response.status === 'requires_action' ) {
					answer = await handleRequiresAction(
						response,
						token,
						newthreadID,
						userQuery
					);
				} else {
					answer = response.answer;
				}

				if ( ! answer || ! answer.length ) {
					throw new Error( 'No message returned from assistant' );
				}

				setAndStoreMessages( answer, 'assistant' );
			} catch ( error: any ) {
				handleError( error.message || 'An error occurred' );
			} finally {
				setLoading( false );
			}
		},
		[
			setAndStoreMessages,
			setAndStorethreadID,
			handleRequiresAction,
			handleError,
			setLoading,
			threadID,
		]
	);

	return { makeWooAssistantApiCall };
};

export default useWooAssistantApiCall;
