/**
 * External dependencies
 */
import React from 'react';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';

jest.mock( '../../../data/api', () => ( {
	prepare: jest.fn(),
} ) );

/**
 * Internal dependencies
 */
import { prepare } from '../../../data/api';
import UploadStep, { isCsvLikeFile } from '../upload-step';
import {
	createInitialState,
	type ImporterAction,
} from '../../../hooks/use-importer-state';

const mockedPrepare = prepare as jest.MockedFunction< typeof prepare >;

describe( 'UploadStep', () => {
	beforeEach( () => {
		mockedPrepare.mockReset();
	} );

	it( 'disables Continue when no file is chosen', () => {
		const dispatch = jest.fn();
		render(
			<UploadStep
				state={ createInitialState() }
				dispatch={ dispatch }
				onClose={ jest.fn() }
			/>
		);

		const continueButton = screen.getByRole( 'button', {
			name: /continue/i,
		} );
		expect( continueButton ).toBeDisabled();
	} );

	it( 'calls prepare and dispatches PREPARE_OK on success', async () => {
		const state = createInitialState();
		// jsdom's File lacks text(), so stub the parts the step uses.
		state.file = {
			name: 'a.csv',
			size: 11,
			text: () => Promise.resolve( 'a,b,c\n1,2,3' ),
		} as unknown as File;

		mockedPrepare.mockResolvedValue( {
			token: 'tok',
			headers: [ 'a', 'b', 'c' ],
			sample: [ '1', '2', '3' ],
			total: 1,
			detected_mapping: { '0': 'order_number' },
			delimiter: ',',
		} );

		const dispatched: ImporterAction[] = [];
		const dispatch = jest.fn( ( action: ImporterAction ) =>
			dispatched.push( action )
		);

		render(
			<UploadStep
				state={ state }
				dispatch={ dispatch }
				onClose={ jest.fn() }
			/>
		);

		const continueButton = screen.getByRole( 'button', {
			name: /continue/i,
		} );

		fireEvent.click( continueButton );

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

	it( 'accepts CSV-like files for drag and drop and rejects other types', () => {
		expect(
			isCsvLikeFile( new File( [ '' ], 'drop.csv', { type: '' } ) )
		).toBe( true );
		expect(
			isCsvLikeFile( new File( [ '' ], 'notes.txt', { type: '' } ) )
		).toBe( true );
		expect(
			isCsvLikeFile( new File( [ '' ], 'export', { type: 'text/csv' } ) )
		).toBe( true );
		expect(
			isCsvLikeFile(
				new File( [ '' ], 'image.png', { type: 'image/png' } )
			)
		).toBe( false );
		expect(
			isCsvLikeFile(
				new File( [ '' ], 'doc.pdf', { type: 'application/pdf' } )
			)
		).toBe( false );
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

		const dispatched: ImporterAction[] = [];
		const dispatch = jest.fn( ( action: ImporterAction ) =>
			dispatched.push( action )
		);

		render(
			<UploadStep
				state={ state }
				dispatch={ dispatch }
				onClose={ jest.fn() }
			/>
		);

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
