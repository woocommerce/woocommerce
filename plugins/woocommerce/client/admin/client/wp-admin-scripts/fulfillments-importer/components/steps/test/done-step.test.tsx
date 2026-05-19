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
} );
