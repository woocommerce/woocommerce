/**
 * External dependencies
 */
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { FeedbackModal } from '../index';

const mockRecordScoreCallback = jest.fn();

describe( 'FeedbackModal', () => {
	it( 'should render a modal', async () => {
		render(
			<FeedbackModal
				onSendFeedback={ mockRecordScoreCallback }
				title="Testing"
				sendButtonLabel="Send"
				cancelButtonLabel="Cancel"
			/>
		);

		// Wait for the modal to render.
		await screen.findByRole( 'dialog' );

		expect(
			screen.getByRole( 'button', { name: /Send/i } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: /Cancel/i } )
		).toBeInTheDocument();
	} );

	it( 'should close modal when cancel button pressed', async () => {
		render(
			<FeedbackModal
				onSendFeedback={ mockRecordScoreCallback }
				title="Testing"
				sendButtonLabel="Send"
				cancelButtonLabel="Cancel"
			/>
		);

		// Wait for the modal to render.
		await screen.findByRole( 'dialog' );

		// Press cancel button.
		fireEvent.click( screen.getByRole( 'button', { name: /Cancel/i } ) );

		expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
	} );
} );
