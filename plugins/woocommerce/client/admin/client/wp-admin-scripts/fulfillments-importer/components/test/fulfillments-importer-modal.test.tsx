/**
 * External dependencies
 */
import React from 'react';
import { fireEvent, render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import type { StepComponentProps } from '../steps/types';

// The upload stub drives the reducer straight to the import step with a
// complete mapping, so the shell's close rules can be exercised there.
jest.mock(
	'../steps/upload-step',
	() =>
		( { dispatch }: StepComponentProps ) => (
			<div>
				UPLOAD_STEP_STUB
				<button
					onClick={ () => {
						dispatch( {
							type: 'PREPARE_OK',
							payload: {
								token: 'tok',
								headers: [ 'order', 'tracking', 'carrier' ],
								sample: [ '1', 'T-1', 'UPS' ],
								total: 1,
								detected_mapping: {
									'0': 'order_number',
									'1': 'tracking_number',
									'2': 'shipment_provider',
								},
								delimiter: ',',
							},
						} );
						dispatch( { type: 'GO_IMPORT' } );
					} }
				>
					stub-go-import
				</button>
			</div>
		)
);
jest.mock( '../steps/mapping-step', () => () => <div>MAPPING_STEP_STUB</div> );
jest.mock(
	'../steps/import-step',
	() =>
		( { dispatch, onClose }: StepComponentProps ) => (
			<div>
				IMPORT_STEP_STUB
				<button onClick={ onClose }>stub-close</button>
				<button
					onClick={ () =>
						dispatch( { type: 'ERROR', message: 'Chunk failed.' } )
					}
				>
					stub-fail
				</button>
			</div>
		)
);
jest.mock( '../steps/done-step', () => () => <div>DONE_STEP_STUB</div> );

/**
 * Internal dependencies
 */
import FulfillmentsImporterModal from '../fulfillments-importer-modal';

describe( 'FulfillmentsImporterModal shell', () => {
	it( 'renders nothing when closed', () => {
		const { container } = render(
			<FulfillmentsImporterModal isOpen={ false } onClose={ () => {} } />
		);
		expect( container.firstChild ).toBeNull();
	} );

	it( 'renders the upload step by default when opened', () => {
		render(
			<FulfillmentsImporterModal isOpen={ true } onClose={ () => {} } />
		);
		expect( screen.getByText( 'UPLOAD_STEP_STUB' ) ).toBeInTheDocument();
	} );

	it( 'exposes the stepper with upload as the current step on first open', () => {
		render(
			<FulfillmentsImporterModal isOpen={ true } onClose={ () => {} } />
		);
		const current = document.querySelector( '[aria-current="step"]' );
		expect( current?.textContent ).toContain( 'Upload' );
	} );

	it( 'blocks closing during the import step until an error is surfaced', () => {
		const onClose = jest.fn();
		render(
			<FulfillmentsImporterModal isOpen={ true } onClose={ onClose } />
		);

		fireEvent.click( screen.getByText( 'stub-go-import' ) );
		expect( screen.getByText( 'IMPORT_STEP_STUB' ) ).toBeInTheDocument();
		// The header hides its action while the import runs.
		expect( screen.queryByRole( 'button', { name: 'Cancel' } ) ).toBeNull();

		fireEvent.click( screen.getByText( 'stub-close' ) );
		expect( onClose ).not.toHaveBeenCalled();

		fireEvent.click( screen.getByText( 'stub-fail' ) );
		expect(
			screen.getByRole( 'button', { name: 'Cancel' } )
		).toBeInTheDocument();

		fireEvent.click( screen.getByText( 'stub-close' ) );
		expect( onClose ).toHaveBeenCalledTimes( 1 );
	} );
} );
