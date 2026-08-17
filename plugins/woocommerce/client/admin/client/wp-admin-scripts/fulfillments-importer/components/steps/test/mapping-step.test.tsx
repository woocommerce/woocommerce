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
	state.total = 1;
	state.token = 'tok';
	state.mapping = mapping;
	return state;
}

describe( 'MappingStep', () => {
	it( 'renders one row per header and disables Continue when required columns are missing', () => {
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

	it( 'auto-detects common header aliases for required columns', () => {
		const state = buildStateWithHeaders( {}, [
			'Order No',
			'tracking_num',
			'shipping provider',
		] );

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
			(
				a
			): a is Extract<
				ImporterAction,
				{ type: 'RESET_MAPPING_TO_DETECTED' }
			> => a.type === 'RESET_MAPPING_TO_DETECTED'
		);
		expect( reset?.mapping[ 0 ] ).toBe( 'order_number' );
		expect( reset?.mapping[ 1 ] ).toBe( 'tracking_number' );
		expect( reset?.mapping[ 2 ] ).toBe( 'shipment_provider' );
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

	it( 'highlights only unmapped rows while a required column is missing', () => {
		const state = buildStateWithHeaders( {
			0: 'order_number',
			1: 'tracking_url',
			// 2 is unmapped; tracking_number and shipment_provider are missing.
		} );

		render(
			<MappingStep
				state={ state }
				dispatch={ jest.fn() }
				onClose={ jest.fn() }
			/>
		);

		const highlighted = document.querySelectorAll( 'tr.is-required-row' );
		expect( highlighted ).toHaveLength( 1 );
		expect( highlighted[ 0 ] ).toHaveTextContent( 'Carrier' );
	} );

	it( 'highlights no rows when all required columns are mapped', () => {
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

		expect(
			document.querySelectorAll( 'tr.is-required-row' )
		).toHaveLength( 0 );
	} );

	it( 'leaves ambiguous headers unmapped on auto-detect', () => {
		const state = buildStateWithHeaders( {}, [ 'foo', 'bar', 'baz' ] );

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
			(
				a
			): a is Extract<
				ImporterAction,
				{ type: 'RESET_MAPPING_TO_DETECTED' }
			> => a.type === 'RESET_MAPPING_TO_DETECTED'
		);
		expect( reset?.mapping[ 0 ] ).toBe( '' );
		expect( reset?.mapping[ 1 ] ).toBe( '' );
		expect( reset?.mapping[ 2 ] ).toBe( '' );

		const startButton = screen.getByRole( 'button', {
			name: /start import/i,
		} );
		expect( startButton ).toBeDisabled();
	} );
} );
