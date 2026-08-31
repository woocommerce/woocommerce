/**
 * External dependencies
 */
import React from 'react';
import { fireEvent, render, screen } from '@testing-library/react';

jest.mock( '../../importer-summary', () => () => (
	<div>SUMMARY_PANEL_STUB</div>
) );

/**
 * Internal dependencies
 */
import DoneStep from '../done-step';
import { createInitialState } from '../../../hooks/use-importer-state';

describe( 'DoneStep', () => {
	it( 'renders the summary panel and resets when "Import another file" is clicked', () => {
		const state = createInitialState();
		state.step = 'done';
		state.summary = {
			created: 3,
			updated: 1,
			skipped: 0,
			failed: 0,
			notified: 0,
			rows: [],
		};

		const dispatch = jest.fn();
		const onClose = jest.fn();

		render(
			<DoneStep
				state={ state }
				dispatch={ dispatch }
				onClose={ onClose }
			/>
		);

		expect( screen.getByText( 'SUMMARY_PANEL_STUB' ) ).toBeInTheDocument();

		fireEvent.click(
			screen.getByRole( 'button', { name: /import another file/i } )
		);
		expect( dispatch ).toHaveBeenCalledWith( { type: 'RESET' } );

		fireEvent.click( screen.getByRole( 'button', { name: /^done$/i } ) );
		expect( onClose ).toHaveBeenCalled();
	} );

	it( 'offers "Download failed rows" as the main action when some rows failed', () => {
		const state = createInitialState();
		state.step = 'done';
		state.summary = {
			created: 2,
			updated: 0,
			skipped: 0,
			failed: 4,
			notified: 0,
			rows: [],
		};

		render(
			<DoneStep
				state={ state }
				dispatch={ jest.fn() }
				onClose={ jest.fn() }
			/>
		);

		expect(
			screen.getByRole( 'button', { name: /download failed rows/i } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: /^done$/i } )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: /back to mapping/i } )
		).not.toBeInTheDocument();
	} );

	it( 'offers "Back to mapping" and an explanatory notice when nothing imported', () => {
		const state = createInitialState();
		state.step = 'done';
		state.file = new File( [ 'a,b,c' ], 'orders.csv' );
		state.summary = {
			created: 0,
			updated: 0,
			skipped: 0,
			failed: 2,
			notified: 0,
			rows: [
				{
					row: 2,
					status: 'failed',
					code: 'order_not_found',
					message: 'Order not found for order number "x".',
					order_number: 'x',
				},
				{
					row: 3,
					status: 'failed',
					code: 'order_not_found',
					message: 'Order not found for order number "y".',
					order_number: 'y',
				},
			],
		};

		render(
			<DoneStep
				state={ state }
				dispatch={ jest.fn() }
				onClose={ jest.fn() }
			/>
		);

		expect(
			screen.getByRole( 'button', { name: /back to mapping/i } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: /download failed rows/i } )
		).toBeInTheDocument();
		expect(
			screen.getAllByText( /No rows were imported/ ).length
		).toBeGreaterThan( 0 );
	} );
} );
