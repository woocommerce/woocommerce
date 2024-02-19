/**
 * External dependencies
 */
import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import apiFetch from '@wordpress/api-fetch';
/**
 * Internal dependencies
 */
import ChatInputForm from './chat-input-form';

jest.mock( '@wordpress/api-fetch', () => ( {
	__esModule: true,
	default: jest.fn( () => Promise.resolve( {} ) ), // Default mock behavior
} ) );
const mockHandleError = jest.fn();

describe( 'ChatInputForm', () => {
	afterEach( () => {
		jest.clearAllMocks();
		apiFetch.mockReset();
	} );
	it( 'does not call handleSubmit when input is empty', async () => {
		const mockOnSubmit = jest.fn();
		render(
			<ChatInputForm
				onSubmit={ mockOnSubmit }
				isLoading={ false }
				handleError={ mockHandleError }
			/>
		);

		await userEvent.click( screen.getByText( 'Send' ) );
		expect( mockOnSubmit ).not.toHaveBeenCalled();
	} );

	it( 'calls handleSubmit with correct arguments for non-empty input', async () => {
		const mockOnSubmit = jest.fn();
		const textToType = 'Example question.';
		render(
			<ChatInputForm
				onSubmit={ mockOnSubmit }
				isLoading={ false }
				handleError={ mockHandleError }
			/>
		);
		const textInput = screen.getByPlaceholderText( /Type your message/i );
		await userEvent.type( textInput, textToType );
		expect( textInput ).toHaveValue( textToType );
		// Test submission via enter key press.
		await userEvent.type( textInput, '{Enter}' );
		expect( mockOnSubmit ).toHaveBeenCalledWith( textToType );
		// Test that the input field is cleared after submission.
		expect( textInput ).toHaveValue( '' );
	} );

	it( 'displays an error if microphone access fails', async () => {
		navigator.mediaDevices.getUserMedia.mockRejectedValue(
			new Error( 'Access Denied' )
		);

		const mockOnSubmit = jest.fn();
		render(
			<ChatInputForm
				onSubmit={ mockOnSubmit }
				isLoading={ false }
				handleError={ mockHandleError }
			/>
		);

		const button = screen.getByLabelText( 'Start recording' );
		await waitFor( () => {
			expect( button ).toBeInTheDocument();
		} );
		await userEvent.click( button );

		await waitFor( () => {
			expect( mockHandleError ).toHaveBeenCalledWith(
				'Error accessing microphone.'
			);
		} );
	} );
	it( 'fetches transcription when recording is complete', async () => {
		const mockOnSubmit = jest.fn();
		const mockTranscriptionResponse = 'test transcription';
		jest.mock( '@wordpress/api-fetch', () =>
			jest.fn(
				() => Promise.resolve( mockTranscriptionResponse ) // Directly return the expected string
			)
		);

		render(
			<ChatInputForm
				onSubmit={ mockOnSubmit }
				isLoading={ false }
				handleError={ mockHandleError }
			/>
		);
		const textInput = screen.getByPlaceholderText( /Type your message/i );
		const startRecordingButton = await screen.findByLabelText(
			/start recording/i
		);
		expect( startRecordingButton ).toBeInTheDocument();

		await userEvent.click( startRecordingButton );

		const stopRecordingButton = await screen.findByLabelText(
			/stop recording/i
		);
		// wait for state to update before clicking stop
		expect( stopRecordingButton ).toBeInTheDocument();
		await userEvent.click( stopRecordingButton );

		await waitFor(
			() => {
				expect( apiFetch ).toHaveBeenCalledWith(
					expect.objectContaining( {
						url: expect.stringContaining(
							'woo-wizard/speech-to-text'
						),
						method: 'POST',
						body: expect.any( FormData ),
					} )
				);
			},
			{ timeout: 1000 }
		);

		await waitFor( () =>
			expect( textInput.value ).toBe( mockTranscriptionResponse )
		);
	} );
	it( 'does not fetch transcription if audioBlob is empty', async () => {
		const mockOnSubmit = jest.fn();
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

		render(
			<ChatInputForm
				onSubmit={ mockOnSubmit }
				isLoading={ false }
				handleError={ mockHandleError }
			/>
		);

		await userEvent.click( screen.getByLabelText( /start recording/i ) );
		// wait for state to update before clicking stop
		await waitFor( () => {
			expect(
				screen.getByLabelText( /stop recording/i )
			).toBeInTheDocument();
		} );
		await userEvent.click( screen.getByLabelText( /stop recording/i ) );

		await waitFor( () => {
			expect( apiFetch ).not.toHaveBeenCalled();
		} );
	} );
} );
