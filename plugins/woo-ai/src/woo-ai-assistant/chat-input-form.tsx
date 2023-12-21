/**
 * External dependencies
 */

import { Button, TextareaControl } from '@wordpress/components';
import React, { useCallback, useEffect, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __experimentalRequestJetpackToken as requestJetpackToken } from '@woocommerce/ai';

/**
 * Internal dependencies
 */
import { AudioRecorder } from '../components/audio-recorder';

type ChatInputFormProps = {
	onSubmit: ( input: string ) => void;
	isLoading: boolean;
	handleError: ( message: string ) => void;
};

const ChatInputForm = ( {
	onSubmit,
	isLoading,
	handleError,
}: ChatInputFormProps ) => {
	const [ input, setInput ] = useState( '' );
	const [ isTranscribing, setIsTranscribing ] = useState( false );
	const [ audioBlob, setAudioBlob ] = useState< Blob | null >( null );
	const handleSubmit = ( event: React.FormEvent ) => {
		event.preventDefault();
		onSubmit( input );
		setInput( '' );
	};

	const decodeHtmlEntities = useCallback( ( text: string ) => {
		const textArea = document.createElement( 'textarea' );
		textArea.innerHTML = text;
		const decodedText = textArea.value;

		return decodedText;
	}, [] );
	// Send a request for speech-to-text conversion when the audioBlob changes.
	useEffect( () => {
		if ( ! audioBlob ) {
			return;
		}

		const fetchTranscription = async () => {
			try {
				setIsTranscribing( true );
				const { token } = await requestJetpackToken();
				const formData = new FormData();
				formData.append( 'audio_file', audioBlob );
				formData.append( 'token', token );

				const response = ( await apiFetch( {
					url: 'https://public-api.wordpress.com/wpcom/v2/woo-wizard/speech-to-text',
					method: 'POST',
					body: formData,
				} ) ) as string;

				if ( response && response ) {
					// Strip out HTML entities from the response.
					const strippedResponse = decodeHtmlEntities( response );

					setInput( strippedResponse );
				}
			} catch ( error ) {
				handleError( "I'm sorry, I had trouble processing the audio." );
			} finally {
				setIsTranscribing( false );
			}
		};

		fetchTranscription();
	}, [ audioBlob, decodeHtmlEntities, handleError ] );
	return (
		<form onSubmit={ handleSubmit } className="chat-form">
			<TextareaControl
				value={ input }
				onChange={ setInput }
				placeholder="Type your message..."
				rows={ 2 }
				style={ { flexGrow: 1 } }
			/>
			<AudioRecorder
				onRecordingComplete={ setAudioBlob }
				isTranscribing={ isTranscribing }
				handleError={ handleError }
			/>
			<Button
				className="woo-ai-assistant-submit-button"
				disabled={ isLoading }
				isBusy={ isLoading }
				type="submit"
				variant="primary"
			>
				Send
			</Button>
		</form>
	);
};

export default ChatInputForm;
