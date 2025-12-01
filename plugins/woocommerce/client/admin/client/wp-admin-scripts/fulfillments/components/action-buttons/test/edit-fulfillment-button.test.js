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
} );
