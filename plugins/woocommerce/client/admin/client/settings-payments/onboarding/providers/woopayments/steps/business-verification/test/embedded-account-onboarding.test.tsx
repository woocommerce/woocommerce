import {
	beforeEach,
	describe,
	expect,
	it,
	vi,
	type Mock,
	type MockedFunction,
} from 'vitest';

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
vi.mock( '@stripe/connect-js', () => {
	const mock = {
		loadConnectAndInitialize: vi.fn( () => ( {
			mockStripeConnectInstance: true,
		} ) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@stripe/react-connect-js', () => {
	const mock = {
		ConnectComponentsProvider: ( {
			children,
		}: {
			children: React.ReactNode;
		} ) => (
			<div data-testid="connect-components-provider">{ children }</div>
		),
		ConnectAccountOnboarding: () => (
			<div data-testid="connect-account-onboarding" />
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../utils/actions', () => {
	const mock = {
		createEmbeddedKycSession: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../../data/onboarding-context', () => {
	const mock = {
		useOnboardingContext: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const mockCreateEmbeddedKycSession = createEmbeddedKycSession as MockedFunction<
	typeof createEmbeddedKycSession
>;
const mockLoadConnectAndInitialize = loadConnectAndInitialize as MockedFunction<
	typeof loadConnectAndInitialize
>;
const mockUseOnboardingContext = useOnboardingContext as Mock;
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
			onExit={ vi.fn() }
			{ ...overrides }
		/>
	);
};
describe( 'EmbeddedAccountOnboarding', async () => {
	beforeEach( () => {
		vi.clearAllMocks();
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
		const mockOnInitializationError = vi.fn();
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
		const mockOnInitializationError = vi.fn();
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
			createSession( {
				locale,
			} )
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
