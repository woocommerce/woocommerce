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
} );
