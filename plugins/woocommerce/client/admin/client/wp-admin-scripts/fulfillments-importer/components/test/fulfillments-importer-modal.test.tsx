/**
 * External dependencies
 */
import React from 'react';
import { render, screen } from '@testing-library/react';

jest.mock( '../steps/upload-step', () => () => (
	<div>UPLOAD_STEP_STUB</div>
) );
jest.mock( '../steps/mapping-step', () => () => (
	<div>MAPPING_STEP_STUB</div>
) );
jest.mock( '../steps/import-step', () => () => (
	<div>IMPORT_STEP_STUB</div>
) );
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
		expect(
			screen.getByText( 'UPLOAD_STEP_STUB' )
		).toBeInTheDocument();
	} );

	it( 'exposes the stepper with upload as the current step on first open', () => {
		render(
			<FulfillmentsImporterModal isOpen={ true } onClose={ () => {} } />
		);
		const current = document.querySelector( '[aria-current="step"]' );
		expect( current?.textContent ).toContain( 'Upload' );
	} );
} );
