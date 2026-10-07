/**
 * External dependencies
 */
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { SettingsPaymentsCheque } from '../settings-payments-cheque';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
} ) );

const chequeSettings = {
	enabled: true,
	description: 'Take payments in person via checks.',
	settings: {
		title: { value: 'Check payments' },
		instructions: { value: 'Send the check to our address.' },
		enable_for_methods: {
			value: [ 'flat_rate:1' ],
			options: {
				'Flat rate': {
					'flat_rate:1': 'Flat rate (#1)',
				},
				'Free shipping': {
					'free_shipping:2': 'Free shipping (#2)',
				},
			},
		},
		enable_for_virtual: { value: 'yes' },
	},
};

describe( 'SettingsPaymentsCheque', () => {
	let updatePaymentGateway: jest.Mock;

	beforeEach( () => {
		updatePaymentGateway = jest.fn().mockResolvedValue( {} );
		( useDispatch as jest.Mock ).mockReturnValue( {
			createSuccessNotice: jest.fn(),
			createErrorNotice: jest.fn(),
			updatePaymentGateway,
			invalidateResolution: jest.fn(),
			invalidateResolutionForStoreSelector: jest.fn(),
		} );
		( useSelect as jest.Mock ).mockReturnValue( {
			chequeSettings,
			isLoading: false,
		} );
	} );

	it( 'renders all settings fields with stored values', () => {
		render( <SettingsPaymentsCheque /> );

		expect(
			screen.getByLabelText( 'Enable check payments' )
		).toBeChecked();
		expect( screen.getByLabelText( 'Title' ) ).toHaveValue(
			'Check payments'
		);
		expect( screen.getByLabelText( 'Description' ) ).toHaveValue(
			'Take payments in person via checks.'
		);
		expect( screen.getByLabelText( 'Instructions' ) ).toHaveValue(
			'Send the check to our address.'
		);
		expect(
			screen.getByLabelText( 'Enable for shipping methods' )
		).toBeInTheDocument();
		// The stored shipping method selection is rendered as a tag.
		expect( screen.getByText( 'Flat rate (#1)' ) ).toBeInTheDocument();
		expect(
			screen.getByLabelText( 'Accept for virtual orders' )
		).toBeChecked();
	} );

	it( 'renders placeholders while loading', () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			chequeSettings: null,
			isLoading: true,
		} );

		const { container } = render( <SettingsPaymentsCheque /> );

		expect(
			container.querySelectorAll( '.woocommerce-field-placeholder' )
				.length
		).toBeGreaterThan( 0 );
		expect( screen.queryByLabelText( 'Title' ) ).not.toBeInTheDocument();
	} );

	it( 'disables save until a change is made', () => {
		render( <SettingsPaymentsCheque /> );

		expect(
			screen.getByRole( 'button', { name: 'Save changes' } )
		).toBeDisabled();

		fireEvent.change( screen.getByLabelText( 'Title' ), {
			target: { value: 'Cheque payments' },
		} );

		expect(
			screen.getByRole( 'button', { name: 'Save changes' } )
		).toBeEnabled();
	} );

	it( 'saves the edited values with the expected payload shape', async () => {
		render( <SettingsPaymentsCheque /> );

		fireEvent.change( screen.getByLabelText( 'Title' ), {
			target: { value: 'Cheque payments' },
		} );
		fireEvent.click( screen.getByLabelText( 'Enable check payments' ) );
		fireEvent.click( screen.getByLabelText( 'Accept for virtual orders' ) );
		fireEvent.click(
			screen.getByRole( 'button', { name: 'Save changes' } )
		);

		await waitFor( () => {
			expect( updatePaymentGateway ).toHaveBeenCalledWith( 'cheque', {
				enabled: false,
				description: 'Take payments in person via checks.',
				settings: {
					title: 'Cheque payments',
					instructions: 'Send the check to our address.',
					enable_for_methods: [ 'flat_rate:1' ],
					enable_for_virtual: 'no',
				},
			} );
		} );
	} );

	it( 'disables save again after a successful save', async () => {
		render( <SettingsPaymentsCheque /> );

		fireEvent.change( screen.getByLabelText( 'Title' ), {
			target: { value: 'Cheque payments' },
		} );
		fireEvent.click(
			screen.getByRole( 'button', { name: 'Save changes' } )
		);

		await waitFor( () => {
			expect(
				screen.getByRole( 'button', { name: 'Save changes' } )
			).toBeDisabled();
		} );
	} );

	it( 'supports keyboard navigation through the form fields', () => {
		render( <SettingsPaymentsCheque /> );

		// Make a change first so the Save button is enabled (and tabbable).
		fireEvent.change( screen.getByLabelText( 'Title' ), {
			target: { value: 'Edited title' },
		} );

		userEvent.tab();
		expect(
			screen.getByLabelText( 'Enable check payments' )
		).toHaveFocus();
		userEvent.tab();
		expect( screen.getByLabelText( 'Title' ) ).toHaveFocus();
		userEvent.tab();
		expect( screen.getByLabelText( 'Description' ) ).toHaveFocus();
		userEvent.tab();
		expect( screen.getByLabelText( 'Instructions' ) ).toHaveFocus();
		// The shipping methods tree select and the virtual orders checkbox
		// sit between Instructions and Save; tab until Save receives focus.
		const saveButton = screen.getByRole( 'button', {
			name: 'Save changes',
		} );
		for (
			let i = 0;
			i < 6 && saveButton.ownerDocument.activeElement !== saveButton;
			i++
		) {
			userEvent.tab();
		}
		expect( saveButton ).toHaveFocus();
	} );

	it( 'shows an error notice when saving fails', async () => {
		const createErrorNotice = jest.fn();
		updatePaymentGateway.mockRejectedValueOnce(
			new Error( 'save failed' )
		);
		( useDispatch as jest.Mock ).mockReturnValue( {
			createSuccessNotice: jest.fn(),
			createErrorNotice,
			updatePaymentGateway,
			invalidateResolution: jest.fn(),
			invalidateResolutionForStoreSelector: jest.fn(),
		} );

		render( <SettingsPaymentsCheque /> );

		fireEvent.change( screen.getByLabelText( 'Title' ), {
			target: { value: 'Edited title' },
		} );
		fireEvent.click(
			screen.getByRole( 'button', { name: 'Save changes' } )
		);

		await waitFor( () => {
			expect( createErrorNotice ).toHaveBeenCalledWith(
				'Failed to update settings'
			);
		} );
	} );
} );
