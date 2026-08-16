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
		it( 'should not have redundant aria-label overriding visible text', () => {
			render( <CancelLink onClick={ () => {} } /> );

			const button = screen.getByRole( 'button' );
			expect( button ).not.toHaveAttribute( 'aria-label' );
		} );

		it( 'should have aria-describedby with unique prefix', () => {
			render( <CancelLink onClick={ () => {} } /> );

			const button = screen.getByRole( 'button' );
			expect( button.getAttribute( 'aria-describedby' ) ).toMatch(
				/^cancel-link-description/
			);
		} );

		it( 'should have hidden description for screen readers', () => {
			render( <CancelLink onClick={ () => {} } /> );

			const description = screen.getByText(
				'Cancels the current operation without saving changes'
			);
			expect( description ).toBeInTheDocument();
			expect( description.getAttribute( 'id' ) ).toMatch(
				/^cancel-link-description/
			);
			expect( description ).toHaveClass( 'screen-reader-text' );
		} );

		it( 'should be keyboard accessible', () => {
			const mockOnClick = jest.fn();
			render( <CancelLink onClick={ mockOnClick } /> );

			const button = screen.getByRole( 'button' );
			button.focus();
			expect( button.ownerDocument.activeElement ).toBe( button );
		} );

		it( 'should have correct styling for flex layout', () => {
			render( <CancelLink onClick={ () => {} } /> );

			const button = screen.getByRole( 'button' );
			expect( button ).toHaveStyle( { flex: '1' } );
		} );
	} );
} );
