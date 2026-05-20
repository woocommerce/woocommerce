/**
 * External dependencies
 */
import React from 'react';
import { fireEvent, render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import MappingStep from '../mapping-step';
import { createInitialState } from '../../../hooks/use-importer-state';
import type { ImporterAction } from '../../../hooks/use-importer-state';

function buildStateWithHeaders(
	mapping: Record< number, string >,
	headers: string[] = [ 'Order ID', 'Tracking', 'Carrier' ]
) {
	const state = createInitialState();
	state.headers = headers;
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

	it( 'auto-detects common header aliases for required columns', () => {
		const state = buildStateWithHeaders(
			{},
			[ 'Order No', 'tracking_num', 'shipping provider' ]
		);

		const dispatched: ImporterAction[] = [];
		render(
			<MappingStep
				state={ state }
				dispatch={ ( action: ImporterAction ) => {
					dispatched.push( action );
				} }
				onClose={ jest.fn() }
			/>
		);

		fireEvent.click(
			screen.getByRole( 'button', { name: /auto-detect/i } )
		);

		const reset = dispatched.find(
			( a ) => a.type === 'RESET_MAPPING_TO_DETECTED'
		);
		expect( reset ).toBeTruthy();
		if ( reset && reset.type === 'RESET_MAPPING_TO_DETECTED' ) {
			expect( reset.mapping[ 0 ] ).toBe( 'order_number' );
			expect( reset.mapping[ 1 ] ).toBe( 'tracking_number' );
			expect( reset.mapping[ 2 ] ).toBe( 'shipment_provider' );
		}
	} );

	it( 'leaves ambiguous headers unmapped on auto-detect', () => {
		const state = buildStateWithHeaders(
			{},
			[ 'foo', 'bar', 'baz' ]
		);

		const dispatched: ImporterAction[] = [];
		render(
			<MappingStep
				state={ state }
				dispatch={ ( action: ImporterAction ) => {
					dispatched.push( action );
				} }
				onClose={ jest.fn() }
			/>
		);

		fireEvent.click(
			screen.getByRole( 'button', { name: /auto-detect/i } )
		);

		const reset = dispatched.find(
			( a ) => a.type === 'RESET_MAPPING_TO_DETECTED'
		);
		expect( reset ).toBeTruthy();
		if ( reset && reset.type === 'RESET_MAPPING_TO_DETECTED' ) {
			expect( reset.mapping[ 0 ] ).toBe( '' );
			expect( reset.mapping[ 1 ] ).toBe( '' );
			expect( reset.mapping[ 2 ] ).toBe( '' );
		}

		const startButton = screen.getByRole( 'button', {
			name: /start import/i,
		} );
		expect( startButton ).toBeDisabled();
	} );
} );
