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
import type { ColumnMapping } from '../../../data/types';

function buildStateWithHeaders(
	mapping: ColumnMapping,
	headers: string[] = [ 'Order ID', 'Tracking', 'Carrier' ]
) {
	const state = createInitialState();
	state.headers = headers;
	state.sample = [ '12345', 'TRK-1', 'UPS' ];
	state.total = 6;
	state.token = 'tok';
	state.mapping = mapping;
	return state;
}

describe( 'MappingStep', () => {
	it( 'renders one row per header and disables Start import when required columns are missing', () => {
		const state = buildStateWithHeaders( {
			0: 'order_number',
			1: 'tracking_number',
			// 2 is intentionally unmapped; shipment_provider is required.
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

	it( 'shows the number of rows found in the file', () => {
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

		expect( screen.getByText( '6 rows found.' ) ).toBeInTheDocument();
	} );

	it( 'names the missing required field in an error notice', () => {
		const state = buildStateWithHeaders( {
			0: 'order_number',
			1: '',
			2: 'shipment_provider',
		} );

		render(
			<MappingStep
				state={ state }
				dispatch={ jest.fn() }
				onClose={ jest.fn() }
			/>
		);

		// The Notice also mirrors its text into an a11y-speak region, so
		// match on all occurrences.
		expect(
			screen.getAllByText( /Tracking number is not mapped/ ).length
		).toBeGreaterThan( 0 );
		expect( screen.getByText( 'Not mapped.' ) ).toBeInTheDocument();
	} );

	it( 'flags unassigned columns but not those set to "Do not import"', () => {
		const state = buildStateWithHeaders( {
			0: 'order_number',
			1: 'skip',
			2: '',
			// tracking_number and shipment_provider are missing; column 1 was
			// deliberately excluded so only column 2 is a candidate.
		} );

		render(
			<MappingStep
				state={ state }
				dispatch={ jest.fn() }
				onClose={ jest.fn() }
			/>
		);

		const flagged = document.querySelectorAll( 'td.is-error' );
		expect( flagged ).toHaveLength( 1 );
		expect( flagged[ 0 ]?.closest( 'tr' ) ).toHaveTextContent( 'Carrier' );
	} );

	it( 'exposes the flagged column error state to assistive tech', () => {
		const state = buildStateWithHeaders( {
			0: 'order_number',
			1: '',
			2: 'shipment_provider',
		} );

		render(
			<MappingStep
				state={ state }
				dispatch={ jest.fn() }
				onClose={ jest.fn() }
			/>
		);

		const select = screen.getByLabelText( 'Map column Tracking' );
		expect( select ).toHaveAttribute( 'aria-invalid', 'true' );
		const describedBy = select.getAttribute( 'aria-describedby' );
		expect( describedBy ).toBeTruthy();
		expect(
			document.getElementById( describedBy as string )
		).toHaveTextContent( 'Not mapped.' );
	} );

	it( 'shows unassigned columns as "Do not import" once nothing required is missing', () => {
		const state = buildStateWithHeaders(
			{
				0: 'order_number',
				1: 'tracking_number',
				2: 'shipment_provider',
				3: '',
			},
			[ 'Order ID', 'Tracking', 'Carrier', 'Note' ]
		);
		state.sample = [ '12345', 'TRK-1', 'UPS', 'hello' ];

		render(
			<MappingStep
				state={ state }
				dispatch={ jest.fn() }
				onClose={ jest.fn() }
			/>
		);

		expect( screen.getByLabelText( 'Map column Note' ) ).toHaveValue(
			'skip'
		);
		expect( document.querySelectorAll( 'td.is-error' ) ).toHaveLength( 0 );
	} );

	it( 'flags no columns when all required columns are mapped', () => {
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

		expect( document.querySelectorAll( 'td.is-error' ) ).toHaveLength( 0 );
	} );

	it( 'dispatches SET_MAPPING_FOR_COL with the changed column and value', () => {
		const state = buildStateWithHeaders( {
			0: 'order_number',
			1: 'tracking_number',
			2: '',
		} );

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

		fireEvent.change( screen.getByLabelText( 'Map column Carrier' ), {
			target: { value: 'shipment_provider' },
		} );

		expect( dispatched ).toEqual( [
			{
				type: 'SET_MAPPING_FOR_COL',
				col: 2,
				value: 'shipment_provider',
			},
		] );
	} );

	it( 'dispatches BACK_TO_UPLOAD from the Back button', () => {
		const state = buildStateWithHeaders( {
			0: 'order_number',
			1: 'tracking_number',
			2: 'shipment_provider',
		} );

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

		fireEvent.click( screen.getByRole( 'button', { name: /^back$/i } ) );

		expect( dispatched ).toEqual( [ { type: 'BACK_TO_UPLOAD' } ] );
	} );

	it( 'dispatches SET_NOTIFY when the notification checkbox is toggled', () => {
		const state = buildStateWithHeaders( {
			0: 'order_number',
			1: 'tracking_number',
			2: 'shipment_provider',
		} );

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
			screen.getByRole( 'checkbox', {
				name: /send shipment notification emails/i,
			} )
		);

		expect( dispatched ).toEqual( [ { type: 'SET_NOTIFY', value: true } ] );
	} );
} );
