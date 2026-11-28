/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ShipmentForm from '../../shipment-form';
import { useShipmentFormContext } from '../../../context/shipment-form-context';
import {
	SHIPMENT_OPTION_MANUAL_ENTRY,
	SHIPMENT_OPTION_NO_INFO,
	SHIPMENT_OPTION_TRACKING_NUMBER,
} from '../../../data/constants';

// 🔁 Mock dependent components
jest.mock( '../shipment-tracking-number-form', () => () => (
	<div data-testid="tracking-form" />
) );
jest.mock( '../shipment-manual-entry-form', () => () => (
	<div data-testid="manual-form" />
) );
jest.mock( '../../../utils/icons', () => ( {
	TruckIcon: () => <div data-testid="truck-icon" />,
} ) );

// 🧪 Mock context
const mockSetSelectedOption = jest.fn();

jest.mock( '../../../context/shipment-form-context', () => ( {
	useShipmentFormContext: jest.fn(),
} ) );

beforeEach( () => {
	jest.clearAllMocks();
} );

function setup( selectedOption ) {
	useShipmentFormContext.mockReturnValue( {
		selectedOption,
		setSelectedOption: mockSetSelectedOption,
	} );

	render( <ShipmentForm /> );
}

describe( '<ShipmentForm />', () => {
	it( 'renders all shipment option radios', () => {
		setup();

		expect(
			screen.getByLabelText( 'Tracking Number' )
		).toBeInTheDocument();
		expect( screen.getByLabelText( 'Enter manually' ) ).toBeInTheDocument();
		expect(
			screen.getByLabelText( 'No shipment information' )
		).toBeInTheDocument();
	} );

	it( 'shows tracking number form when selected', () => {
		setup( SHIPMENT_OPTION_TRACKING_NUMBER );

		expect( screen.getByTestId( 'tracking-form' ) ).toBeInTheDocument();
		expect( screen.queryByTestId( 'manual-form' ) ).not.toBeInTheDocument();
	} );

	it( 'shows manual entry form when selected', () => {
		setup( SHIPMENT_OPTION_MANUAL_ENTRY );

		expect( screen.getByTestId( 'manual-form' ) ).toBeInTheDocument();
		expect(
			screen.queryByTestId( 'tracking-form' )
		).not.toBeInTheDocument();
	} );

	it( 'does not show any form when no info is selected', () => {
		setup( SHIPMENT_OPTION_NO_INFO );

		expect(
			screen.queryByTestId( 'tracking-form' )
		).not.toBeInTheDocument();
		expect( screen.queryByTestId( 'manual-form' ) ).not.toBeInTheDocument();
	} );

	it( 'calls setSelectedOption when a different radio is selected', async () => {
		setup( '' );

		fireEvent.click( screen.getByLabelText( 'Tracking Number' ) );
		expect( mockSetSelectedOption ).toHaveBeenCalledWith(
			SHIPMENT_OPTION_TRACKING_NUMBER
		);

		fireEvent.click( screen.getByLabelText( 'Enter manually' ) );
		expect( mockSetSelectedOption ).toHaveBeenCalledWith(
			SHIPMENT_OPTION_MANUAL_ENTRY
		);

		fireEvent.click( screen.getByLabelText( 'No shipment information' ) );
		expect( mockSetSelectedOption ).toHaveBeenCalledWith(
			SHIPMENT_OPTION_NO_INFO
		);
	} );
} );
