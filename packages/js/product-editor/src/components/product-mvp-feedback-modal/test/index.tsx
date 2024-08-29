/**
 * External dependencies
 */
import { render, screen, fireEvent, act } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ProductMVPFeedbackModal } from '../index';

const mockRecordScoreCallback = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn(),
} ) );

describe( 'ProductMVPFeedbackModal', () => {
	const createSuccessNotice = jest.fn();

	beforeEach( () => {
		( useDispatch as jest.Mock ).mockReturnValue( { createSuccessNotice } );
	} );

	afterEach( () => {
		jest.resetAllMocks();
	} );

	it( 'should close the ProductMVPFeedback modal when X button pressed', async () => {
		render(
			<ProductMVPFeedbackModal
				recordScoreCallback={ mockRecordScoreCallback }
			/>
		);
		// Wait for the modal to render.
		await screen.findByRole( 'dialog' );
		// Press cancel button.
		fireEvent.click( screen.getByRole( 'button', { name: /Close/i } ) );
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
		fireEvent.click( screen.getByRole( 'button', { name: /Send/i } ) );
	} );
	it( 'should call the function sent as recordScoreCallback with the checked options', async () => {
		render(
			<ProductMVPFeedbackModal
				recordScoreCallback={ mockRecordScoreCallback }
			/>
		);
		// Wait for the modal to render.
		await screen.findByRole( 'dialog' );
		act( () => {
			fireEvent.click(
				screen.getByRole( 'checkbox', { name: /other/i } )
			);
		} );
		act( () => {
			fireEvent.click( screen.getByRole( 'button', { name: /Send/i } ) );
		} );

		expect( mockRecordScoreCallback ).toHaveBeenCalledWith(
			[ 'other' ],
			'',
			''
		);
	} );
} );
