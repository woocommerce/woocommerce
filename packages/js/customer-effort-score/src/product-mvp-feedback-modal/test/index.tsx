/**
 * External dependencies
 */
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ProductMVPFeedbackModal } from '../index';

const mockRecordScoreCallback = jest.fn();

describe( 'ProductMVPFeedbackModal', () => {
	it( 'should close the ProductMVPFeedback modal when skip button pressed', async () => {
		render(
			<ProductMVPFeedbackModal
				recordScoreCallback={ mockRecordScoreCallback }
			/>
		);
		// Wait for the modal to render.
		await screen.findByRole( 'dialog' );
		// Press cancel button.
		fireEvent.click( screen.getByRole( 'button', { name: /Skip/i } ) );
		expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
	} );
	it( 'should enable Send button when an option is checked', async () => {
		render(
			<ProductMVPFeedbackModal
				recordScoreCallback={ mockRecordScoreCallback }
			/>
		);
		// Wait for the modal to render.
		await screen.findByRole( 'dialog' );
		fireEvent.click( screen.getByRole( 'checkbox', { name: /other/i } ) );
		fireEvent.click(
			screen.getByRole( 'button', { name: /Send feedback/i } )
		);
	} );
	it( 'should call the function sent as recordScoreCallback with the checked options', async () => {
		render(
			<ProductMVPFeedbackModal
				recordScoreCallback={ mockRecordScoreCallback }
			/>
		);
		// Wait for the modal to render.
		await screen.findByRole( 'dialog' );
		fireEvent.click( screen.getByRole( 'checkbox', { name: /other/i } ) );
		expect( mockRecordScoreCallback ).toHaveBeenCalledWith(
			[ 'other' ],
			''
		);
	} );
} );
