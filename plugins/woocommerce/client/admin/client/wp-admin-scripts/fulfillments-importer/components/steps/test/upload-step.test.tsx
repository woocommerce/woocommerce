/**
 * External dependencies
 */
import React from 'react';
import {
	act,
	fireEvent,
	render,
	screen,
	waitFor,
} from '@testing-library/react';

jest.mock( '../../../data/api', () => ( {
	prepare: jest.fn(),
} ) );

// The real DropZone needs drag events jsdom cannot deliver; capture its
// handler so tests can hand it files directly.
const mockDropZone: { onFilesDrop?: ( files: File[] ) => void } = {};
jest.mock( '@wordpress/components', () => {
	const actual = jest.requireActual( '@wordpress/components' );
	return {
		...actual,
		DropZone: ( props: { onFilesDrop: ( files: File[] ) => void } ) => {
			mockDropZone.onFilesDrop = props.onFilesDrop;
			return null;
		},
	};
} );

/**
 * Internal dependencies
 */
import { prepare } from '../../../data/api';
import UploadStep, { isCsvLikeFile } from '../upload-step';
import {
	createInitialState,
	importerReducer,
	type ImporterAction,
	type ImporterState,
} from '../../../hooks/use-importer-state';
import type { PrepareResponse } from '../../../data/types';

const mockedPrepare = prepare as jest.MockedFunction< typeof prepare >;

const INVALID_TYPE_MESSAGE =
	'Invalid file type. The importer supports CSV and TXT file formats.';

// Notice also announces its text through the a11y speak region, so scope the
// lookup to the notice itself.
function queryError() {
	return screen.queryByText( INVALID_TYPE_MESSAGE, {
		selector: '.components-notice__content',
	} );
}

function renderStep( state = createInitialState() ) {
	const dispatched: ImporterAction[] = [];
	const dispatch = jest.fn( ( action: ImporterAction ) =>
		dispatched.push( action )
	);
	const step = ( next: ImporterState ) => (
		<UploadStep
			state={ next }
			dispatch={ dispatch }
			onClose={ jest.fn() }
		/>
	);
	const { rerender, unmount } = render( step( state ) );
	return {
		dispatch,
		dispatched,
		unmount,
		rerender: ( next: ImporterState ) => rerender( step( next ) ),
	};
}

// jsdom's File lacks text(), so stub the parts the step uses.
function stateWithFile(): ImporterState {
	const state = createInitialState();
	state.file = {
		name: 'a.csv',
		size: 11,
		text: () => Promise.resolve( 'a,b,c\n1,2,3' ),
	} as unknown as File;
	return state;
}

const PREPARED: PrepareResponse = {
	token: 'tok',
	headers: [ 'a', 'b', 'c' ],
	sample: [ '1', '2', '3' ],
	total: 1,
	detected_mapping: { '0': 'order_number' },
	delimiter: ',',
};

/**
 * Queue a prepare call that stays pending until resolved here. With
 * rejectOnAbort it rejects with an AbortError when its signal is aborted,
 * like apiFetch does.
 */
function deferredPrepare( rejectOnAbort = false ) {
	let resolve!: ( response: PrepareResponse ) => void;
	const seen: { signal?: AbortSignal } = {};
	const promise = new Promise< PrepareResponse >( ( res, rej ) => {
		resolve = res;
		mockedPrepare.mockImplementationOnce( ( args ) => {
			seen.signal = args.signal;
			if ( rejectOnAbort ) {
				args.signal?.addEventListener( 'abort', () =>
					rej( new DOMException( 'Aborted', 'AbortError' ) )
				);
			}
			return promise;
		} );
	} );
	return { resolve, seen };
}

// Let the step's awaited promise chain settle inside act().
async function flush() {
	await act( async () => {
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
	} );
}

async function clickContinue() {
	fireEvent.click( screen.getByRole( 'button', { name: /continue/i } ) );
	await waitFor( () => expect( mockedPrepare ).toHaveBeenCalledTimes( 1 ) );
}

function pickFile( file: File ) {
	const input = document.querySelector( 'input[type="file"]' );
	if ( ! input ) {
		throw new Error( 'The file input did not render.' );
	}
	fireEvent.change( input, { target: { files: [ file ] } } );
}

const PNG = new File( [ '' ], 'image.png', { type: 'image/png' } );
const CSV = new File( [ 'a,b\n1,2' ], 'rows.csv', { type: 'text/csv' } );

