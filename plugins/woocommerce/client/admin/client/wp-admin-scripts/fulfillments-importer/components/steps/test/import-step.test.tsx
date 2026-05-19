/**
 * External dependencies
 */
import React from 'react';
import { render, screen } from '@testing-library/react';

jest.mock( '../../../hooks/use-chunked-import', () => ( {
	useChunkedImport: jest.fn( () => ( {
		run: jest.fn(),
		retry: jest.fn(),
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
