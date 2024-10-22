/**
 * External dependencies
 */
import React from 'react';
import { render, fireEvent, screen } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';

/**
 * Internal dependencies
 */
import { ConfirmationModal } from '../confirmation-modal';

// Mock the necessary external dependencies
jest.mock( '@wordpress/components', () => ( {
	Modal: jest.fn( ( { title, children, onRequestClose } ) => (
		<div>
			<div>{ title }</div>
			<div>{ children }</div>
			<button onClick={ onRequestClose }>Close</button>
		</div>
	) ),
	Button: jest.fn( ( { children, onClick } ) => (
		<button onClick={ onClick }>{ children }</button>
	) ),
} ) );

describe( 'ConfirmationModal', () => {
	let formRef, saveButtonRef;

	const mockSelectComingSoon = ( value ) => {
		// Set up form data
		const input = document.createElement( 'input' );
		input.name = 'woocommerce_coming_soon';
		input.value = value;
		formRef.current.appendChild( input );
	};

	const fireSubmitEvent = () => {
		// Simulate form submission
		const submitEvent = new Event( 'submit', {
			bubbles: true,
			cancelable: true,
		} );
		fireEvent( formRef.current, submitEvent );
	};

	beforeEach( () => {
		formRef = { current: document.createElement( 'form' ) };
		saveButtonRef = { current: document.createElement( 'button' ) };
		formRef.current.appendChild( saveButtonRef.current );
		document.body.appendChild( formRef.current );
	} );

	afterEach( () => {
		document.body.removeChild( formRef.current );
	} );

	it( 'should prompt the modal if current setting is live and submit the form', () => {
		const currentSetting = { woocommerce_coming_soon: 'no' };

		render(
			<ConfirmationModal
				formRef={ formRef }
				saveButtonRef={ saveButtonRef }
				currentSetting={ currentSetting }
			/>
		);

		const submitListener = jest.fn();
		formRef.current.onsubmit = submitListener;

		mockSelectComingSoon( 'yes' );
		fireSubmitEvent();

		// Confirm modal is prompted
		expect(
			screen.getByText( 'Confirm switch to ‘Coming soon’ mode' )
		).toBeInTheDocument();

		// Simulate confirming submission
		fireEvent.click( screen.getByText( 'Switch' ) );

		// Ensure the form is submitted
		expect( submitListener ).toHaveBeenCalled();
	} );

	it( 'should prompt the modal if current setting is not set', () => {
		render(
			<ConfirmationModal
				formRef={ formRef }
				saveButtonRef={ saveButtonRef }
				currentSetting={ null }
			/>
		);

		mockSelectComingSoon( 'yes' );
		fireSubmitEvent();

		// Confirm that the modal is not prompted
		expect(
			screen.queryByText( 'Confirm switch to ‘Coming soon’ mode' )
		).toBeInTheDocument();
	} );

	it( 'should not prompt the modal if current setting is already "coming soon"', () => {
		const currentSetting = { woocommerce_coming_soon: 'yes' };

		render(
			<ConfirmationModal
				formRef={ formRef }
				saveButtonRef={ saveButtonRef }
				currentSetting={ currentSetting }
			/>
		);

		mockSelectComingSoon( 'yes' );
		fireSubmitEvent();

		// Confirm that the modal is not prompted
		expect(
			screen.queryByText( 'Confirm switch to ‘Coming soon’ mode' )
		).not.toBeInTheDocument();
	} );

	it( 'should close the modal on cancel', () => {
		const currentSetting = { woocommerce_coming_soon: 'no' };

		render(
			<ConfirmationModal
				formRef={ formRef }
				saveButtonRef={ saveButtonRef }
				currentSetting={ currentSetting }
			/>
		);

		mockSelectComingSoon( 'yes' );
		fireSubmitEvent();

		// Confirm modal is prompted
		expect(
			screen.getByText( 'Confirm switch to ‘Coming soon’ mode' )
		).toBeInTheDocument();

		// Simulate canceling the modal
		fireEvent.click( screen.getByText( 'Cancel' ) );

		// Confirm that the modal is closed
		expect(
			screen.queryByText( 'Confirm switch to ‘Coming soon’ mode' )
		).not.toBeInTheDocument();
	} );

	it( 'should handle the save button correctly', () => {
		const currentSetting = { woocommerce_coming_soon: 'no' };
		saveButtonRef.current.name = 'save';
		saveButtonRef.current.value = 'Save changes';

		render(
			<ConfirmationModal
				formRef={ formRef }
				saveButtonRef={ saveButtonRef }
				currentSetting={ currentSetting }
			/>
		);

		mockSelectComingSoon( 'yes' );
		fireSubmitEvent();

		// Confirm modal is prompted
		expect(
			screen.getByText( 'Confirm switch to ‘Coming soon’ mode' )
		).toBeInTheDocument();

		// Simulate confirming submission
		fireEvent.click( screen.getByText( 'Switch' ) );

		// Check that the hidden input with "save" has been added to the form
		const hiddenInput =
			formRef.current.querySelector( 'input[name="save"]' );
		expect( hiddenInput ).toBeInTheDocument();
		expect( hiddenInput.value ).toBe( 'Save changes' );
	} );
} );