describe( 'UploadStep', () => {
	beforeEach( () => {
		mockedPrepare.mockReset();
	} );

	afterEach( () => {
		delete window.wcFulfillmentsImporterSettings;
	} );

	it( 'disables Continue when no file is chosen', () => {
		renderStep();

		expect(
			screen.getByRole( 'button', { name: /continue/i } )
		).toBeDisabled();
	} );

	it( 'rejects a non-CSV file from the picker', () => {
		const { dispatched } = renderStep();

		pickFile( PNG );

		expect( queryError() ).toBeInTheDocument();
		expect(
			dispatched.find( ( a ) => a.type === 'SET_FILE' )
		).toBeUndefined();
		expect( mockedPrepare ).not.toHaveBeenCalled();
	} );

	it( 'stages a CSV file from the picker', () => {
		const { dispatched } = renderStep();

		pickFile( CSV );

		expect( queryError() ).toBeNull();
		expect( dispatched ).toContainEqual( { type: 'SET_FILE', file: CSV } );
	} );

	it( 'rejects a non-CSV file dropped on the drop zone', () => {
		const { dispatched } = renderStep();

		act( () => {
			mockDropZone.onFilesDrop?.( [ PNG ] );
		} );

		expect( queryError() ).toBeInTheDocument();
		expect(
			dispatched.find( ( a ) => a.type === 'SET_FILE' )
		).toBeUndefined();
		expect( mockedPrepare ).not.toHaveBeenCalled();
	} );

	it( 'stages a CSV file dropped on the drop zone', () => {
		const { dispatched } = renderStep();

		act( () => {
			mockDropZone.onFilesDrop?.( [ CSV ] );
		} );

		expect( dispatched ).toContainEqual( { type: 'SET_FILE', file: CSV } );
	} );

	it( 'calls prepare and dispatches PREPARE_OK on success', async () => {
		mockedPrepare.mockResolvedValue( PREPARED );

		const { dispatched } = renderStep( stateWithFile() );

		expect( screen.getByText( 'a.csv (11 B)' ) ).toBeInTheDocument();

		fireEvent.click( screen.getByRole( 'button', { name: /continue/i } ) );

		await waitFor( () => {
			expect( mockedPrepare ).toHaveBeenCalledTimes( 1 );
			expect(
				dispatched.find( ( a ) => a.type === 'PREPARE_OK' )
			).toBeTruthy();
		} );

		// The file content is cached for the failed-rows export.
		const fileText = dispatched.find(
			( a ) => a.type === 'SET_FILE_TEXT'
		) as Extract< ImporterAction, { type: 'SET_FILE_TEXT' } > | undefined;
		expect( fileText?.text ).toBe( 'a,b,c\n1,2,3' );
	} );

	it( 'ignores a prepare response that arrives after the step unmounted', async () => {
		const pending = deferredPrepare();
		const { dispatched, unmount } = renderStep( stateWithFile() );

		await clickContinue();
		expect( pending.seen.signal?.aborted ).toBe( false );

		unmount();
		expect( pending.seen.signal?.aborted ).toBe( true );

		pending.resolve( PREPARED );
		await flush();

		expect(
			dispatched.find( ( a ) => a.type === 'PREPARE_OK' )
		).toBeUndefined();
	} );

	it( 'ignores a prepare response that arrives after the wizard was reset', async () => {
		const pending = deferredPrepare();
		const state = stateWithFile();
		const { dispatched, rerender } = renderStep( state );

		await clickContinue();

		// The modal dispatched RESET while this step stayed mounted.
		rerender( importerReducer( state, { type: 'RESET' } ) );

		pending.resolve( PREPARED );
		await flush();

		expect(
			dispatched.find( ( a ) => a.type === 'PREPARE_OK' )
		).toBeUndefined();
	} );

	it( 'does not surface an error for an aborted prepare', async () => {
		deferredPrepare( true );
		const { dispatched, unmount } = renderStep( stateWithFile() );

		await clickContinue();

		unmount();
		await flush();

		expect(
			dispatched.find( ( a ) => a.type === 'ERROR' )
		).toBeUndefined();
	} );

	it( 'aborts the pending prepare when a different file is picked', async () => {
		const pending = deferredPrepare();
		const { dispatched } = renderStep( stateWithFile() );

		await clickContinue();

		pickFile( CSV );
		expect( pending.seen.signal?.aborted ).toBe( true );
		expect( dispatched ).toContainEqual( { type: 'SET_FILE', file: CSV } );

		pending.resolve( PREPARED );
		await flush();

		expect(
			dispatched.find( ( a ) => a.type === 'PREPARE_OK' )
		).toBeUndefined();
	} );

	it( 'accepts the file types the core CSV importers accept and rejects others', () => {
		expect(
			isCsvLikeFile( new File( [ '' ], 'drop.csv', { type: '' } ) )
		).toBe( true );
		// Core's product and tax importers accept .txt as well.
		expect(
			isCsvLikeFile( new File( [ '' ], 'notes.txt', { type: '' } ) )
		).toBe( true );
		expect(
			isCsvLikeFile( new File( [ '' ], 'export', { type: 'text/csv' } ) )
		).toBe( true );
		expect( isCsvLikeFile( PNG ) ).toBe( false );
		expect(
			isCsvLikeFile(
				new File( [ '' ], 'doc.pdf', { type: 'application/pdf' } )
			)
		).toBe( false );
	} );

	it( 'shows the default row limit until the server localizes one', () => {
		renderStep();

		expect(
			screen.getByText( 'Up to 5,000 rows per file' )
		).toBeInTheDocument();
	} );

	it( 'shows the localized row limit, coercing the string wp_localize_script sends', () => {
		window.wcFulfillmentsImporterSettings = {
			importRoute: '/wc/v3/fulfillments/import',
			maxRows: '2500',
			providers: [],
		};

		renderStep();

		expect(
			screen.getByText( 'Up to 2,500 rows per file' )
		).toBeInTheDocument();
	} );

	it( 'surfaces the server error message from an apiFetch rejection', async () => {
		const state = createInitialState();
		state.file = new File( [ 'a,b,c\n1,2,3' ], 'a.csv', {
			type: 'text/csv',
		} );

		// apiFetch rejects with a plain object, not an Error instance.
		mockedPrepare.mockRejectedValue( {
			code: 'woocommerce_fulfillments_import_file_too_large',
			message:
				'The uploaded file is larger than the allowed maximum of 8 MB.',
			data: { status: 413 },
		} );

		const { dispatched } = renderStep( state );

		fireEvent.click( screen.getByRole( 'button', { name: /continue/i } ) );

		await waitFor( () => {
			const errorAction = dispatched.find(
				( a ) => a.type === 'ERROR'
			) as Extract< ImporterAction, { type: 'ERROR' } > | undefined;
			expect( errorAction?.message ).toBe(
				'The uploaded file is larger than the allowed maximum of 8 MB.'
			);
		} );
	} );
} );
