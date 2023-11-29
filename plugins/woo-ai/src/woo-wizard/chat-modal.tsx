/**
 * External dependencies
 */
import React, { useEffect, useState } from 'react';
import { Modal, Button, TextareaControl } from '@wordpress/components';
import { useDispatch, select } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';
import apiFetch from '@wordpress/api-fetch';
import { __experimentalRequestJetpackToken as requestJetpackToken } from '@woocommerce/ai';

/**
 * Internal dependencies
 */
import './index.scss';
import makeWCRestApiCall from '../utils/wcRestApi';
import { AudioRecorder } from '../components/audio-recorder';

type Message = {
	sender: 'user' | 'assistant';
	text: string;
};

type ChatModalProps = {
	onClose: () => void;
};

const ONE_HOUR_IN_MS = 3600000;
const WOO_AI_PLUGIN_NAME = 'woo-ai-plugin';
const threadPreferenceId = 'assistant-chat-thread-id';
const threadExpirationPreferenceId = 'assistant-chat-thread-id-expiration';
const chatHistoryPreferenceId = 'woo-ai-chat-history';

const ChatModal: React.FC< ChatModalProps > = ( { onClose } ) => {
	const [ input, setInput ] = useState( '' );
	const [ messages, setMessages ] = useState< Message[] >( [] );
	const [ isLoading, setLoading ] = useState( false );
	const [ threadID, setThreadID ] = useState< string >( '' );
	const [ isResponseError, setIsResponseError ] = useState( false );
	const [ audioBlob, setAudioBlob ] = useState< Blob | null >( null );
	const { set: setStorageData } = useDispatch( preferencesStore );

	useEffect( () => {
		if ( ! threadID ) {
			const storedThreadId = select( preferencesStore ).get(
				WOO_AI_PLUGIN_NAME,
				threadPreferenceId
			);
			// check that the thread hasn't expired
			const expiration = select( preferencesStore ).get(
				WOO_AI_PLUGIN_NAME,
				threadExpirationPreferenceId
			);
			if ( expiration && Date.now() > expiration ) {
				setStorageData( WOO_AI_PLUGIN_NAME, threadPreferenceId, '' );
				setStorageData(
					WOO_AI_PLUGIN_NAME,
					threadExpirationPreferenceId,
					''
				);
			}
			setThreadID( storedThreadId );
		}
	}, [ threadID, setStorageData ] );

	useEffect( () => {
		if ( ! messages || ! messages.length ) {
			const storedMessages = select( preferencesStore ).get(
				WOO_AI_PLUGIN_NAME,
				chatHistoryPreferenceId
			);
			if ( storedMessages ) {
				setMessages( JSON.parse( storedMessages ) );
			} else {
				const assistantMessage: Message = {
					sender: 'assistant',
					text: 'Hi there! How can I help you today?',
				};
				setMessages( [ assistantMessage ] );
			}
		}
	}, [ messages ] );

	const prepareFormData = (
		input: string,
		token: string,
		audioBlob: Blob | null
	) => {
		const formData = new FormData();
		formData.append( 'message', input );
		formData.append( 'token', token );
		if ( threadID ) {
			formData.append( 'thread_id', threadID );
		}
		if ( audioBlob ) {
			// @todo: maybe grab the extension from the blob mime type?
			formData.append( 'audio_file', audioBlob, 'audio.wav' );
		}
		return formData;
	};

	const handleError = ( message: string ) => {
		setIsResponseError( true );
		setMessages( ( messages ) => [
			...messages,
			{
				sender: 'assistant',
				text: message,
			},
		] );
	};

	/**
	 *
	 * @param {any}    response The response from the assistant API.
	 * @param {string} token    The Jetpack token.
	 * @return {string} The message to display to the user.
	 */
	const handleRequiresAction = async ( response: any, token: string ) => {
		const answer = response.answer;
		const functionID: string = answer.function_id;
		const runID: string = response.run_id;
		let message = '';
		if ( answer.function_name === 'makeWCRestApiCall' ) {
			const functionArguments = answer.function_args;
			try {
				const responseBody = ( await makeWCRestApiCall(
					functionArguments
				) ) as string;
				message = responseBody;
				// Make an API call to update the thread with the result of the function call
			} catch ( error ) {
				handleError(
					"I'm sorry, I had trouble performing this task for you."
				);
			}

			/* 
                    Update the thread with the result of the function call. Otherwise, the run is stuck at 'pending'.
                    That would prevent us from appending new messages to the thread.
                    We're doing this even if the function call failed, because we want to be able to continue the conversation.
                */
			try {
				const formData = prepareFormData( input, token, audioBlob );
				formData.append( 'run_id', runID );
				formData.append( 'tool_call_id', functionID );
				formData.append( 'output', message );

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

			// At this point, if there's an error, we don't continue with a summary.
			if ( isResponseError ) {
				setMessages( ( messages ) => [
					...messages,
					{
						sender: 'assistant',
						text: message,
					},
				] );
				setLoading( false );
				return;
			}
			try {
				const summaryPrompt = `Provide a helpful answer for the original query using the resulting data from the API request. The original query was "${ input }". Parse through the data and find the most relevant information to answer the query and provide it in a human-readable format. The data from the result of the API request is: ${ JSON.stringify(
					message
				) }`;

				const summaryFormData = prepareFormData(
					summaryPrompt,
					token,
					null
				);

				const summaryResponse = ( await apiFetch( {
					url: 'https://public-api.wordpress.com/wpcom/v2/woo-wizard',
					method: 'POST',
					body: summaryFormData,
				} ) ) as any;
				message = summaryResponse.answer as string;
			} catch ( error ) {
				handleError(
					"I believe that I was able to accomplish the task you requested, but I'm having trouble summarizing the results."
				);
			}
		}
		return message;
	};

	const setAndStoreThreadID = ( newThreadID: string ) => {
		setThreadID( newThreadID );
		setStorageData( WOO_AI_PLUGIN_NAME, threadPreferenceId, newThreadID );
		// Set the expiry to 1 hour from now since threads expire after 1 hour.
		setStorageData(
			WOO_AI_PLUGIN_NAME,
			threadExpirationPreferenceId,
			Date.now() + ONE_HOUR_IN_MS
		);
	};

	const setAndStoreMessages = (
		answer: string,
		sender: 'assistant' | 'user'
	) => {
		const assistantMessage: Message = {
			sender,
			text: answer,
		};
		setMessages( ( messages ) => [ ...messages, assistantMessage ] );
		setStorageData(
			WOO_AI_PLUGIN_NAME,
			chatHistoryPreferenceId,
			JSON.stringify( [ ...messages, assistantMessage ] )
		);
	};

	const handleSubmit = async ( event: React.FormEvent ) => {
		event.preventDefault();
		if ( ! input.trim() && ! audioBlob ) {
			return;
		}
		setLoading( true );

		setAndStoreMessages( input, 'user' );
		setInput( '' );

		try {
			const { token } = await requestJetpackToken();
			const formData = prepareFormData( input, token, audioBlob );
			const response = ( await apiFetch( {
				url: 'https://public-api.wordpress.com/wpcom/v2/woo-wizard',
				method: 'POST',
				body: formData,
			} ) ) as any;
			const newThreadID = response.thread_id;
			if ( ! threadID && newThreadID ) {
				setAndStoreThreadID( newThreadID );
			}

			let answer: string;
			if ( response.status === 'requires_action' ) {
				const actionsAnswer = await handleRequiresAction(
					response,
					token
				);
				answer = actionsAnswer || '';
			} else {
				answer = response.answer;
			}
			if ( ! answer || ! answer.length ) {
				throw new Error( 'No message returned from assistant' );
			}
			setAndStoreMessages( answer, 'assistant' );
		} catch ( error ) {
			handleError(
				"I'm sorry, I had trouble generating a response for you."
			);
		}
		setLoading( false );
	};

	return (
		<Modal
			title="Woo Wizard Assistant"
			onRequestClose={ onClose }
			className="woo-wizard-chat-modal"
		>
			<div className="woo-chat-history">
				{ messages.map( ( message, index ) => (
					<div
						key={ index }
						className={ `message ${ message.sender }` }
					>
						{ message.text }
					</div>
				) ) }
			</div>
			<form onSubmit={ handleSubmit } className="chat-form">
				<TextareaControl
					value={ input }
					onChange={ setInput }
					placeholder="Type your message..."
					rows={ 2 }
					style={ { flexGrow: 1 } }
				/>
				<AudioRecorder
					onRecordingComplete={ ( audioBlob: Blob ) =>
						setAudioBlob( audioBlob )
					}
				/>
				<Button
					className="woo-wizard-submit-button"
					disabled={ isLoading }
					isBusy={ isLoading }
					type="submit"
					size="default"
					variant="primary"
				>
					Send
				</Button>
			</form>
		</Modal>
	);
};

export default ChatModal;
