/**
 * External dependencies
 */
import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import micIcon from './mic-icon';
import playerStop from './player-stop';

type AudioRecorderProps = {
	onRecordingComplete: ( audioBlob: Blob ) => void;
};

export const AudioRecorder: React.FC< AudioRecorderProps > = ( {
	onRecordingComplete,
} ) => {
	const [ isRecording, setIsRecording ] = useState( false );
	const mediaRecorderRef = useRef< MediaRecorder | null >( null );

	const handleDataAvailable = useCallback(
		( e: BlobEvent ) => {
			if ( e.data.size > 0 ) {
				onRecordingComplete( e.data );
			}
		},
		[ onRecordingComplete ]
	);

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
			console.error( 'Error accessing microphone:', err );
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

	return (
		<Button
			className="woo-ai-assistant-mic-button"
			icon={ isRecording ? playerStop : micIcon }
			iconSize={ 32 }
			onClick={ isRecording ? stopRecording : startRecording }
			label={ isRecording ? 'Stop recording' : 'Start recording' }
		></Button>
	);
};
