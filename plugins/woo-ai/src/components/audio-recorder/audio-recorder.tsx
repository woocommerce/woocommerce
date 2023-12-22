/**
 * External dependencies
 */
import React from 'react';
import { Button, Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import playerStop from './player-stop';
import useMediaRecorder from '../../hooks/useMediaRecorder';
import './index.scss';

type AudioRecorderProps = {
	onRecordingComplete: ( audioBlob: Blob ) => void;
	isTranscribing: boolean;
	handleError: ( message: string ) => void;
};

export const AudioRecorder: React.FC< AudioRecorderProps > = ( {
	onRecordingComplete,
	isTranscribing,
	handleError,
} ) => {
	const { isRecording, startRecording, stopRecording } = useMediaRecorder( {
		onRecordingComplete,
		handleError,
	} );

	if ( isTranscribing && ! isRecording ) {
		return (
			<div
				className="woo-ai-assistant-mic-button-spinner"
				aria-label="Transcribing"
			>
				<Spinner />
			</div>
		);
	}

	return (
		<Button
			className="woo-ai-assistant-mic-button"
			icon={ isRecording ? playerStop : 'microphone' }
			iconSize={ 32 }
			onClick={ isRecording ? stopRecording : startRecording }
			label={ isRecording ? 'Stop recording' : 'Start recording' }
		/>
	);
};

export default AudioRecorder;
