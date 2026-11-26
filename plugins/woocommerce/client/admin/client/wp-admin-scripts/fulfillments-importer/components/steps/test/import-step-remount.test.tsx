/**
 * External dependencies
 */
import React from 'react';
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

jest.mock( '../../../data/api', () => ( {
	runChunk: jest.fn(),
} ) );

/**
 * Internal dependencies
 */
import ImportStep from '../import-step';
import { runChunk } from '../../../data/api';
import { useImporterState } from '../../../hooks/use-importer-state';
import type { ImporterAction } from '../../../hooks/use-importer-state';
import type { RunChunkResponse } from '../../../data/types';

const mockedRunChunk = runChunk as jest.MockedFunction< typeof runChunk >;

const TOKEN_INVALID = {
	code: 'woocommerce_fulfillments_import_token_invalid',
	message: 'Import session is missing or has expired.',
	data: { status: 400 },
};

// The a11y speak regions repeat the text, so only the notice paragraph counts.
const IN_NOTICE = { selector: '.components-notice p' };

const MAY_HAVE_FINISHED =
	'The import session ended before the last batch of rows was confirmed, so the import may have finished. Check your orders before you upload the file again.';

function chunk( processed: number ): RunChunkResponse {
	return {
		processed,
		total: 6,
		done: false,
		counts: {
			created: processed,
			updated: 0,
			skipped: 0,
			failed: 0,
			notified: 0,
		},
		rows: [],
	};
}

/**
 * Keeps the wizard state while the import step is unmounted and mounted
 * again, the way the wizard modal does.
 */
function Harness( {
	stepKey,
	showStep,
	dispatchRef,
}: {
	stepKey: number;
	showStep: boolean;
	dispatchRef: { current: React.Dispatch< ImporterAction > | null };
} ) {
	const [ state, dispatch ] = useImporterState();
	dispatchRef.current = dispatch;
	return showStep && state.step === 'import' ? (
		<ImportStep
			key={ stepKey }
			state={ state }
			dispatch={ dispatch }
			onClose={ jest.fn() }
		/>
	) : null;
}

function setUp() {
	const dispatchRef: {
		current: React.Dispatch< ImporterAction > | null;
	} = { current: null };
	const utils = render(
		<Harness stepKey={ 1 } showStep dispatchRef={ dispatchRef } />
	);
	const startImport = () =>
		act( async () => {
			dispatchRef.current?.( {
				type: 'PREPARE_OK',
				payload: {
					token: 'tok',
					headers: [ 'Order', 'Tracking', 'Provider' ],
					sample: [],
					total: 6,
					detected_mapping: {
						0: 'order_number',
						1: 'tracking_number',
						2: 'shipment_provider',
					},
					delimiter: ',',
				},
			} );
			dispatchRef.current?.( { type: 'GO_IMPORT' } );
		} );
	const remount = () => {
		utils.rerender(
			<Harness
				stepKey={ 1 }
				showStep={ false }
				dispatchRef={ dispatchRef }
			/>
		);
		utils.rerender(
			<Harness stepKey={ 2 } showStep dispatchRef={ dispatchRef } />
		);
	};
	return { startImport, remount };
}

describe( 'ImportStep mounted again with the wizard state', () => {
	beforeEach( () => {
		mockedRunChunk.mockReset();
		window.wcFulfillmentsImporterSettings = {
			importRoute: '/wc/v3/fulfillments/import',
			chunkSize: '2',
			maxRows: '5000',
			providers: [],
		};
	} );

	afterEach( () => {
		delete window.wcFulfillmentsImporterSettings;
	} );

	it( 'waits for Retry instead of resending a failed chunk on its own', async () => {
		mockedRunChunk
			.mockResolvedValueOnce( chunk( 2 ) )
			.mockRejectedValueOnce( new Error( 'Failed to fetch' ) );
		const { startImport, remount } = setUp();

		await startImport();
		await screen.findByText( 'Failed to fetch', IN_NOTICE );

		remount();

		expect(
			screen.getByText( 'Failed to fetch', IN_NOTICE )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Retry' } )
		).toBeInTheDocument();
		expect( mockedRunChunk ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'still warns that the import may have finished after a lost response', async () => {
		mockedRunChunk
			.mockResolvedValueOnce( chunk( 2 ) )
			.mockRejectedValueOnce( new Error( 'Failed to fetch' ) )
			.mockRejectedValueOnce( TOKEN_INVALID );
		const { startImport, remount } = setUp();

		await startImport();
		await screen.findByText( 'Failed to fetch', IN_NOTICE );
		remount();

		await act( async () => {
			screen.getByRole( 'button', { name: 'Retry' } ).click();
		} );

		await screen.findByText( MAY_HAVE_FINISHED, IN_NOTICE );
		expect( mockedRunChunk ).toHaveBeenCalledTimes( 3 );
		expect( mockedRunChunk.mock.calls[ 2 ][ 0 ].offset ).toBe( 2 );
	} );

	it( 'keeps keyboard focus in the step when Retry removes the notice', async () => {
		mockedRunChunk
			.mockResolvedValueOnce( chunk( 2 ) )
			.mockRejectedValueOnce( new Error( 'Failed to fetch' ) )
			.mockImplementationOnce( () => new Promise( () => {} ) );
		const { startImport } = setUp();

		await startImport();
		await screen.findByText( 'Failed to fetch', IN_NOTICE );

		screen.getByRole( 'button', { name: 'Retry' } ).focus();
		await userEvent.keyboard( '{Enter}' );

		await waitFor( () =>
			expect( mockedRunChunk ).toHaveBeenCalledTimes( 3 )
		);
		expect(
			screen.queryByText( 'Failed to fetch', IN_NOTICE )
		).not.toBeInTheDocument();
		const heading = screen.getByRole( 'heading', {
			name: 'Importing fulfillments',
		} );
		expect( heading.ownerDocument.activeElement ).toBe( heading );
	} );

	it( 'shows the expired session as is on a fresh import', async () => {
		mockedRunChunk.mockRejectedValueOnce( TOKEN_INVALID );
		const { startImport } = setUp();

		await startImport();

		await screen.findByText( TOKEN_INVALID.message, IN_NOTICE );
		expect(
			screen.queryByText( MAY_HAVE_FINISHED, IN_NOTICE )
		).not.toBeInTheDocument();
	} );

	it( 'forgets the lost response after Start over', async () => {
		mockedRunChunk
			.mockResolvedValueOnce( chunk( 2 ) )
			.mockRejectedValueOnce( new Error( 'Failed to fetch' ) )
			.mockRejectedValueOnce( TOKEN_INVALID );
		const { startImport, remount } = setUp();

		await startImport();
		await screen.findByText( 'Failed to fetch', IN_NOTICE );
		remount();

		await act( async () => {
			screen.getByRole( 'button', { name: 'Start over' } ).click();
		} );
		await startImport();

		await screen.findByText( TOKEN_INVALID.message, IN_NOTICE );
		expect( mockedRunChunk.mock.calls[ 2 ][ 0 ].offset ).toBe( 0 );
	} );
} );
