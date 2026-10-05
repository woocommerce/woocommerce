import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';

/**
 * External dependencies
 */
import {
	render,
	screen,
	fireEvent,
	waitFor,
	act,
} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { SettingsPaymentsCod } from '../settings-payments-cod';
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		useSelect: vi.fn(),
		useDispatch: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const codSettings = {
	enabled: true,
	description: 'Pay with cash upon delivery.',
	settings: {
		title: {
			value: 'Cash on delivery',
		},
		instructions: {
			value: 'Pay with cash upon delivery.',
		},
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
		enable_for_virtual: {
			value: 'yes',
		},
	},
};
describe( 'SettingsPaymentsCod', () => {
	let updatePaymentGateway: Mock;
	beforeEach( () => {
		updatePaymentGateway = vi.fn().mockResolvedValue( {} );
		( useDispatch as Mock ).mockReturnValue( {
			createSuccessNotice: vi.fn(),
			createErrorNotice: vi.fn(),
			updatePaymentGateway,
			invalidateResolution: vi.fn(),
			invalidateResolutionForStoreSelector: vi.fn(),
		} );
		( useSelect as Mock ).mockReturnValue( {
			codSettings,
			isLoading: false,
		} );
	} );
	it( 'renders all settings fields with stored values', () => {
		render( <SettingsPaymentsCod /> );
		expect(
			screen.getByLabelText( 'Enable cash on delivery payments' )
		).toBeChecked();
		expect( screen.getByLabelText( 'Title' ) ).toHaveValue(
			'Cash on delivery'
		);
		expect( screen.getByLabelText( 'Description' ) ).toHaveValue(
			'Pay with cash upon delivery.'
		);
		expect( screen.getByLabelText( 'Instructions' ) ).toHaveValue(
			'Pay with cash upon delivery.'
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
		( useSelect as Mock ).mockReturnValue( {
			codSettings: null,
			isLoading: true,
		} );
		const { container } = render( <SettingsPaymentsCod /> );
		expect(
			container.querySelectorAll( '.woocommerce-field-placeholder' )
				.length
		).toBeGreaterThan( 0 );
		expect( screen.queryByLabelText( 'Title' ) ).not.toBeInTheDocument();
	} );
	it( 'disables save until a change is made', () => {
		render( <SettingsPaymentsCod /> );
		expect(
			screen.getByRole( 'button', {
				name: 'Save changes',
			} )
		).toBeDisabled();
		fireEvent.change( screen.getByLabelText( 'Title' ), {
			target: {
				value: 'COD payments',
			},
		} );
		expect(
			screen.getByRole( 'button', {
				name: 'Save changes',
			} )
		).toBeEnabled();
	} );
	it( 'saves the edited values with the expected payload shape', async () => {
		render( <SettingsPaymentsCod /> );
		fireEvent.change( screen.getByLabelText( 'Title' ), {
			target: {
				value: 'COD payments',
			},
		} );
		fireEvent.click( screen.getByLabelText( 'Accept for virtual orders' ) );
		fireEvent.click(
			screen.getByRole( 'button', {
				name: 'Save changes',
			} )
		);
		await waitFor( () => {
			expect( updatePaymentGateway ).toHaveBeenCalledWith( 'cod', {
				enabled: true,
				description: 'Pay with cash upon delivery.',
				settings: {
					title: 'COD payments',
					instructions: 'Pay with cash upon delivery.',
					enable_for_methods: [ 'flat_rate:1' ],
					enable_for_virtual: 'no',
				},
			} );
		} );
	} );
	it( 'disables save again after a successful save', async () => {
		render( <SettingsPaymentsCod /> );
		fireEvent.change( screen.getByLabelText( 'Title' ), {
			target: {
				value: 'COD payments',
			},
		} );
		fireEvent.click(
			screen.getByRole( 'button', {
				name: 'Save changes',
			} )
		);
		await waitFor( () => {
			expect(
				screen.getByRole( 'button', {
					name: 'Save changes',
				} )
			).toBeDisabled();
		} );
	} );
	it( 'supports keyboard navigation through the form fields', async () => {
		render( <SettingsPaymentsCod /> );

		// Make a change first so the Save button is enabled (and tabbable).
		fireEvent.change( screen.getByLabelText( 'Title' ), {
			target: {
				value: 'Edited title',
			},
		} );
		await act( async () => {
			userEvent.tab();
		} );
		expect(
			screen.getByLabelText( 'Enable cash on delivery payments' )
		).toHaveFocus();
		await act( async () => {
			userEvent.tab();
		} );
		expect( screen.getByLabelText( 'Title' ) ).toHaveFocus();
		await act( async () => {
			userEvent.tab();
		} );
		expect( screen.getByLabelText( 'Description' ) ).toHaveFocus();
		await act( async () => {
			userEvent.tab();
		} );
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
			await act( async () => {
				userEvent.tab();
			} );
		}
		expect( saveButton ).toHaveFocus();
	} );
	it( 'shows an error notice when saving fails', async () => {
		const createErrorNotice = vi.fn();
		updatePaymentGateway.mockRejectedValueOnce(
			new Error( 'save failed' )
		);
		( useDispatch as Mock ).mockReturnValue( {
			createSuccessNotice: vi.fn(),
			createErrorNotice,
			updatePaymentGateway,
			invalidateResolution: vi.fn(),
			invalidateResolutionForStoreSelector: vi.fn(),
		} );
		render( <SettingsPaymentsCod /> );
		fireEvent.change( screen.getByLabelText( 'Title' ), {
			target: {
				value: 'Edited title',
			},
		} );
		fireEvent.click(
			screen.getByRole( 'button', {
				name: 'Save changes',
			} )
		);
		await waitFor( () => {
			expect( createErrorNotice ).toHaveBeenCalledWith(
				'Failed to update settings'
			);
		} );
	} );
} );
