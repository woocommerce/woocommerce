/**
 * External dependencies
 */
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import '../../../test-helper/global-mock';
import ShipmentTrackingNumberForm from '../shipment-tracking-number-form';
import { useShipmentFormContext } from '../../../context/shipment-form-context';

jest.mock( '../../../context/shipment-form-context', () => ( {
	useShipmentFormContext: jest.fn(),
} ) );

jest.mock( '../../../utils/icons', () => ( {
	EditIcon: () => <span data-testid="edit-icon" />,
} ) );

jest.mock( '@wordpress/api-fetch' );

jest.mock( '@wordpress/components', () => ( {
	...jest.requireActual( '@wordpress/components' ),
	TextControl: ( { value, onChange, placeholder } ) => (
		<div data-testid="text-control">
			<input
				type="text"
				value={ value }
				placeholder={ placeholder }
				onChange={ ( e ) => onChange( e.target.value ) }
			/>
		</div>
	),
} ) );

describe( 'ShipmentTrackingNumberForm', () => {
	const mockContext = {
		trackingNumber: '',
		setTrackingNumber: jest.fn(),
		shipmentProvider: '',
		setShipmentProvider: jest.fn(),
		trackingUrl: '',
		setTrackingUrl: jest.fn(),
		providerName: '',
		setProviderName: jest.fn(),
	};

	beforeEach( () => {
		jest.clearAllMocks();
		useShipmentFormContext.mockReturnValue( mockContext );
	} );

	it( 'renders tracking number input in edit mode', () => {
		render( <ShipmentTrackingNumberForm /> );
		expect(
			screen.getByPlaceholderText( 'Enter tracking number' )
		).toBeInTheDocument();
		expect( screen.getByText( 'Find info' ) ).toBeInTheDocument();
	} );

	it( 'renders tracking number and provider in view mode', () => {
		mockContext.trackingNumber = '1Z12345E0291980793';
		mockContext.shipmentProvider = 'ups';
		render( <ShipmentTrackingNumberForm /> );
		expect( screen.getByText( '1Z12345E0291980793' ) ).toBeInTheDocument();
		expect( screen.getByText( 'UPS' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'edit-icon' ) ).toBeInTheDocument();
	} );

	it( 'calls setTrackingNumber and switches to view mode on valid lookup', async () => {
		mockContext.trackingNumber = '';
		mockContext.shipmentProvider = '';
		apiFetch.mockResolvedValueOnce( {
			tracking_number_details: {
				tracking_number: '1Z12345E0291980793',
				provider: 'ups',
				tracking_url:
					'https://www.ups.com/track?tracknum=1Z12345E0291980793',
			},
		} );
		render( <ShipmentTrackingNumberForm /> );
		const input = screen.getByPlaceholderText( 'Enter tracking number' );
		fireEvent.change( input, { target: { value: '1Z12345E0291980793' } } );
		fireEvent.click( screen.getByText( 'Find info' ) );

		await waitFor( () => {
			expect( mockContext.setTrackingNumber ).toHaveBeenCalledWith(
				'1Z12345E0291980793'
			);
		} );
		await expect( mockContext.setShipmentProvider ).toHaveBeenCalledWith(
			'ups'
		);
		await expect( mockContext.setTrackingUrl ).toHaveBeenCalledWith(
			'https://www.ups.com/track?tracknum=1Z12345E0291980793'
		);
		await expect(
			screen.queryByPlaceholderText( 'Enter tracking number' )
		).not.toBeInTheDocument();
	} );

	it( 'shows error message on invalid lookup', async () => {
		mockContext.trackingNumber = '';
		mockContext.shipmentProvider = '';
		apiFetch.mockResolvedValueOnce( { tracking_number_details: [] } );
		render( <ShipmentTrackingNumberForm /> );
		const input = screen.getByPlaceholderText( 'Enter tracking number' );
		fireEvent.change( input, { target: { value: 'invalid' } } );
		fireEvent.click( screen.getByText( 'Find info' ) );
		await waitFor( () => {
			expect(
				screen.getByText(
					'No information found for this tracking number. Check the number or enter the details manually.'
				)
			).toBeInTheDocument();
		} );
	} );

	it( 'switches back to edit mode when edit button is clicked', () => {
		mockContext.trackingNumber = '12345678';
		render( <ShipmentTrackingNumberForm /> );
		fireEvent.click( screen.getByTestId( 'edit-icon' ) );
		expect(
			screen.getByPlaceholderText( 'Enter tracking number' )
		).toBeInTheDocument();
	} );
} );
