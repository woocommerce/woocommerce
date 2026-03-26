/**
 * External dependencies
 */
import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';

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

const setNotifyCustomer = jest.fn();
const setCustomerNote = jest.fn();

const mockUseFulfillmentContext = jest.fn( () => ( {
	notifyCustomer: true,
	setNotifyCustomer,
	customerNote: '',
	setCustomerNote,
} ) );

jest.mock( '../../../context/fulfillment-context', () => ( {
	useFulfillmentContext: ( ...args ) => mockUseFulfillmentContext( ...args ),
} ) );

// Mock ToggleControl and TextareaControl to make testing easier
jest.mock( '@wordpress/components', () => ( {
	ToggleControl: React.forwardRef( ( props, ref ) => (
		<div data-testid="toggle-control">
			<input
				ref={ ref }
				type="checkbox"
				checked={ props.checked }
				onChange={ () => props.onChange( ! props.checked ) }
				data-testid="toggle-input"
			/>
		</div>
	) ),
	TextareaControl: ( props ) => (
		<div data-testid="textarea-control">
			{ /* eslint-disable-next-line jsx-a11y/label-has-associated-control */ }
			<label htmlFor="customer-note">{ props.label }</label>
			<textarea
				id="customer-note"
				data-testid="customer-note-input"
				value={ props.value }
				onChange={ ( e ) => props.onChange( e.target.value ) }
				placeholder={ props.placeholder }
				rows={ props.rows }
			/>
		</div>
	),
} ) );

describe( 'CustomerNotificationBox component', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockUseFulfillmentContext.mockReturnValue( {
			notifyCustomer: true,
			setNotifyCustomer,
			customerNote: '',
			setCustomerNote,
		} );
	} );

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

	it( 'should call setNotifyCustomer with the correct value when toggle is changed', () => {
		render( <CustomerNotificationBox type="fulfill" /> );

		// Find and click the toggle input
		const toggleInput = screen.getByTestId( 'toggle-input' );
		toggleInput.click();

		// Check that setNotifyCustomer was called with false (toggling from true -> false)
		expect( setNotifyCustomer ).toHaveBeenCalledWith( false );
	} );

	it( 'should render with toggle in correct state based on value prop', () => {
		render( <CustomerNotificationBox type="fulfill" /> );

		// Verify toggle is checked
		const toggleInput = screen.getByTestId( 'toggle-input' );
		expect( toggleInput.checked ).toBe( true );
	} );

	it( 'should show textarea when type is update and notifyCustomer is true', () => {
		render( <CustomerNotificationBox type="update" /> );

		expect(
			screen.getByTestId( 'customer-note-input' )
		).toBeInTheDocument();
	} );

	it( 'should hide textarea when type is fulfill', () => {
		render( <CustomerNotificationBox type="fulfill" /> );

		expect(
			screen.queryByTestId( 'customer-note-input' )
		).not.toBeInTheDocument();
	} );

	it( 'should hide textarea when type is update but notifyCustomer is false', () => {
		mockUseFulfillmentContext.mockReturnValue( {
			notifyCustomer: false,
			setNotifyCustomer,
			customerNote: '',
			setCustomerNote,
		} );

		render( <CustomerNotificationBox type="update" /> );

		expect(
			screen.queryByTestId( 'customer-note-input' )
		).not.toBeInTheDocument();
	} );

	it( 'should call setCustomerNote when textarea value changes', () => {
		render( <CustomerNotificationBox type="update" /> );

		const textarea = screen.getByTestId( 'customer-note-input' );
		fireEvent.change( textarea, { target: { value: 'Test note' } } );

		expect( setCustomerNote ).toHaveBeenCalledWith( 'Test note' );
	} );
} );
