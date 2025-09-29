/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import '../../../test-helper/global-mock';
import ShipmentManualEntryForm from '../shipment-manual-entry-form';
import { useShipmentFormContext } from '../../../context/shipment-form-context';

jest.mock( '../../../context/shipment-form-context', () => ( {
	useShipmentFormContext: jest.fn(),
} ) );

jest.mock( '../../../utils/icons', () => ( {
	SearchIcon: () => <span data-testid="search-icon" />,
} ) );

jest.mock( '@wordpress/components', () => ( {
	...jest.requireActual( '@wordpress/components' ),
	ComboboxControl: ( { value, onChange, options } ) => (
		<div data-testid="combobox-control">
			<select
				value={ value }
				onChange={ ( e ) => onChange( e.target.value ) }
			>
				{ options.map( ( option ) => (
					<option key={ option.value } value={ option.value }>
						{ option.label }
					</option>
				) ) }
			</select>
		</div>
	),
} ) );

describe( 'ShipmentManualEntryForm', () => {
	const mockContext = {
		trackingNumber: '',
		setTrackingNumber: jest.fn(),
		shipmentProvider: '',
		setShipmentProvider: jest.fn(),
		providerName: '',
		setProviderName: jest.fn(),
		trackingUrl: '',
		setTrackingUrl: jest.fn(),
	};

	beforeEach( () => {
		jest.clearAllMocks();
		useShipmentFormContext.mockReturnValue( mockContext );
	} );

	it( 'renders tracking number input', () => {
		render( <ShipmentManualEntryForm /> );
		expect(
			screen.getByPlaceholderText( 'Enter tracking number' )
		).toBeInTheDocument();
	} );

	it( 'renders provider combobox and search icon', () => {
		render( <ShipmentManualEntryForm /> );
		expect( screen.getByRole( 'combobox' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'search-icon' ) ).toBeInTheDocument();
	} );

	it( 'renders provider name input when provider is set to other', () => {
		mockContext.shipmentProvider = 'other';
		render( <ShipmentManualEntryForm /> );
		expect(
			screen.getByPlaceholderText( 'Enter provider name' )
		).toBeInTheDocument();
	} );

	it( 'renders tracking URL input', () => {
		render( <ShipmentManualEntryForm /> );
		expect(
			screen.getByPlaceholderText( 'Enter tracking URL' )
		).toBeInTheDocument();
	} );

	it( 'calls setTrackingNumber on input change', () => {
		render( <ShipmentManualEntryForm /> );
		const input = screen.getByPlaceholderText( 'Enter tracking number' );
		fireEvent.change( input, { target: { value: '12345' } } );
		expect( mockContext.setTrackingNumber ).toHaveBeenCalledWith( '12345' );
	} );

	it( 'calls setShipmentProvider on combobox change', () => {
		render( <ShipmentManualEntryForm /> );
		const combobox = screen.getByRole( 'combobox' );
		fireEvent.change( combobox, { target: { value: 'dhl' } } );
		expect( mockContext.setShipmentProvider ).toHaveBeenCalledWith( 'dhl' );
	} );

	it( 'calls setProviderName on provider name input change', () => {
		mockContext.shipmentProvider = 'other';
		render( <ShipmentManualEntryForm /> );
		const input = screen.getByPlaceholderText( 'Enter provider name' );
		fireEvent.change( input, { target: { value: 'Custom Provider' } } );
		expect( mockContext.setProviderName ).toHaveBeenCalledWith(
			'Custom Provider'
		);
	} );

	it( 'calls setTrackingUrl on tracking URL input change', () => {
		render( <ShipmentManualEntryForm /> );
		const input = screen.getByPlaceholderText( 'Enter tracking URL' );
		fireEvent.change( input, { target: { value: 'http://example.com' } } );
		expect( mockContext.setTrackingUrl ).toHaveBeenCalledWith(
			'http://example.com'
		);
	} );
} );
