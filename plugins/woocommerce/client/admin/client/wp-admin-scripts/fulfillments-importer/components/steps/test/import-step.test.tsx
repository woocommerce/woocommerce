/**
 * External dependencies
 */
import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';

const mockRun = jest.fn();
let mockIsRunning = true;

jest.mock( '../../../hooks/use-chunked-import', () => ( {
	useChunkedImport: jest.fn( () => ( {
		run: mockRun,
		isRunning: mockIsRunning,
	} ) ),
} ) );

/**
 * Internal dependencies
 */
import ImportStep from '../import-step';
import { useChunkedImport } from '../../../hooks/use-chunked-import';
import type { UseChunkedImportArgs } from '../../../hooks/use-chunked-import';
import { createInitialState } from '../../../hooks/use-importer-state';
import type { RunChunkResponse } from '../../../data/types';

const mockedUseChunkedImport = useChunkedImport as jest.MockedFunction<
	typeof useChunkedImport
>;

function importState( overrides = {} ) {
	return {
		...createInitialState(),
		step: 'import' as const,
		token: 'tok',
		total: 100,
		...overrides,
	};
}

function renderStep( overrides = {}, dispatch = jest.fn() ) {
	const utils = render(
		<ImportStep
			state={ importState( overrides ) }
			dispatch={ dispatch }
			onClose={ jest.fn() }
		/>
	);
	const hookArgs = mockedUseChunkedImport.mock.calls[
		mockedUseChunkedImport.mock.calls.length - 1
	][ 0 ] as UseChunkedImportArgs;
	return { ...utils, dispatch, hookArgs };
}

