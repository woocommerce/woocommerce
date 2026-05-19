/**
 * External dependencies
 */
import React from 'react';
import { fireEvent, render, screen } from '@testing-library/react';

jest.mock( '../../../data/api', () => ( {
	prepare: jest.fn(),
} ) );

/**
 * Internal dependencies
 */
import { prepare } from '../../../data/api';
import UploadStep from '../upload-step';
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
		state.file = new File( [ 'a,b,c\n1,2,3' ], 'a.csv', {
			type: 'text/csv',
		} );

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
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

		expect( mockedPrepare ).toHaveBeenCalledTimes( 1 );
		const prepareOk = dispatched.find( ( a ) => a.type === 'PREPARE_OK' );
		expect( prepareOk ).toBeTruthy();
	} );
} );
