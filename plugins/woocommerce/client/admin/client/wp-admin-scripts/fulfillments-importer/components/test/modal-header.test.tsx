/**
 * External dependencies
 */
import React from 'react';
import { fireEvent, render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ModalHeader from '../modal-header';

describe( 'ModalHeader', () => {
	it( 'shows Cancel before the import runs', () => {
		const onClose = jest.fn();
		render(
			<ModalHeader
				currentStep="upload"
				title="Import fulfillments"
				onClose={ onClose }
				canClose={ true }
			/>
		);

		fireEvent.click( screen.getByRole( 'button', { name: /cancel/i } ) );
		expect( onClose ).toHaveBeenCalled();
	} );

	it( 'hides the action while the import is running', () => {
		render(
			<ModalHeader
				currentStep="import"
				title="Import fulfillments"
				onClose={ jest.fn() }
				canClose={ false }
			/>
		);

		expect( screen.queryByRole( 'button' ) ).not.toBeInTheDocument();
	} );

	it( 'shows Close import on the summary', () => {
		render(
			<ModalHeader
				currentStep="done"
				title="Import fulfillments"
				onClose={ jest.fn() }
				canClose={ true }
			/>
		);

		expect(
			screen.getByRole( 'button', { name: /close import/i } )
		).toBeInTheDocument();
	} );
} );