describe( 'ImportStep', () => {
	beforeEach( () => {
		// The a11y live regions are shared by every test in this file.
		document
			.querySelectorAll( '[id^="a11y-speak-"]' )
			.forEach( ( region ) => region.replaceChildren() );
		mockRun.mockClear();
		mockedUseChunkedImport.mockClear();
		mockIsRunning = true;
	} );

	it( 'invokes run exactly once on mount', async () => {
		const { rerender } = renderStep();

		await waitFor( () => {
			expect( mockRun ).toHaveBeenCalledTimes( 1 );
		} );

		// Re-render with updated state should not re-trigger run.
		rerender(
			<ImportStep
				state={ importState( { processed: 10 } ) }
				dispatch={ jest.fn() }
				onClose={ jest.fn() }
			/>
		);
		expect( mockRun ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not start the loop when it mounts with an error showing', () => {
		mockIsRunning = false;
		renderStep( { processed: 2, error: 'Failed to fetch' } );

		expect( mockRun ).not.toHaveBeenCalled();
		expect(
			screen.getByRole( 'button', { name: /retry/i } )
		).toBeInTheDocument();
	} );

	it( 'passes the session and run settings to the import loop', () => {
		const { hookArgs } = renderStep( {
			processed: 40,
			mapping: { 0: 'order_number' },
			notifyCustomer: true,
			updateExisting: false,
		} );

		expect( hookArgs ).toEqual(
			expect.objectContaining( {
				token: 'tok',
				total: 100,
				processed: 40,
				mapping: { 0: 'order_number' },
				notifyCustomer: true,
				updateExisting: false,
			} )
		);
	} );

	it( 'dispatches the loop callbacks to the reducer', () => {
		const { hookArgs, dispatch } = renderStep();
		const response = { processed: 1 } as RunChunkResponse;
		const summary = {
			created: 1,
			updated: 0,
			skipped: 0,
			failed: 0,
			notified: 0,
			rows: [],
		};

		hookArgs.onChunk?.( response );
		hookArgs.onFinish?.( summary );
		hookArgs.onError?.( 'Session expired.', true );

		expect( dispatch.mock.calls ).toEqual( [
			[ { type: 'CHUNK_OK', payload: response } ],
			[ { type: 'FINISH', summary } ],
			[
				{
					type: 'ERROR',
					message: 'Session expired.',
					sessionEnded: true,
				},
			],
		] );
	} );

	it.each( [
		[ 25, 100, '25' ],
		[ 995, 1000, '99' ],
		[ 120, 100, '100' ],
		[ 0, 0, '0' ],
	] )(
		'shows %d of %d rows as a progress value of %s',
		( processed, total, expected ) => {
			renderStep( { processed, total } );

			// The design system ProgressBar renders a native <progress> element,
			// which exposes its position through the value attribute.
			const bar = screen.getByRole( 'progressbar', {
				name: /import progress/i,
			} );
			expect( bar.getAttribute( 'value' ) ).toBe( expected );
		}
	);

	it( 'announces the processed count with a plural form', () => {
		const { rerender } = renderStep( { processed: 0, total: 1 } );
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			/^Processed 0 of 1 row$/
		);

		rerender(
			<ImportStep
				state={ importState( { processed: 1200, total: 5000 } ) }
				dispatch={ jest.fn() }
				onClose={ jest.fn() }
			/>
		);
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			/^Processed 1,200 of 5,000 rows$/
		);
	} );

	it( 'keeps the live progress regions outside a busy region while the loop runs', () => {
		// Screen readers may hold back announcements inside a busy region.
		renderStep( { processed: 10 } );

		expect(
			screen.getByRole( 'status' ).closest( '[aria-busy="true"]' )
		).toBeNull();
		expect(
			document
				.querySelector( '.woocommerce-fulfillment-importer-counts' )
				?.closest( '[aria-busy="true"]' )
		).toBeNull();
	} );

	it( 'announces progress only through the status line', () => {
		// Bare counter numbers spoken after each chunk would have no labels.
		const { container } = renderStep( { processed: 10 } );

		const regions = container.querySelectorAll(
			'[aria-live], [role="status"], [role="alert"], [role="log"]'
		);
		expect( regions ).toHaveLength( 1 );
		expect( regions[ 0 ] ).toBe( screen.getByRole( 'status' ) );
	} );

	it( 'shows all five counters, zeros included', () => {
		renderStep( {
			counts: {
				created: 1,
				updated: 2,
				skipped: 0,
				failed: 4,
				notified: 1500,
			},
		} );

		const values = Object.fromEntries(
			screen
				.getAllByRole( 'term' )
				.map( ( term ) => [
					term.textContent,
					term.nextElementSibling?.textContent,
				] )
		);
		expect( values ).toEqual( {
			'Fulfillment created': '1',
			'Fulfillments updated': '2',
			'Rows skipped': '0',
			'Rows failed': '4',
			'Customers notified': '1,500',
		} );
	} );

	it( 'Retry clears the error and resumes the loop', () => {
		mockIsRunning = false;
		const { dispatch } = renderStep( {
			processed: 10,
			error: 'Network blew up',
		} );
		mockRun.mockClear();

		screen.getByRole( 'button', { name: /retry/i } ).click();

		expect( dispatch ).toHaveBeenCalledWith( { type: 'CLEAR_ERROR' } );
		expect( mockRun ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'disables Retry and Start over while the loop runs', () => {
		renderStep( { error: 'Network blew up' } );

		expect(
			screen.getByRole( 'button', { name: /retry/i } )
		).toBeDisabled();
		expect(
			screen.getByRole( 'button', { name: /start over/i } )
		).toBeDisabled();
	} );

	it( 'offers Start over next to Retry for an error that may repeat', () => {
		mockIsRunning = false;
		const { dispatch } = renderStep( {
			error: 'The offset does not match.',
		} );

		expect(
			screen.getByRole( 'button', { name: /retry/i } )
		).toBeInTheDocument();
		screen.getByRole( 'button', { name: /start over/i } ).click();
		expect( dispatch ).toHaveBeenCalledWith( { type: 'RESET' } );
	} );

	it( 'speaks only the error text, not the button labels', () => {
		mockIsRunning = false;
		// A text no other test speaks: @wordpress/a11y marks a repeated message.
		renderStep( { error: 'Only this test shows this error.' } );

		// Notice speaks through the shared assertive region of @wordpress/a11y.
		expect(
			document.getElementById( 'a11y-speak-assertive' )?.textContent
		).toBe( 'Only this test shows this error.' );
	} );

	it( 'offers only Start over once the session has ended', () => {
		mockIsRunning = false;
		const { dispatch } = renderStep( {
			processed: 10,
			error: 'Import session is missing or has expired.',
			sessionEnded: true,
		} );

		expect(
			screen.queryByRole( 'button', { name: /retry/i } )
		).not.toBeInTheDocument();

		screen.getByRole( 'button', { name: /start over/i } ).click();
		expect( dispatch ).toHaveBeenCalledWith( { type: 'RESET' } );
	} );
} );
