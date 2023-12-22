/**
 * External dependencies
 */
import { Button, TextareaControl } from '@wordpress/components';
import React, { useEffect, useState } from 'react';

/**
 * Internal dependencies
 */
import { AudioRecorder } from '../components/audio-recorder';
import useMediaRecorder from '../hooks/useMediaRecorder';
import useTranscriptionFetcher from '../hooks/useTranscriptionFetcher';

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
	const { fetchTranscription, isTranscribing, transcription } =
		useTranscriptionFetcher( handleError );

	const { audioBlob } = useMediaRecorder( {
		onRecordingComplete: () => {},
		handleError,
	} );

	const handleSubmit = ( event: React.FormEvent ) => {
		event.preventDefault();
		if ( input.trim() ) {
			onSubmit( input );
			setInput( '' );
		}
	};

	useEffect( () => {
		if ( transcription && ! input && transcription.length > 0 ) {
			setInput( transcription );
		}
	}, [ input, setInput, transcription ] );

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
				onRecordingComplete={ () => fetchTranscription( audioBlob ) }
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
