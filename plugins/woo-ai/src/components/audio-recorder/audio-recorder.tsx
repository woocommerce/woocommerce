/**
 * External dependencies
 */
import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Button, Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import playerStop from './player-stop';
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
	const [ isRecording, setIsRecording ] = useState( false );
	const mediaRecorderRef = useRef< MediaRecorder | null >( null );
	const [ audioBlob, setAudioBlob ] = useState< Blob | null >( null );

	const handleDataAvailable = useCallback(
		( e: BlobEvent ) => {
			if ( e.data.size > 0 ) {
				setAudioBlob( e.data );
			}
		},
		[ setAudioBlob ]
	);

	useEffect( () => {
		if ( audioBlob && ! isRecording ) {
			onRecordingComplete( audioBlob );
		}
	}, [ audioBlob, isRecording, onRecordingComplete ] );

	const startRecording = async () => {
		try {
			const stream = await navigator.mediaDevices.getUserMedia( {
				audio: true,
			} );
			const mediaRecorder = new MediaRecorder( stream );
			mediaRecorderRef.current = mediaRecorder;

			mediaRecorder.start();
			setIsRecording( true );
			mediaRecorder.addEventListener(
				'dataavailable',
				handleDataAvailable
			);
		} catch ( err ) {
			handleError( 'Error accessing microphone:' );
		}
	};

	const stopRecording = () => {
		if ( mediaRecorderRef.current?.state === 'recording' ) {
			mediaRecorderRef.current.stop();
			setIsRecording( false );
		}
	};

	useEffect( () => {
		return () => {
			if ( mediaRecorderRef.current ) {
				mediaRecorderRef.current.removeEventListener(
					'dataavailable',
					handleDataAvailable
				);
			}
		};
	}, [ handleDataAvailable ] );

	if ( isTranscribing && ! isRecording ) {
		return (
			<div className="woo-ai-assistant-mic-button-spinner">
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
		></Button>
	);
};
