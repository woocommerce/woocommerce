/**
 * External dependencies
 */
import { useState, useCallback, useRef, useEffect } from 'react';

type UseMediaRecorderProps = {
	onRecordingComplete: ( audioBlob: Blob ) => void;
	handleError: ( message: string ) => void;
};

const useMediaRecorder = ( {
	onRecordingComplete,
	handleError,
}: UseMediaRecorderProps ) => {
	const [ isRecording, setIsRecording ] = useState( false );
	const [ audioBlob, setAudioBlob ] = useState< Blob >( new Blob() );
	const mediaRecorderRef = useRef< MediaRecorder | null >( null );

	const handleDataAvailable = useCallback( ( e: BlobEvent ) => {
		if ( e.data.size > 0 ) {
			setAudioBlob( e.data );
		}
	}, [] );

	useEffect( () => {
		if ( audioBlob.size > 0 && ! isRecording ) {
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
			handleError( 'Error accessing microphone.' );
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

	return { isRecording, startRecording, stopRecording, audioBlob };
};

export default useMediaRecorder;
