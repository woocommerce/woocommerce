/**
 * External dependencies
 */
import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';

const mockRun = jest.fn();
const mockRetry = jest.fn();

jest.mock( '../../../hooks/use-chunked-import', () => ( {
	useChunkedImport: jest.fn( () => ( {
		run: mockRun,
		retry: mockRetry,
		cancel: jest.fn(),
		reset: jest.fn(),
		isRunning: true,
	} ) ),
} ) );

/**
 * Internal dependencies
 */
import ImportStep from '../import-step';
import { createInitialState } from '../../../hooks/use-importer-state';

describe( 'ImportStep', () => {
	beforeEach( () => {
		mockRun.mockClear();
		mockRetry.mockClear();
	} );

	it( 'invokes run exactly once on mount', async () => {
		const state = createInitialState();
		state.step = 'import';
		state.total = 100;
		state.token = 'tok';

		const { rerender } = render(
			<ImportStep
				state={ state }
				dispatch={ jest.fn() }
				onClose={ jest.fn() }
			/>
		);

		await waitFor( () => {
			expect( mockRun ).toHaveBeenCalledTimes( 1 );
		} );

		// Re-render with updated state should not re-trigger run.
		rerender(
			<ImportStep
				state={ { ...state, processed: 10 } }
				dispatch={ jest.fn() }
				onClose={ jest.fn() }
			/>
		);
		expect( mockRun ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'renders a progressbar reflecting processed / total', () => {
		const state = createInitialState();
		state.step = 'import';
		state.total = 100;
		state.processed = 25;
		state.token = 'tok';

		render(
			<ImportStep
				state={ state }
				dispatch={ jest.fn() }
				onClose={ jest.fn() }
			/>
		);

		const bar = screen.getByRole( 'progressbar', {
			name: /import progress/i,
		} );
		expect( bar.getAttribute( 'aria-valuenow' ) ).toBe( '25' );
	} );

	it( 'exposes a Retry button when an error is set', () => {
		const state = createInitialState();
		state.step = 'import';
		state.total = 100;
		state.processed = 10;
		state.token = 'tok';
		state.error = 'Network blew up';

		render(
			<ImportStep
				state={ state }
				dispatch={ jest.fn() }
				onClose={ jest.fn() }
			/>
		);

		expect(
			screen.getByRole( 'button', { name: /retry/i } )
		).toBeInTheDocument();
	} );
} );
