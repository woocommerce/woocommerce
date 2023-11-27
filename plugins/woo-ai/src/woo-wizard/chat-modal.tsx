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
import makeWCRestApiCall from '../utils/wcRestApi';
import { AudioRecorder } from '../components/audio-recorder';

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

	const [ audioBlob, setAudioBlob ] = useState< Blob | null >( null );

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
			const formData = prepareFormData( input, token, audioBlob );
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

						const formData = prepareFormData(
							input,
							token,
							audioBlob
						);
						formData.append( 'run_id', runID );
						formData.append( 'tool_call_id', functionID );
						formData.append( 'output', message );

						/* 
                        Update the thread with the result of the function call. Otherwise, the run is stuck at 'pending'.
                        That would prevent us from appending new messages to the thread.
                        */
						await apiFetch( {
							url: 'https://public-api.wordpress.com/wpcom/v2/woo-wizard/submit-tool-output',
							method: 'POST',
							body: formData,
						} );

						const summaryPrompt = `Provide a helpful answer for the original query using the resulting data from the API request. The original query was "${ input }". Parse through the data and find the most relevant information to answer the query and provide it in a human-readable format. The data from the result of the API request is: ${ JSON.stringify(
							message
						) }`;

						const summaryFormData = prepareFormData(
							input,
							token,
							null
						);

						const summaryResponse = ( await apiFetch( {
							url: 'https://public-api.wordpress.com/wpcom/v2/woo-wizard',
							method: 'POST',
							body: summaryFormData,
						} ) ) as any;
						message = summaryResponse.answer;
					} catch ( error ) {
						message =
							"I'm sorry, I had trouble performing this task for you.";
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

			const { answer } = response;
			if ( ! answer || ! answer.length ) {
				throw new Error( 'No message returned from assistant' );
			}
			const assistantMessage: Message = {
				sender: 'assistant',
				text: answer,
			};

			setMessages( ( messages ) => [ ...messages, assistantMessage ] );
		} catch ( error ) {
			setMessages( ( messages ) => [
				...messages,
				{
					sender: 'assistant',
					text: "I'm sorry, I had trouble generating a response for you.",
				},
			] );
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
