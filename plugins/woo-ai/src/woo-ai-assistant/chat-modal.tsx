/**
 * External dependencies
 */
import React, { useEffect, useRef, useState } from 'react';
import { Button, Modal, TextareaControl } from '@wordpress/components';
import { useDispatch, select } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';
import apiFetch from '@wordpress/api-fetch';
import { __experimentalRequestJetpackToken as requestJetpackToken } from '@woocommerce/ai';
import { useAutoAnimate } from '@formkit/auto-animate/react';

/**
 * Internal dependencies
 */
import './index.scss';
import makeWCRestApiCall from '../utils/wcRestApi';
import { AudioRecorder } from '../components/audio-recorder';
import MessageItem from './message-item';
import recordWooAIAssistantTracks from './utils';

export type Message = {
	sender: 'user' | 'assistant';
	text: string;
};

type ChatModalProps = {
	onClose: () => void;
};

const WOO_AI_PLUGIN_NAME = 'woo-ai-plugin';
const preferencesThreadID = 'assistant-thread-id';
const preferencesChatHistory = 'woo-ai-chat-history';

const ChatModal: React.FC< ChatModalProps > = ( { onClose } ) => {
	const [ input, setInput ] = useState( '' );
	const [ messages, setMessages ] = useState< Message[] >( [] );
	const [ isLoading, setLoading ] = useState( false );
	const [ threadID, setThreadID ] = useState< string >( '' );
	const [ isResponseError, setIsResponseError ] = useState( false );
	const [ audioBlob, setAudioBlob ] = useState< Blob | null >( null );
	const { set: setStorageData } = useDispatch( preferencesStore );
	const startSessionTimeRef = useRef< number >( Date.now() );

	const [ parent ] = useAutoAnimate();
	const chatMessagesEndRef = React.useRef< HTMLLIElement | null >( null );

	// Calculate the session duration based on the component mounting and unmounting (opening and closing modal)
	useEffect( () => {
		return () => {
			const sessionDurationInSeconds = Math.floor(
				( Date.now() - startSessionTimeRef.current ) / 1000
			);
			if (
				startSessionTimeRef?.current &&
				sessionDurationInSeconds > 0
			) {
				recordWooAIAssistantTracks( 'session_duration', {
					session_duration_in_seconds: sessionDurationInSeconds,
				} );
			}
			startSessionTimeRef.current = 0;
		};
	}, [] );

	useEffect( () => {
		// @todo: auto-focus the input field when the component mounts.
	} );

	useEffect( () => {
		recordWooAIAssistantTracks( 'open' );
	}, [] );

	useEffect( () => {
		if ( chatMessagesEndRef && chatMessagesEndRef.current ) {
			const element = chatMessagesEndRef.current;
			element.scrollIntoView( { behavior: 'smooth' } );
		}
	}, [ chatMessagesEndRef, messages ] );

	useEffect( () => {
		if ( ! threadID ) {
			const storedthreadID = select( preferencesStore ).get(
				WOO_AI_PLUGIN_NAME,
				preferencesThreadID
			);
			setThreadID( storedthreadID );
		}
	}, [ threadID, setStorageData ] );

	useEffect( () => {
		if ( ! messages || ! messages.length ) {
			const storedMessages = select( preferencesStore ).get(
				WOO_AI_PLUGIN_NAME,
				preferencesChatHistory
			);
			// check that each message text property is a string, otherwise we'll get an error when rendering
			// unset the message in question if it's not a string
			if ( storedMessages ) {
				const parsedMessages = JSON.parse( storedMessages );
				const filteredMessages = parsedMessages.filter(
					( message: Message ) => typeof message.text === 'string'
				);
				setMessages( filteredMessages );
			}
		}
	}, [ messages ] );

	const prepareFormData = (
		message: string,
		token: string,
		tempthreadID?: string
	) => {
		const formData = new FormData();
		formData.append( 'message', message );
		formData.append( 'token', token );
		if ( tempthreadID ) {
			formData.append( 'thread_id', tempthreadID );
		}
		if ( audioBlob ) {
			// @todo: maybe grab the extension from the blob mime type?
			formData.append( 'audio_file', audioBlob, 'audio.wav' );
		}
		return formData;
	};

	const handleError = ( message: string ) => {
		setIsResponseError( true );
		setMessages( ( prevMessages ) => [
			...prevMessages,
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
	const handleRequiresAction = async (
		response: any,
		token: string,
		tempthreadID: string
	) => {
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
				// Make an API call to update the chat with the result of the function call
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
				const formData = prepareFormData( input, token, tempthreadID );
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
				setMessages( ( prevMessages ) => [
					...prevMessages,
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

				const summaryFormData = prepareFormData( summaryPrompt, token );

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

	const setAndStorethreadID = ( newthreadID: string ) => {
		setStorageData( WOO_AI_PLUGIN_NAME, preferencesThreadID, newthreadID );
		setThreadID( newthreadID );
	};

	const setAndStoreMessages = (
		answer: string,
		sender: 'assistant' | 'user'
	) => {
		const chatMessage: Message = {
			sender,
			text: answer,
		};
		if ( typeof answer !== 'string' ) {
			return;
		}
		setMessages( ( prevMessages ) => [ ...prevMessages, chatMessage ] );
		setStorageData(
			WOO_AI_PLUGIN_NAME,
			preferencesChatHistory,
			JSON.stringify( [ ...messages, chatMessage ] )
		);
	};

	const makeWooAssistantApiCall = async ( userQuery: string ) => {
		recordWooAIAssistantTracks( 'send_message', {
			message: userQuery,
		} );
		let answer = '';
		setLoading( true );

		try {
			const { token } = await requestJetpackToken();
			const formData = prepareFormData( userQuery, token );
			const response = ( await apiFetch( {
				url: 'https://public-api.wordpress.com/wpcom/v2/woo-wizard',
				method: 'POST',
				body: formData,
			} ) ) as any;
			const newthreadID = response.thread_id;
			const tempthreadID = newthreadID || threadID;
			if ( ! threadID && newthreadID ) {
				setAndStorethreadID( newthreadID );
			}

			if ( response.status === 'requires_action' ) {
				const actionsAnswer = await handleRequiresAction(
					response,
					token,
					tempthreadID
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
		} finally {
			setLoading( false );
		}
	};

	const retryUserQuery = async ( index: number ) => {
		const userQuery = messages[ index ];
		setAndStoreMessages( userQuery.text, 'user' );
		const retryUserQueryPrompt = `Your previous answer to the following request was unsatisfactory, please try again: ${ userQuery.text }`;
		if ( userQuery.sender !== 'user' ) {
			return;
		}

		try {
			makeWooAssistantApiCall( retryUserQueryPrompt );
		} catch ( error ) {
			handleError( 'I had trouble generating a response for you.' );
		} finally {
			recordWooAIAssistantTracks( 'send_message_retry', {
				message: userQuery.text,
			} );
		}
	};

	const handleSubmit = async ( event: React.FormEvent ) => {
		event.preventDefault();
		if ( ! input.trim() && ! audioBlob ) {
			return;
		}
		try {
			setAndStoreMessages( input, 'user' );
			setInput( '' );
			makeWooAssistantApiCall( input );
		} catch ( error ) {
			handleError(
				"I'm sorry, I had trouble generating a response for you."
			);
		}
	};

	return (
		<Modal
			title="Woo Wizard Assistant"
			onRequestClose={ onClose }
			className="woo-ai-assistant-chat-modal"
		>
			<ul className="woo-chat-history" ref={ parent }>
				{ ! messages.length && (
					<li>
						<p className="message assistant">
							Hi there! How can I help you today?
						</p>
					</li>
				) }
				{ messages.map( ( message, index ) => (
					<MessageItem
						message={ message }
						key={ index }
						index={ index }
						onRetry={ retryUserQuery }
					/>
				) ) }
				<li ref={ chatMessagesEndRef }></li>
			</ul>
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
					className="woo-ai-assistant-submit-button"
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
