/**
 * External dependencies
 */
import React, { useRef, useState } from 'react';
import { Modal, Button, TextareaControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __experimentalRequestJetpackToken as requestJetpackToken } from '@woocommerce/ai';

/**
 * Internal dependencies
 */
import './index.scss';
import micIcon from './mic-icon';
import playerStop from './player-stop';
import makeWCRestApiCall from '../utils/wcRestApi';

type Message = {
	sender: 'user' | 'assistant';
	text: string;
};

type ChatModalProps = {
	onClose: () => void;
};

const ChatModal: React.FC< ChatModalProps > = ( { onClose } ) => {
	const [ input, setInput ] = useState( '' );
	const [ messages, setMessages ] = useState< Message[] >( [] );
	const [ isLoading, setLoading ] = useState( false );
	// @todo: store threadID as localStorage to be retrieved on page load.
	// @todo: store the last 3 messages as localStorage to be retrieved on page load.
	const threadIDRef = useRef< string >( '' );
	let threadID = threadIDRef.current;

	const [ recording, setRecording ] = useState( false );
	const [ audioBlob, setAudioBlob ] = useState< Blob | null >( null );
	const mediaRecorderRef = useRef< MediaRecorder | null >( null );

	const startRecording = async () => {
		try {
			const stream = await navigator.mediaDevices.getUserMedia( {
				audio: true,
			} );
			const mediaRecorder = new MediaRecorder( stream );
			mediaRecorderRef.current = mediaRecorder;
			mediaRecorder.ondataavailable = ( e ) => {
				setAudioBlob( e.data );
			};
			mediaRecorder.start();
			setRecording( true );
		} catch ( err ) {
			console.error( 'Error accessing microphone:', err );
		}
	};

	const stopRecording = () => {
		mediaRecorderRef.current?.stop();
		setRecording( false );
		// Now `audioBlob` contains the recorded audio
	};

	const handleSubmit = async ( event: React.FormEvent ) => {
		event.preventDefault();
		if ( ! input.trim() && ! audioBlob ) {
			return;
		}
		setLoading( true );

		const userMessage: Message = { sender: 'user', text: input };
		setMessages( [ ...messages, userMessage ] );
		setInput( '' );

		try {
			const { token } = await requestJetpackToken();
			const formData = new FormData();
			formData.append( 'message', input );
			formData.append( 'token', token );
			if ( threadID ) {
				formData.append( 'thread_id', threadID );
			}

			// Append audio file if it exists
			if ( audioBlob ) {
				formData.append( 'audio_file', audioBlob, 'audio.wav' ); // Assuming 'audioBlob' is your recorded audio Blob
			}
			const response = ( await apiFetch( {
				url: 'https://public-api.wordpress.com/wpcom/v2/woo-wizard',
				method: 'POST',
				body: formData,
			} ) ) as any;
			if ( ! threadID && response.thread_id ) {
				threadID = response.thread_id;
			}

			if ( response.status === 'requires_action' ) {
				const answer = response.answer;
				const functionID: string = answer.function_id;
				const runID: string = response.run_id;
				if ( answer.function_name === 'makeWCRestApiCall' ) {
					const functionArguments = answer.function_args;
					let message = '';
					try {
						const responseBody = ( await makeWCRestApiCall(
							functionArguments
						) ) as string;
						message = responseBody;
						// Make an API call to update the thread with the result of the function call

						const formData = new FormData();
						formData.append( 'thread_id', threadID );
						formData.append( 'run_id', runID );
						formData.append( 'tool_call_id', functionID );
						formData.append( 'output', message );
						formData.append( 'token', token );

						const outputToolResponse = ( await apiFetch( {
							url: 'https://public-api.wordpress.com/wpcom/v2/woo-wizard/submit-tool-output',
							method: 'POST',
							body: formData,
						} ) ) as any;

						// @todo: handle outputToolResponse?

						const summaryPrompt = `Provide a helpful answer for the original query using the resulting data from the API request. The original query was "${ input }". Parse through the data and find the most relevant information to answer the query and provide it in a human-readable format. The data from the result of the API request is: ${ JSON.stringify(
							message
						) }`;

						const summaryFormData = new FormData();
						summaryFormData.append( 'message', summaryPrompt );
						summaryFormData.append( 'token', token );
						summaryFormData.append( 'thread_id', threadID );

						const summaryResponse = ( await apiFetch( {
							url: 'https://public-api.wordpress.com/wpcom/v2/woo-wizard',
							method: 'POST',
							body: summaryFormData,
						} ) ) as any;
						message = summaryResponse.answer;
					} catch ( error ) {
						message = 'I had trouble making the api call for you.';
					}

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
			}

			// Assuming the response contains the assistant's message
			const message = response.answer;
			if ( ! message || ! message.length ) {
				throw new Error( 'No message returned from assistant' );
			}
			const assistantMessage: Message = {
				sender: 'assistant',
				text: message,
			};

			setMessages( ( messages ) => [ ...messages, assistantMessage ] );
		} catch ( error ) {
			console.error( 'Error fetching response:', error );
			// Handle the error appropriately
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
				<Button
					className="woo-wizard-mic-button"
					icon={ recording ? playerStop : micIcon }
					iconSize={ 32 }
					onClick={ recording ? stopRecording : startRecording }
					label={ recording ? 'Stop recording' : 'Start recording' }
				></Button>
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
