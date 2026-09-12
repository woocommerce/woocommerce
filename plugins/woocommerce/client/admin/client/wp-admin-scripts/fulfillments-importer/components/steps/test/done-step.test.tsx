/**
 * External dependencies
 */
import React from 'react';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';

jest.mock( '../../importer-summary', () => () => (
	<div>SUMMARY_PANEL_STUB</div>
) );

jest.mock( '../../../utils/csv', () => ( {
	downloadCsv: jest.fn(),
	buildFailedRowsCsv: jest.fn( () => 'CSV_CONTENT' ),
} ) );

jest.mock( '../../../data/api', () => ( {
	prepare: jest.fn(),
} ) );

/**
 * Internal dependencies
 */
import DoneStep from '../done-step';
import { prepare } from '../../../data/api';
import { buildFailedRowsCsv, downloadCsv } from '../../../utils/csv';
import {
	createInitialState,
	type ImporterAction,
	type ImporterState,
} from '../../../hooks/use-importer-state';
import type { ImporterRowResult } from '../../../data/types';

const mockedPrepare = prepare as jest.MockedFunction< typeof prepare >;
const mockedDownloadCsv = downloadCsv as jest.MockedFunction<
	typeof downloadCsv
>;
const mockedBuildFailedRowsCsv = buildFailedRowsCsv as jest.MockedFunction<
	typeof buildFailedRowsCsv
>;

const FAILED_ROWS: ImporterRowResult[] = [
	{
		row: 3,
		status: 'failed',
		code: 'order_not_found',
		message: 'Order not found for order number "x".',
		order_number: 'x',
	},
	{
		row: 5,
		status: 'failed',
		code: 'order_not_found',
		message: 'Order not found for order number "y".',
		order_number: 'y',
	},
];

function buildFailedState( overrides: Partial< ImporterState > = {} ) {
	const state = createInitialState();
	state.step = 'done';
	state.file = new File( [ 'a,b,c' ], 'orders.csv' );
	state.fileText = 'a,b,c\n1,2,3\n';
	state.delimiter = ',';
	state.summary = {
		created: 0,
		updated: 0,
		skipped: 0,
		failed: 2,
		notified: 0,
		rows: FAILED_ROWS,
	};
	return Object.assign( state, overrides );
}

function renderStep(
	state: ImporterState,
	dispatched: ImporterAction[] = [],
	onClose = jest.fn()
) {
	render(
		<DoneStep
			state={ state }
			dispatch={ ( action: ImporterAction ) => {
				dispatched.push( action );
			} }
			onClose={ onClose }
		/>
	);
}

