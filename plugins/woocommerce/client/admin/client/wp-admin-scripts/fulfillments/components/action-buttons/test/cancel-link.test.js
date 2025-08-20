/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import CancelLink from '../cancel-link';

describe( 'CancelLink component', () => {
	it( 'should render a cancel button', () => {
		render( <CancelLink onClick={ () => {} } /> );
		expect( screen.getByText( 'Cancel' ) ).toBeInTheDocument();
	} );

	it( 'should call onClick handler when clicked', () => {
		const mockOnClick = jest.fn();
		render( <CancelLink onClick={ mockOnClick } /> );

		fireEvent.click( screen.getByText( 'Cancel' ) );
		expect( mockOnClick ).toHaveBeenCalledTimes( 1 );
	} );

	describe( 'Accessibility', () => {
		it( 'should have proper ARIA label', () => {
			render( <CancelLink onClick={ () => {} } /> );

			const button = screen.getByRole( 'button' );
			expect( button ).toHaveAttribute(
				'aria-label',
				'Cancel current action and return to previous state'
			);
		} );

		it( 'should have aria-describedby attribute', () => {
			render( <CancelLink onClick={ () => {} } /> );

			const button = screen.getByRole( 'button' );
			expect( button ).toHaveAttribute(
				'aria-describedby',
				'cancel-link-description'
			);
		} );

		it( 'should have hidden description for screen readers', () => {
			render( <CancelLink onClick={ () => {} } /> );

			const description = screen.getByText(
				'Cancels the current operation without saving changes'
			);
			expect( description ).toBeInTheDocument();
			expect( description ).toHaveAttribute(
				'id',
				'cancel-link-description'
			);
			expect( description ).toHaveClass( 'screen-reader-text' );
		} );

		it( 'should be keyboard accessible', () => {
			const mockOnClick = jest.fn();
			render( <CancelLink onClick={ mockOnClick } /> );

			const button = screen.getByRole( 'button' );
			button.focus();
			expect( document.activeElement ).toBe( button );
		} );

		it( 'should have correct styling for flex layout', () => {
			render( <CancelLink onClick={ () => {} } /> );

			const button = screen.getByRole( 'button' );
			expect( button ).toHaveStyle( { flex: '1' } );
		} );
	} );
} );
