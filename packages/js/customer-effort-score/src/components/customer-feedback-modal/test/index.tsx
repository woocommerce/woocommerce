/**
 * External dependencies
 */
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CustomerFeedbackModal } from '../index';

const mockRecordScoreCallback = jest.fn();

describe( 'CustomerFeedbackModal', () => {
	it( 'should close modal when cancel button pressed', async () => {
		render(
			<CustomerFeedbackModal
				recordScoreCallback={ mockRecordScoreCallback }
				title="Testing"
				firstQuestion="First question"
				secondQuestion="Second question"
			/>
		);

		// Wait for the modal to render.
		await screen.findByRole( 'dialog' );

		// Press cancel button.
		fireEvent.click( screen.getByRole( 'button', { name: /cancel/i } ) );

		expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
	} );

	it( 'should halt with an error when submitting without a score', async () => {
		render(
			<CustomerFeedbackModal
				recordScoreCallback={ mockRecordScoreCallback }
				title="Testing"
				firstQuestion="First question"
				secondQuestion="Second question"
			/>
		);

		await screen.findByRole( 'dialog' ); // Wait for the modal to render.

		fireEvent.click( screen.getByRole( 'button', { name: /share/i } ) ); // Press send button.

		// Wait for error message.
		await screen.findByRole( 'alert' );

		expect( screen.getByRole( 'dialog' ) ).toBeInTheDocument();
	} );

	it( 'should disable the comments field initially', async () => {
		render(
			<CustomerFeedbackModal
				recordScoreCallback={ mockRecordScoreCallback }
				title="Testing"
				firstQuestion="First question"
				secondQuestion="Second question"
			/>
		);

		// Wait for the modal to render.
		await screen.findByRole( 'dialog' );

		expect(
			screen.queryByLabelText(
				'How is that screen useful to you? What features would you add or change?'
			)
		).not.toBeInTheDocument();
	} );

	it.each( [ 'Strongly disagree', 'Disagree' ] )(
		'should toggle the comments field when %s is selected',
		async ( labelText ) => {
			render(
				<CustomerFeedbackModal
					recordScoreCallback={ mockRecordScoreCallback }
					title="Testing"
					firstQuestion="First question"
					secondQuestion="Second question"
				/>
			);

			// Wait for the modal to render.
			await screen.findByRole( 'dialog' );

			// Select the option.
			fireEvent.click( screen.getAllByLabelText( labelText )[ 0 ] );

			// Wait for comments field to show.
			await screen.findByLabelText(
				'How is that screen useful to you? What features would you add or change?'
			);

			// Select neutral score.
			fireEvent.click( screen.getAllByLabelText( 'Neutral' )[ 0 ] );

			// Wait for comments field to hide.
			await waitFor( () => {
				expect(
					screen.queryByLabelText(
						'How is that screen useful to you? What features would you add or change?'
					)
				).not.toBeInTheDocument();
			} );
		}
	);

	it( 'should render even if no second question is provided', async () => {
		render(
			<CustomerFeedbackModal
				recordScoreCallback={ mockRecordScoreCallback }
				title="Testing"
				firstQuestion="First question"
			/>
		);

		// Wait for the modal to render.
		await screen.findByRole( 'dialog' );
		// Should be only one neutral emoji, since there is one question only
		expect( screen.getAllByLabelText( 'Neutral' ).length ).toBe( 1 );
	} );

	it( 'should render default title if no title is provided', async () => {
		render(
			<CustomerFeedbackModal
				recordScoreCallback={ mockRecordScoreCallback }
				firstQuestion="First question"
			/>
		);

		// Wait for the modal to render.
		await screen.findByRole( 'dialog' );
		expect(
			screen.queryByLabelText( 'Please share your feedback' )
		).toBeInTheDocument();
	} );
} );
