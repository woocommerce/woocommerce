/**
 * External dependencies
 */
import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';
/**
 * Internal dependencies
 */
import ChatInputForm from './chat-input-form';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
const mockHandleError = jest.fn();

describe( 'ChatInputForm', () => {
	afterEach( () => {
		jest.clearAllMocks();
		apiFetch.mockReset();
	} );
	it( 'does not call handleSubmit when input is empty', () => {
		const mockOnSubmit = jest.fn();
		render(
			<ChatInputForm
				onSubmit={ mockOnSubmit }
				isLoading={ false }
				handleError={ mockHandleError }
			/>
		);

		fireEvent.click( screen.getByText( 'Send' ) );
		expect( mockOnSubmit ).not.toHaveBeenCalled();
	} );

	it( 'calls handleSubmit with correct arguments for non-empty input', () => {
		const mockOnSubmit = jest.fn();
		render(
			<ChatInputForm
				onSubmit={ mockOnSubmit }
				isLoading={ false }
				handleError={ mockHandleError }
			/>
		);

		fireEvent.change( screen.getByPlaceholderText( /Type your message/ ), {
			target: { value: 'Example question' },
		} );
		fireEvent.click( screen.getByText( 'Send' ) );
		expect( mockOnSubmit ).toHaveBeenCalledWith( 'Example question' );
	} );
	it( 'clears input after handleSubmit is called', () => {
		const mockOnSubmit = jest.fn();
		render(
			<ChatInputForm
				onSubmit={ mockOnSubmit }
				isLoading={ false }
				handleError={ mockHandleError }
			/>
		);
		const input = screen.getByPlaceholderText( /Type your message/ );

		fireEvent.change( input, {
			target: { value: 'Example question' },
		} );
		fireEvent.click( screen.getByText( 'Send' ) );
		expect( input ).toHaveValue( '' );
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
		fireEvent.click( button );

		await waitFor( () => {
			expect( mockHandleError ).toHaveBeenCalledWith(
				'Error accessing microphone.'
			);
		} );
	} );
	it( 'fetches transcription when recording is complete', async () => {
		const mockOnSubmit = jest.fn();

		const mockTranscriptionResponse = 'test transcription';
		apiFetch.mockResolvedValueOnce( { mockTranscriptionResponse } );

		render(
			<ChatInputForm
				onSubmit={ mockOnSubmit }
				isLoading={ false }
				handleError={ mockHandleError }
			/>
		);

		fireEvent.click( screen.getByLabelText( /start recording/i ) );
		// wait for state to update before clicking stop
		await waitFor( () => {
			expect(
				screen.getByLabelText( /stop recording/i )
			).toBeInTheDocument();
		} );
		fireEvent.click( screen.getByLabelText( /stop recording/i ) );

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

		await waitFor( () => {
			expect(
				screen.getByDisplayValue( mockTranscriptionResponse )
			).toBeInTheDocument();
		} );
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

		fireEvent.click( screen.getByLabelText( /start recording/i ) );
		// wait for state to update before clicking stop
		await waitFor( () => {
			expect(
				screen.getByLabelText( /stop recording/i )
			).toBeInTheDocument();
		} );
		fireEvent.click( screen.getByLabelText( /stop recording/i ) );

		await waitFor( () => {
			expect( apiFetch ).not.toHaveBeenCalled();
		} );
	} );
} );
