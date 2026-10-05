import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import '../../../test-helper/global-mock';
import ShipmentViewer from '../shipment-viewer';
import { useShipmentFormContext } from '../../../context/shipment-form-context';
vi.mock( '../../../context/shipment-form-context', () => {
	const mock = {
		useShipmentFormContext: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../../utils/icons', () => {
	const mock = {
		CopyIcon: ( { copyText } ) => (
			<span data-testid="copy-icon">{ copyText }</span>
		),
		TruckIcon: () => <span data-testid="truck-icon" />,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../user-interface/fulfillments-card/card', () => {
	const mock = {
		__esModule: true,
		default: ( { header, children } ) => (
			<div data-testid="fulfillment-card">
				<div data-testid="card-header">{ header }</div>
				<div data-testid="card-body">{ children }</div>
			</div>
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../user-interface/meta-list/meta-list', () => {
	const mock = {
		__esModule: true,
		default: ( { metaList } ) => (
			<ul data-testid="meta-list">
				{ metaList.map( ( item, index ) => (
					<li key={ index }>
						{ item.label }: { item.value }
					</li>
				) ) }
			</ul>
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'ShipmentViewer', () => {
	const mockContext = {
		shipmentProvider: '',
		trackingNumber: '',
		trackingUrl: '',
		selectedOption: '',
	};
	beforeEach( () => {
		vi.clearAllMocks();
		useShipmentFormContext.mockReturnValue( mockContext );
	} );
	it( 'renders no shipment information when option is set to no info', () => {
		mockContext.selectedOption = 'no-info';
		render( <ShipmentViewer /> );
		expect( screen.getByTestId( 'truck-icon' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'No shipment information' )
		).toBeInTheDocument();
	} );
	it( 'renders shipment information when data is provided', () => {
		mockContext.selectedOption = 'tracking-number';
		mockContext.shipmentProvider = 'ups';
		mockContext.trackingNumber = '12345678';
		mockContext.trackingUrl = 'https://www.ups.com/track?tracknum=12345678';
		render( <ShipmentViewer /> );
		expect(
			screen.getByRole( 'heading', {
				level: 3,
				name: /12345678/,
			} )
		).toBeInTheDocument();
		expect( screen.getByTestId( 'copy-icon' ) ).toHaveTextContent(
			'12345678'
		);
		expect( screen.getByTestId( 'meta-list' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Tracking number: 12345678' )
		).toBeInTheDocument();
		expect(
			screen.getByText( /Provider name\s*:\s*UPS/ )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Tracking URL: https://www.ups.com/track?tracknum=12345678'
			)
		).toBeInTheDocument();
	} );
} );
