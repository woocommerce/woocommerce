/**
 * External dependencies
 */
import React from 'react';
import { Button, Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import playerStop from './player-stop';
import './index.scss';

type AudioRecorderProps = {
	isRecording: boolean;
	onStartRecording: () => void;
	onStopRecording: () => void;
	isTranscribing: boolean;
};

export const AudioRecorder: React.FC< AudioRecorderProps > = ( {
	isRecording,
	onStartRecording,
	onStopRecording,
	isTranscribing,
} ) => {
	return (
		<>
			{ isTranscribing && ! isRecording && (
				<div
					className="woo-ai-assistant-mic-button-spinner"
					aria-label="Transcribing"
				>
					<Spinner />
				</div>
			) }
			{ ! isTranscribing && (
				<Button
					className="woo-ai-assistant-mic-button"
					icon={ isRecording ? playerStop : 'microphone' }
					iconSize={ 32 }
					onClick={ isRecording ? onStopRecording : onStartRecording }
					label={ isRecording ? 'Stop recording' : 'Start recording' }
				/>
			) }
		</>
	);
};

export default AudioRecorder;
