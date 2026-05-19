/**
 * External dependencies
 */
import React from 'react';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import MappingStep from '../mapping-step';
import { createInitialState } from '../../../hooks/use-importer-state';

function buildStateWithHeaders( mapping: Record< number, string > ) {
	const state = createInitialState();
	state.headers = [ 'Order ID', 'Tracking', 'Carrier' ];
	state.sample = [ '12345', 'TRK-1', 'UPS' ];
	state.total = 1;
	state.token = 'tok';
	state.mapping = mapping as any;
	return state;
}

describe( 'MappingStep', () => {
	it( 'renders one row per header and disables Continue when required columns are missing', () => {
		const state = buildStateWithHeaders( {
			0: 'order_number',
			1: 'tracking_number',
			// 2 is intentionally unmapped — shipment_provider is required.
		} );

		render(
			<MappingStep
				state={ state }
				dispatch={ jest.fn() }
				onClose={ jest.fn() }
			/>
		);

		expect( screen.getByText( 'Order ID' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Tracking' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Carrier' ) ).toBeInTheDocument();

		const startButton = screen.getByRole( 'button', {
			name: /start import/i,
		} );
		expect( startButton ).toBeDisabled();
	} );

	it( 'enables Start import when all required columns are mapped', () => {
		const state = buildStateWithHeaders( {
			0: 'order_number',
			1: 'tracking_number',
			2: 'shipment_provider',
		} );

		render(
			<MappingStep
				state={ state }
				dispatch={ jest.fn() }
				onClose={ jest.fn() }
			/>
		);

		const startButton = screen.getByRole( 'button', {
			name: /start import/i,
		} );
		expect( startButton ).toBeEnabled();
	} );
} );
