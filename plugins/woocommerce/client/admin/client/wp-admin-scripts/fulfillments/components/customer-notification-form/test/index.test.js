/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import CustomerNotificationBox from '../index';

// Mock dependencies
jest.mock( '../../user-interface/fulfillments-card/card', () => ( {
	__esModule: true,
	default: ( { children, header } ) => (
		<div data-testid="fulfillment-card">
			<div data-testid="card-header">{ header }</div>
			<div data-testid="card-body">{ children }</div>
		</div>
	),
} ) );

jest.mock( '../../../utils/icons', () => ( {
	EnvelopeIcon: () => <div data-testid="envelope-icon" />,
} ) );

const setValue = jest.fn();

jest.mock( '../../../context/fulfillment-context', () => ( {
	useFulfillmentContext: jest.fn( () => ( {
		notifyCustomer: true,
		setNotifyCustomer: setValue,
	} ) ),
} ) );

// Mock ToggleControl to make testing easier
jest.mock( '@wordpress/components', () => ( {
	ToggleControl: ( props ) => (
		<div data-testid="toggle-control">
			<input
				type="checkbox"
				checked={ props.checked }
				onChange={ () => props.onChange( ! props.checked ) }
				data-testid="toggle-input"
			/>
		</div>
	),
} ) );

describe( 'CustomerNotificationBox component', () => {
	it( 'should render the component with proper title', () => {
		render( <CustomerNotificationBox type="fulfill" /> );

		// Check title and icon
		expect(
			screen.getByText( 'Fulfillment notification' )
		).toBeInTheDocument();
		expect( screen.getByTestId( 'envelope-icon' ) ).toBeInTheDocument();
	} );

	it( 'should render the description text', () => {
		render( <CustomerNotificationBox type="fulfill" /> );

		// Check description text
		expect(
			screen.getByText(
				'Automatically send an email to the customer when the selected items are fulfilled.'
			)
		).toBeInTheDocument();
	} );

	it( 'should call setValue with the correct value when toggle is changed', () => {
		render( <CustomerNotificationBox type="fulfill" /> );

		// Find and click the toggle input
		const toggleInput = screen.getByTestId( 'toggle-input' );
		toggleInput.click();

		// Check that setValue was called with true (toggling from true -> false)
		expect( setValue ).toHaveBeenCalledWith( false );
	} );

	it( 'should render with toggle in correct state based on value prop', () => {
		render( <CustomerNotificationBox type="fulfill" /> );

		// Verify toggle is checked
		const toggleInput = screen.getByTestId( 'toggle-input' );
		expect( toggleInput.checked ).toBe( true );
	} );
} );
