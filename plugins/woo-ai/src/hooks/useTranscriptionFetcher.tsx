/**
 * External dependencies
 */
import { useState, useCallback } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __experimentalRequestJetpackToken as requestJetpackToken } from '@woocommerce/ai';

const useTranscriptionFetcher = (
	handleError: ( message: string ) => void
) => {
	const [ isTranscribing, setIsTranscribing ] = useState( false );
	const [ transcription, setTranscription ] = useState( '' );

	const decodeHtmlEntities = useCallback( ( text: string ) => {
		const textArea = document.createElement( 'textarea' );
		textArea.innerHTML = text;
		return textArea.value;
	}, [] );

	const clearTranscription = () => {
		setTranscription( '' );
	};

	const fetchTranscription = async ( audioBlob: Blob ) => {
		if ( ! audioBlob.size ) {
			return;
		}

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

			if ( response ) {
				const strippedResponse = decodeHtmlEntities( response );
				setTranscription( strippedResponse );
			}
		} catch ( error ) {
			handleError( "I'm sorry, I had trouble processing the audio." );
		} finally {
			setIsTranscribing( false );
		}
	};

	return {
		fetchTranscription,
		isTranscribing,
		transcription,
		clearTranscription,
	};
};

export default useTranscriptionFetcher;
