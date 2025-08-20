/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import EditFulfillmentButton from '../edit-fulfillment-button';

describe( 'EditFulfillmentButton component', () => {
	it( 'should render button with correct text', () => {
		render( <EditFulfillmentButton onClick={ () => {} } /> );
		expect( screen.getByText( 'Edit fulfillment' ) ).toBeInTheDocument();
	} );

	it( 'should call onClick handler when clicked', () => {
		const mockOnClick = jest.fn();
		render( <EditFulfillmentButton onClick={ mockOnClick } /> );

		fireEvent.click( screen.getByText( 'Edit fulfillment' ) );
		expect( mockOnClick ).toHaveBeenCalledTimes( 1 );
	} );

	describe( 'Accessibility', () => {
		it( 'should have proper ARIA label', () => {
			render( <EditFulfillmentButton onClick={ () => {} } /> );

			const button = screen.getByRole( 'button' );
			expect( button ).toHaveAttribute(
				'aria-label',
				'Edit fulfillment details'
			);
		} );

		it( 'should have aria-describedby attribute', () => {
			render( <EditFulfillmentButton onClick={ () => {} } /> );

			const button = screen.getByRole( 'button' );
			expect( button ).toHaveAttribute(
				'aria-describedby',
				'edit-fulfillment-description'
			);
		} );

		it( 'should have hidden description for screen readers', () => {
			render( <EditFulfillmentButton onClick={ () => {} } /> );

			const description = screen.getByText(
				'Opens the fulfillment editor to modify fulfillment details'
			);
			expect( description ).toBeInTheDocument();
			expect( description ).toHaveAttribute(
				'id',
				'edit-fulfillment-description'
			);
			expect( description ).toHaveClass( 'screen-reader-text' );
		} );

		it( 'should be keyboard accessible', () => {
			const mockOnClick = jest.fn();
			render( <EditFulfillmentButton onClick={ mockOnClick } /> );

			const button = screen.getByRole( 'button' );
			button.focus();
			expect( document.activeElement ).toBe( button );
		} );
	} );
} );
