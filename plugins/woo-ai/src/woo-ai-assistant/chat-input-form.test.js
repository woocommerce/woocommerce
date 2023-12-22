/**
 * External dependencies
 */
import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
/**
 * Internal dependencies
 */
import ChatInputForm from './chat-input-form';

describe( 'ChatInputForm', () => {
	afterEach( () => {
		jest.clearAllMocks();
	} );
	it( 'does not call handleSubmit when input is empty', () => {
		const mockOnSubmit = jest.fn();
		render(
			<ChatInputForm
				onSubmit={ mockOnSubmit }
				isLoading={ false }
				handleError={ jest.fn() }
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
				handleError={ jest.fn() }
			/>
		);

		fireEvent.change( screen.getByPlaceholderText( /Type your message/ ), {
			target: { value: 'Example question' },
		} );
		fireEvent.click( screen.getByText( 'Send' ) );
		expect( mockOnSubmit ).toHaveBeenCalledWith( 'Example question' );
	} );
} );