describe( 'DoneStep', () => {
	beforeEach( () => {
		mockedPrepare.mockReset();
		mockedDownloadCsv.mockClear();
		mockedBuildFailedRowsCsv.mockClear();
	} );

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

		const dispatched: ImporterAction[] = [];
		const onClose = jest.fn();
		renderStep( state, dispatched, onClose );

		expect( screen.getByText( 'SUMMARY_PANEL_STUB' ) ).toBeInTheDocument();

		fireEvent.click(
			screen.getByRole( 'button', { name: /import another file/i } )
		);
		expect( dispatched ).toContainEqual( { type: 'RESET' } );

		fireEvent.click( screen.getByRole( 'button', { name: /^done$/i } ) );
		expect( onClose ).toHaveBeenCalled();
	} );

	it( 'offers "Download failed rows" as the main action when some rows failed', () => {
		const state = buildFailedState();
		state.summary = { ...state.summary!, created: 2 };

		renderStep( state );

		expect(
			screen.getByRole( 'button', { name: /download failed rows/i } )
		).toBeEnabled();
		expect(
			screen.queryByRole( 'button', { name: /^done$/i } )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: /back to mapping/i } )
		).not.toBeInTheDocument();
	} );

	it( 'disables the download when the row details are missing', () => {
		const state = buildFailedState();
		state.summary = { ...state.summary!, created: 2, rows: [] };

		renderStep( state );

		expect(
			screen.getByRole( 'button', { name: /download failed rows/i } )
		).toBeDisabled();
	} );

	it( 'builds the export from the cached file text and the failed rows only', async () => {
		const state = buildFailedState();
		state.summary = {
			...state.summary!,
			rows: [
				...FAILED_ROWS,
				{
					row: 2,
					status: 'created',
					message: 'Fulfillment created.',
					order_number: '10',
					order_id: 10,
				},
			],
		};

		renderStep( state );
		fireEvent.click(
			screen.getByRole( 'button', { name: /download failed rows/i } )
		);

		await waitFor( () => {
			expect( mockedBuildFailedRowsCsv ).toHaveBeenCalledWith(
				state.fileText,
				',',
				FAILED_ROWS
			);
			expect( mockedDownloadCsv ).toHaveBeenCalledWith(
				'orders-failed-rows.csv',
				'CSV_CONTENT'
			);
		} );
	} );

	it( 'dispatches ERROR when reading the file for the export fails', async () => {
		const state = buildFailedState( { fileText: null } );
		state.file = {
			name: 'orders.csv',
			text: () => Promise.reject( new Error( 'gone' ) ),
		} as unknown as File;

		const dispatched: ImporterAction[] = [];
		renderStep( state, dispatched );
		fireEvent.click(
			screen.getByRole( 'button', { name: /download failed rows/i } )
		);

		await waitFor( () => {
			expect(
				dispatched.find( ( a ) => a.type === 'ERROR' )
			).toBeTruthy();
		} );
		expect( mockedDownloadCsv ).not.toHaveBeenCalled();
	} );

	it( 'offers "Back to mapping" and an explanatory notice when nothing imported', () => {
		renderStep( buildFailedState() );

		expect(
			screen.getByRole( 'button', { name: /back to mapping/i } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: /download failed rows/i } )
		).toBeEnabled();
		// All failures are order_not_found, so the mapping hint shows.
		expect(
			screen.getAllByText( /failed to find its order/ ).length
		).toBeGreaterThan( 0 );
	} );

	it( 'shows the generic notice when failures are not all order_not_found', () => {
		const state = buildFailedState();
		state.summary = {
			...state.summary!,
			rows: [
				FAILED_ROWS[ 0 ],
				{
					row: 4,
					status: 'failed',
					code: 'missing_tracking_number',
					message: 'Missing tracking number.',
					order_number: '12',
				},
			],
		};

		renderStep( state );

		expect(
			screen.getAllByText( /fix the file or the mapping/ ).length
		).toBeGreaterThan( 0 );
		expect(
			screen.queryByText( /failed to find its order/ )
		).not.toBeInTheDocument();
	} );

	it( 'restages the kept file when "Back to mapping" is clicked', async () => {
		mockedPrepare.mockResolvedValue( {
			token: 'fresh',
			headers: [ 'a', 'b', 'c' ],
			sample: [ '1', '2', '3' ],
			total: 2,
			detected_mapping: {},
			delimiter: ',',
		} );

		const state = buildFailedState( {
			notifyCustomer: true,
			updateExisting: false,
		} );
		const dispatched: ImporterAction[] = [];
		renderStep( state, dispatched );

		fireEvent.click(
			screen.getByRole( 'button', { name: /back to mapping/i } )
		);

		await waitFor( () => {
			expect( mockedPrepare ).toHaveBeenCalledWith( {
				file: state.file,
				delimiter: ',',
				notifyCustomer: true,
				updateExisting: false,
			} );
			expect(
				dispatched.find( ( a ) => a.type === 'PREPARE_OK' )
			).toBeTruthy();
		} );
		expect( dispatched[ 0 ] ).toEqual( { type: 'SET_BUSY', value: true } );
	} );

	it( 'dispatches ERROR when restaging fails', async () => {
		mockedPrepare.mockRejectedValue( {
			code: 'err',
			message: 'Upload failed',
			data: { status: 500 },
		} );

		const dispatched: ImporterAction[] = [];
		renderStep( buildFailedState(), dispatched );

		fireEvent.click(
			screen.getByRole( 'button', { name: /back to mapping/i } )
		);

		await waitFor( () => {
			const error = dispatched.find( ( a ) => a.type === 'ERROR' ) as
				| Extract< ImporterAction, { type: 'ERROR' } >
				| undefined;
			expect( error?.message ).toBe( 'Upload failed' );
		} );
	} );
} );
