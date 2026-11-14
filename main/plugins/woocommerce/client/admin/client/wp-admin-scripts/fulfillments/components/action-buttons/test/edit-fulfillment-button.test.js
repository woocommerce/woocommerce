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
		it( 'should not have redundant aria-label overriding visible text', () => {
			render( <EditFulfillmentButton onClick={ () => {} } /> );

			const button = screen.getByRole( 'button' );
			expect( button ).not.toHaveAttribute( 'aria-label' );
		} );

		it( 'should have aria-describedby with unique prefix', () => {
			render( <EditFulfillmentButton onClick={ () => {} } /> );

			const button = screen.getByRole( 'button' );
			expect( button.getAttribute( 'aria-describedby' ) ).toMatch(
				/^edit-fulfillment-description/
			);
		} );

		it( 'should have hidden description for screen readers', () => {
			render( <EditFulfillmentButton onClick={ () => {} } /> );

			const description = screen.getByText(
				'Opens the fulfillment editor to modify fulfillment details'
			);
			expect( description ).toBeInTheDocument();
			expect( description.getAttribute( 'id' ) ).toMatch(
				/^edit-fulfillment-description/
			);
			expect( description ).toHaveClass( 'screen-reader-text' );
		} );

		it( 'should be keyboard accessible', () => {
			const mockOnClick = jest.fn();
			render( <EditFulfillmentButton onClick={ mockOnClick } /> );

			const button = screen.getByRole( 'button' );
			button.focus();
			expect( button.ownerDocument.activeElement ).toBe( button );
		} );
	} );
} );
