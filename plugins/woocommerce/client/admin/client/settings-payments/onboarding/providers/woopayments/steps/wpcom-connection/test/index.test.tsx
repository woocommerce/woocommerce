/**
 * External dependencies
 */
import {
	act,
	fireEvent,
	render,
	screen,
	waitFor,
} from '@testing-library/react';
import React from 'react';
import apiFetch from '@wordpress/api-fetch';
import { speak } from '@wordpress/a11y';

/**
 * Internal dependencies
 */
import { useOnboardingContext } from '../../../data/onboarding-context';
import JetpackStep from '../index';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
jest.mock( '@wordpress/a11y', () => ( { speak: jest.fn() } ) );
jest.mock( '../../../data/onboarding-context', () => ( {
	useOnboardingContext: jest.fn(),
} ) );
jest.mock( '~/settings-payments/utils', () => ( {
	recordPaymentsOnboardingEvent: jest.fn(),
} ) );
jest.mock( '~/utils/admin-settings', () => ( {
	WC_ASSET_URL: '',
} ) );

const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

const renderStep = (
	actions: Record< string, { href: string } > = {
		authorize: { href: 'https://example.com/authorize' },
	}
) => {
	( useOnboardingContext as jest.Mock ).mockReturnValue( {
		currentStep: {
			id: 'wpcom_connection',
			actions,
		},
		closeModal: jest.fn(),
		sessionEntryPoint: 'settings_payments',
	} );

	return render( <JetpackStep /> );
};

describe( 'JetpackStep', () => {
	const originalLocation = window.location;

	beforeEach( () => {
		jest.clearAllMocks();
		mockApiFetch.mockReset();
		Object.defineProperty( window, 'location', {
			writable: true,
			value: { ...originalLocation, href: '' },
		} );
	} );

	afterEach( () => {
		Object.defineProperty( window, 'location', {
			writable: true,
			value: originalLocation,
		} );
	} );

	it( 'redirects to the authorization URL returned after registering the store', async () => {
		mockApiFetch.mockResolvedValue( {
			success: true,
			url: 'https://wordpress.com/auth',
		} );
		renderStep();

		fireEvent.click( screen.getByRole( 'button', { name: 'Connect' } ) );

		await waitFor( () =>
			expect( window.location.href ).toBe( 'https://wordpress.com/auth' )
		);
	} );

	it( 'redirects to the auth URL without a request when the store is already registered', () => {
		renderStep( { auth: { href: 'https://wordpress.com/auth' } } );

		fireEvent.click( screen.getByRole( 'button', { name: 'Connect' } ) );

		expect( mockApiFetch ).not.toHaveBeenCalled();
		expect( window.location.href ).toBe( 'https://wordpress.com/auth' );
	} );

	it( 'decodes registration errors and lets the merchant retry', async () => {
		mockApiFetch.mockRejectedValue( {
			message: 'The site&#039;s URL can&#039;t be reached.',
		} );
		renderStep();
		const connect = screen.getByRole( 'button', { name: 'Connect' } );

		fireEvent.click( connect );

		expect(
			await screen.findByText( "The site's URL can't be reached.", {
				selector: 'p',
			} )
		).toBeInTheDocument();
		expect( connect ).not.toBeDisabled();

		fireEvent.click( connect );

		await waitFor( () =>
			expect( mockApiFetch ).toHaveBeenCalledTimes( 2 )
		);
		expect( mockApiFetch ).toHaveBeenLastCalledWith( {
			url: 'https://example.com/authorize',
			method: 'POST',
			data: { source: 'settings_payments' },
		} );
		await waitFor( () => expect( connect ).not.toBeDisabled() );
	} );

	it( 'keeps Connect focused and announces progress while blocking repeated clicks', async () => {
		let rejectRegistration: ( error: Error ) => void;
		mockApiFetch.mockImplementation(
			() =>
				new Promise( ( resolve, reject ) => {
					rejectRegistration = reject;
				} )
		);
		renderStep();
		const connect = screen.getByRole( 'button', { name: 'Connect' } );
		connect.focus();

		fireEvent.click( connect );

		expect( connect ).toHaveFocus();
		expect( connect ).toHaveAttribute( 'aria-disabled', 'true' );
		expect( connect ).not.toBeDisabled();
		expect( speak ).toHaveBeenCalledWith(
			'Connecting to WordPress.com. Please wait.',
			'polite'
		);

		fireEvent.click( connect );
		expect( mockApiFetch ).toHaveBeenCalledTimes( 1 );

		await act( async () => {
			rejectRegistration( new Error( 'Registration failed.' ) );
		} );

		expect( connect ).toHaveFocus();
		expect( connect ).not.toHaveAttribute( 'aria-disabled', 'true' );
		expect(
			screen.getByText( 'Registration failed.', { selector: 'p' } )
		).toBeInTheDocument();
	} );

	it( 'shows the fallback message when authorization fails without a message', async () => {
		mockApiFetch.mockRejectedValue( {} );
		renderStep();

		fireEvent.click( screen.getByRole( 'button', { name: 'Connect' } ) );

		expect(
			await screen.findByText(
				'Something went wrong. Please try again.',
				{
					selector: 'p',
				}
			)
		).toBeInTheDocument();
	} );
} );
