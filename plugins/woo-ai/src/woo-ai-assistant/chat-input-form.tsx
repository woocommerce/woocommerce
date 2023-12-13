/**
 * External dependencies
 */

import { Button, TextareaControl } from '@wordpress/components';
import React, { useState } from 'react';

/**
 * Internal dependencies
 */
import { AudioRecorder } from '../components/audio-recorder';

type ChatInputFormProps = {
	onSubmit: ( input: string ) => void;
	isLoading: boolean;
	setAudioBlob: ( audioBlob: Blob ) => void;
};

const ChatInputForm = ( {
	onSubmit,
	isLoading,
	setAudioBlob,
}: ChatInputFormProps ) => {
	const [ input, setInput ] = useState( '' );
	const handleSubmit = ( event: React.FormEvent ) => {
		event.preventDefault();
		onSubmit( input );
		setInput( '' );
	};
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
	);
};

export default ChatInputForm;
