/**
 * External dependencies
 */
import React from 'react';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { AudioRecorder } from './audio-recorder';

describe( 'AudioRecorder', () => {
	it( 'shows a spinner when transcribing', () => {
		render(
			<AudioRecorder
				isTranscribing={ true }
				onStartRecording={ () => {} }
				onStopRecording={ () => {} }
				isRecording={ false }
			/>
		);
		expect( screen.getByLabelText( /transcribing/i ) ).toBeInTheDocument();
	} );
} );
