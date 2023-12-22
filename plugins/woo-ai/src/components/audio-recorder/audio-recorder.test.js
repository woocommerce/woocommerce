/**
 * External dependencies
 */
import React from 'react';
import { render, fireEvent, screen, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { AudioRecorder } from './audio-recorder';

// Mock navigator.mediaDevices.getUserMedia
global.navigator.mediaDevices = {
	getUserMedia: jest.fn().mockResolvedValue( {} ),
};

global.MediaRecorder = function ( stream ) {
	this.stream = stream;
	this.state = 'inactive'; // Initial state
	this.start = jest.fn( () => {
		this.state = 'recording'; // Change state to recording when start is called
	} );
	this.stop = jest.fn( () => {
		this.state = 'inactive'; // Change state back to inactive when stop is called
	} );
	this.addEventListener = jest.fn( ( event, handler ) => {
		if ( event === 'dataavailable' ) {
			setTimeout(
				() =>
					handler( {
						data: new Blob( [ 'test data' ], {
							type: 'audio/wav',
						} ),
					} ),
				0
			);
		}
	} );
	this.removeEventListener = jest.fn();
};

describe( 'AudioRecorder', () => {
	it( 'changes to stop recording on click', async () => {
		const mockOnRecordingComplete = jest.fn();
		render(
			<AudioRecorder
				onRecordingComplete={ mockOnRecordingComplete }
				isTranscribing={ false }
				handleError={ () => {} }
			/>
		);

		const startButton = screen.getByLabelText( 'Start recording' );
		fireEvent.click( startButton );
		await waitFor( () => {
			expect(
				screen.getByLabelText( 'Stop recording' )
			).toBeInTheDocument();
		} );

		// Click the button after the state has updated
		const stopButton = screen.getByLabelText( 'Stop recording' );
		fireEvent.click( stopButton );

		await waitFor( () => {
			expect( mockOnRecordingComplete ).toHaveBeenCalled();
		} );
	} );

	it( 'does not call onRecordingComplete if the audioBlob is empty', async () => {
		const mockOnRecordingComplete = jest.fn();
		render(
			<AudioRecorder
				onRecordingComplete={ mockOnRecordingComplete }
				isTranscribing={ false }
				handleError={ () => {} }
			/>
		);

		// Mock the dataavailable event to return an empty blob
		global.MediaRecorder = function ( stream ) {
			this.stream = stream;
			this.state = 'inactive'; // Initial state
			this.start = jest.fn( () => {
				this.state = 'recording'; // Change state to recording when start is called
			} );
			this.stop = jest.fn( () => {
				this.state = 'inactive'; // Change state back to inactive when stop is called
			} );
			this.addEventListener = jest.fn( ( event, handler ) => {
				if ( event === 'dataavailable' ) {
					setTimeout(
						() =>
							handler( {
								data: new Blob( [], {
									type: 'audio/wav',
								} ),
							} ),
						0
					);
				}
			} );
			this.removeEventListener = jest.fn();
		};

		const startButton = screen.getByLabelText( 'Start recording' );
		fireEvent.click( startButton );
		await waitFor( () => {
			expect(
				screen.getByLabelText( 'Stop recording' )
			).toBeInTheDocument();
		} );

		// Click the button after the state has updated
		const stopButton = screen.getByLabelText( 'Stop recording' );
		fireEvent.click( stopButton );

		await waitFor( () => {
			expect( mockOnRecordingComplete ).not.toHaveBeenCalled();
		} );
	} );

	it( 'displays an error if microphone access fails', async () => {
		const mockHandleError = jest.fn();
		navigator.mediaDevices.getUserMedia.mockRejectedValue(
			new Error( 'Access Denied' )
		);

		render(
			<AudioRecorder
				onRecordingComplete={ () => {} }
				isTranscribing={ false }
				handleError={ mockHandleError }
			/>
		);

		const button = screen.getByLabelText( 'Start recording' );
		fireEvent.click( button );

		await waitFor( () => {
			expect( mockHandleError ).toHaveBeenCalledWith(
				'Error accessing microphone.'
			);
		} );
	} );

	it( 'shows a spinner when transcribing', () => {
		render(
			<AudioRecorder
				onRecordingComplete={ () => {} }
				isTranscribing={ true }
				handleError={ () => {} }
			/>
		);
		expect( screen.getByLabelText( 'Transcribing' ) ).toBeInTheDocument();
	} );
} );
