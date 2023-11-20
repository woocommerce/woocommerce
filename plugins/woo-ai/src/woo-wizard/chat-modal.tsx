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

interface Message {
	sender: 'user' | 'assistant';
	text: string;
}

interface ChatModalProps {
	onClose: () => void;
}

const ChatModal: React.FC< ChatModalProps > = ( { onClose } ) => {
	const [ input, setInput ] = useState( '' );
	const [ messages, setMessages ] = useState< Message[] >( [] );
	const [ isLoading, setLoading ] = useState( false );

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
			console.log( 'Started recording' );
			setRecording( true );
		} catch ( err ) {
			console.error( 'Error accessing microphone:', err );
		}
	};

	const stopRecording = () => {
		mediaRecorderRef.current?.stop();
		console.log( 'stopped recording' );
		setRecording( false );
		// Now `audioBlob` contains the recorded audio
	};

	const handleSubmit = async ( event: React.FormEvent ) => {
		console.log( 'handleSubmit' );
		event.preventDefault();
		console.log( audioBlob );
		if ( ! input.trim() && ! audioBlob ) {
			console.log( 'No input or audio blob' );
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

			// Append audio file if it exists
			if ( audioBlob ) {
				formData.append( 'audio_file', audioBlob, 'audio.wav' ); // Assuming 'audioBlob' is your recorded audio Blob
			}
			const response = ( await apiFetch( {
				url: 'https://public-api.wordpress.com/wpcom/v2/woo-wizard',
				method: 'POST',
				body: formData,
			} ) ) as any;

			// Assuming the response contains the assistant's message
			const messages = response.messages;
			if ( ! messages || ! messages.length ) {
				throw new Error( 'No messages returned from assistant' );
			}
			// create a new assistantMessage array
			const assistantMessages: Message[] = [];
			for ( const message of messages ) {
				const assistantMessage: Message = {
					sender: 'assistant',
					text: message.content,
				};
				assistantMessages.push( assistantMessage );
			}
			setMessages( ( messages ) => [
				...messages,
				...assistantMessages,
			] );
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
