/**
 * External dependencies
 */
import { loadConnectAndInitialize } from '@stripe/connect-js';
import { render, screen, waitFor } from '@testing-library/react';
import React from 'react';

/**
 * Internal dependencies
 */
import { EmbeddedAccountOnboarding } from '../components/embedded';
import { createEmbeddedKycSession } from '../utils/actions';
import { useOnboardingContext } from '../../../data/onboarding-context';
import type { EmbeddedKycSessionCreateResult } from '../types';

jest.mock( '@stripe/connect-js', () => ( {
	loadConnectAndInitialize: jest.fn( () => ( {
		mockStripeConnectInstance: true,
	} ) ),
} ) );

jest.mock( '@stripe/react-connect-js', () => ( {
	ConnectComponentsProvider: ( {
		children,
	}: {
		children: React.ReactNode;
	} ) => <div data-testid="connect-components-provider">{ children }</div>,
	ConnectAccountOnboarding: () => (
		<div data-testid="connect-account-onboarding" />
	),
} ) );

jest.mock( '../utils/actions', () => ( {
	createEmbeddedKycSession: jest.fn(),
} ) );

jest.mock( '../../../data/onboarding-context', () => ( {
	useOnboardingContext: jest.fn(),
} ) );

const mockCreateEmbeddedKycSession =
	createEmbeddedKycSession as jest.MockedFunction<
		typeof createEmbeddedKycSession
	>;
const mockLoadConnectAndInitialize =
	loadConnectAndInitialize as jest.MockedFunction<
		typeof loadConnectAndInitialize
	>;
const mockUseOnboardingContext = useOnboardingContext as jest.Mock;

const createSession = (
	overrides: Partial< EmbeddedKycSessionCreateResult[ 'session' ] > = {}
): EmbeddedKycSessionCreateResult => ( {
	session: {
		clientSecret: 'test-secret',
		publishableKey: 'test-key',
		locale: 'en_US',
		expiresAt: 1234567890,
		accountId: 'acct_test',
		isLive: false,
		accountCreated: true,
		...overrides,
	},
} );

const renderEmbeddedAccountOnboarding = (
	overrides: Partial<
		React.ComponentProps< typeof EmbeddedAccountOnboarding >
	> = {}
) => {
	return render(
		<EmbeddedAccountOnboarding
			onboardingData={ {} }
			onExit={ jest.fn() }
			{ ...overrides }
		/>
	);
};

describe( 'EmbeddedAccountOnboarding', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockUseOnboardingContext.mockReturnValue( {
			currentStep: {
				actions: {
					kyc_session: {
						href: 'https://example.com/session',
					},
				},
			},
			sessionEntryPoint: 'settings',
		} );
		mockCreateEmbeddedKycSession.mockResolvedValue( createSession() );
	} );

	it( 'notifies the parent when the KYC session shape is invalid', async () => {
		const mockOnInitializationError = jest.fn();
		mockCreateEmbeddedKycSession.mockResolvedValue( {
			session: {
				unexpected: 'value',
			},
		} as never );

		renderEmbeddedAccountOnboarding( {
			onInitializationError: mockOnInitializationError,
		} );

		await waitFor( () =>
			expect( mockOnInitializationError ).toHaveBeenCalledWith( {
				reason: 'bad_session',
				message:
					'Unable to start the business verification session. If this problem persists, please contact support.',
				receivedKeys: [ 'unexpected' ],
			} )
		);
		expect(
			screen.queryByTestId( 'connect-account-onboarding' )
		).not.toBeInTheDocument();
		expect( mockLoadConnectAndInitialize ).not.toHaveBeenCalled();
	} );

	it( 'notifies the parent when initialization throws unexpectedly', async () => {
		const mockOnInitializationError = jest.fn();
		mockCreateEmbeddedKycSession.mockRejectedValue(
			new Error( 'Network unavailable.' )
		);

		renderEmbeddedAccountOnboarding( {
			onInitializationError: mockOnInitializationError,
		} );

		await waitFor( () =>
			expect( mockOnInitializationError ).toHaveBeenCalledWith( {
				reason: 'init_error',
				message:
					'Unable to start the business verification session. If this problem persists, please contact support.',
			} )
		);
		expect(
			screen.queryByTestId( 'connect-account-onboarding' )
		).not.toBeInTheDocument();
	} );

	it.each( [ undefined, '', 42 ] )(
		'defaults the locale when the KYC session returns %p',
		async ( locale ) => {
			mockCreateEmbeddedKycSession.mockResolvedValue(
				createSession( {
					locale: locale as never,
				} )
			);

			renderEmbeddedAccountOnboarding();

			expect(
				await screen.findByTestId( 'connect-account-onboarding' )
			).toBeInTheDocument();
			expect( mockLoadConnectAndInitialize ).toHaveBeenCalledWith(
				expect.objectContaining( {
					locale: 'en-US',
				} )
			);
		}
	);

	it.each( [
		[ 'en_US', 'en-US' ],
		[ ' sr_Latn_RS ', 'sr-Latn-RS' ],
	] )( 'normalizes locale %p to %p', async ( locale, expectedLocale ) => {
		mockCreateEmbeddedKycSession.mockResolvedValue(
			createSession( { locale } )
		);

		renderEmbeddedAccountOnboarding();

		expect(
			await screen.findByTestId( 'connect-account-onboarding' )
		).toBeInTheDocument();
		expect( mockLoadConnectAndInitialize ).toHaveBeenCalledWith(
			expect.objectContaining( {
				locale: expectedLocale,
			} )
		);
	} );

	it( 'passes session credentials through to Stripe for a valid session', async () => {
		renderEmbeddedAccountOnboarding();

		expect(
			await screen.findByTestId( 'connect-account-onboarding' )
		).toBeInTheDocument();

		const [ firstInitializeCall ] = mockLoadConnectAndInitialize.mock.calls;
		const initializeOptions = firstInitializeCall?.[ 0 ];

		if ( ! initializeOptions ) {
			throw new Error( 'Expected Stripe initialization options.' );
		}

		expect( initializeOptions ).toEqual(
			expect.objectContaining( {
				publishableKey: 'test-key',
				locale: 'en-US',
			} )
		);
		await expect( initializeOptions.fetchClientSecret() ).resolves.toBe(
			'test-secret'
		);
	} );
} );
