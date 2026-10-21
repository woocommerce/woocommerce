/**
 * External dependencies
 */
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import React from 'react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useOnboardingContext } from '../../../data/onboarding-context';
import JetpackStep from '../index';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
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

const renderStep = () => {
	( useOnboardingContext as jest.Mock ).mockReturnValue( {
		currentStep: {
			id: 'wpcom_connection',
			actions: {
				authorize: { href: 'https://example.com/authorize' },
			},
		},
		closeModal: jest.fn(),
		sessionEntryPoint: 'settings_payments',
	} );

	return render( <JetpackStep /> );
};

describe( 'JetpackStep', () => {
	beforeEach( () => {
		jest.clearAllMocks();
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
