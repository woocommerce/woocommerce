/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { MobileAppLoginStepper } from '../MobileAppLoginStepper';
import { SendMagicLinkStates } from '../useSendMagicLink';
import { QRLoginTokenStates, useQRLoginToken } from '../useQRLoginToken';

// Mock the QR login token hook so we can drive each state from the tests.
jest.mock( '../useQRLoginToken', () => {
	const actual = jest.requireActual( '../useQRLoginToken' );
	return {
		...actual,
		useQRLoginToken: jest.fn(),
	};
} );

// Short-circuit the up-front availability probe — these tests focus on the
// stepper's own gating + the underlying token state machine, not on the
// availability gate (which is covered separately by useQRLoginAvailability's
// own tests).
jest.mock( '../useQRLoginAvailability', () => {
	const actual = jest.requireActual( '../useQRLoginAvailability' );
	return {
		...actual,
		useQRLoginAvailability: () => ( {
			isLoading: false,
			available: true,
			reason: null,
		} ),
	};
} );

// Mock tracks to keep tests isolated from analytics side-effects.
jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

const mockedUseQRLoginToken = useQRLoginToken as jest.MockedFunction<
	typeof useQRLoginToken
>;

const readyTokenState = {
	state: QRLoginTokenStates.READY,
	qrUrl: 'woocommerce://qr-login?token=abc&siteUrl=https%3A%2F%2Fexample.test',
	secondsRemaining: 300,
	errorMessage: null,
	errorCode: null,
	deviceInfo: null,
	apUuid: null,
	candidateNumbers: null,
	challengeExpiresAt: 0,
	chooseNumber: jest.fn(),
	fetchToken: jest.fn(),
	refreshToken: jest.fn(),
	revoke: jest.fn(),
};

const errorTokenState = {
	state: QRLoginTokenStates.ERROR,
	qrUrl: null,
	secondsRemaining: 0,
	errorMessage: 'QR login requires an HTTPS connection.',
	errorCode: 'ssl_required',
	deviceInfo: null,
	apUuid: null,
	candidateNumbers: null,
	challengeExpiresAt: 0,
	chooseNumber: jest.fn(),
	fetchToken: jest.fn(),
	refreshToken: jest.fn(),
	revoke: jest.fn(),
};

const baseProps = {
	step: 'second' as const,
	signInResult: null,
	completeInstallationStepHandler: jest.fn(),
	sendMagicLinkHandler: jest.fn(),
	sendMagicLinkStatus: SendMagicLinkStates.INIT,
	onSignedIn: jest.fn(),
};

describe( 'MobileAppLoginStepper', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockedUseQRLoginToken.mockReturnValue( readyTokenState );
	} );

	describe( 'step 2 (sign-in)', () => {
		it( 'renders the QR direct login for an admin without Jetpack and hides the magic link button', () => {
			render(
				<MobileAppLoginStepper
					{ ...baseProps }
					isJetpackPluginInstalled={ false }
					wordpressAccountEmailAddress={ undefined }
				/>
			);

			// The QR direct login is the primary path — its expiry timer copy
			// is a reliable signal that the component is on screen.
			expect(
				screen.getByText( /Code expires in/i )
			).toBeInTheDocument();

			// The WordPress.com magic link secondary CTA should not show up
			// for non-Jetpack users.
			expect(
				screen.queryByText(
					/Or get a WordPress\.com sign-in link by email/i
				)
			).not.toBeInTheDocument();
			expect(
				screen.queryByRole( 'button', {
					name: /Send the sign-in link/i,
				} )
			).not.toBeInTheDocument();
		} );

		it( 'renders both the QR and the magic link button when Jetpack is fully connected and the user has a linked WordPress.com account', () => {
			render(
				<MobileAppLoginStepper
					{ ...baseProps }
					isJetpackPluginInstalled={ true }
					wordpressAccountEmailAddress="admin@example.test"
				/>
			);

			expect(
				screen.getByText( /Code expires in/i )
			).toBeInTheDocument();
			expect(
				screen.getByText(
					/Or get a WordPress\.com sign-in link by email/i
				)
			).toBeInTheDocument();
			expect(
				screen.getByRole( 'button', {
					name: /Send the sign-in link/i,
				} )
			).toBeInTheDocument();
		} );

		it( 'hides the magic link button for a shop manager without a linked WordPress.com account even if Jetpack is installed', () => {
			// Shop managers typically don't own the Jetpack connection and
			// their currentUser.wpcomUser.email is undefined upstream, which
			// surfaces here as wordpressAccountEmailAddress === undefined.
			render(
				<MobileAppLoginStepper
					{ ...baseProps }
					isJetpackPluginInstalled={ true }
					wordpressAccountEmailAddress={ undefined }
				/>
			);

			expect(
				screen.getByText( /Code expires in/i )
			).toBeInTheDocument();
			expect(
				screen.queryByText(
					/Or get a WordPress\.com sign-in link by email/i
				)
			).not.toBeInTheDocument();
		} );

		it( 'invokes the magic link handler when the secondary button is clicked', () => {
			const sendMagicLinkHandler = jest.fn();
			render(
				<MobileAppLoginStepper
					{ ...baseProps }
					sendMagicLinkHandler={ sendMagicLinkHandler }
					isJetpackPluginInstalled={ true }
					wordpressAccountEmailAddress="admin@example.test"
				/>
			);

			fireEvent.click(
				screen.getByRole( 'button', {
					name: /Send the sign-in link/i,
				} )
			);

			expect( sendMagicLinkHandler ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'surfaces the QR error state from useQRLoginToken', () => {
			mockedUseQRLoginToken.mockReturnValue( errorTokenState );

			render(
				<MobileAppLoginStepper
					{ ...baseProps }
					isJetpackPluginInstalled={ false }
					wordpressAccountEmailAddress={ undefined }
				/>
			);

			expect(
				screen.getByText( /QR login requires an HTTPS connection/i )
			).toBeInTheDocument();
			expect(
				screen.getByRole( 'button', { name: /Try again/i } )
			).toBeInTheDocument();
			// Error state should never leak the happy-path timer copy.
			expect(
				screen.queryByText( /Code expires in/i )
			).not.toBeInTheDocument();
		} );

		it( 'does not render the old username + site URL fallback when the user lacks a linked WordPress.com account', () => {
			render(
				<MobileAppLoginStepper
					{ ...baseProps }
					isJetpackPluginInstalled={ false }
					wordpressAccountEmailAddress={ undefined }
				/>
			);

			// The removed MobileAppLoginInfo fallback used to show this copy.
			expect(
				screen.queryByText(
					/Scan the QR code below and enter the wp-admin password/i
				)
			).not.toBeInTheDocument();
		} );
	} );

	describe( 'step 1 (install)', () => {
		it( 'renders the install-app CTA and wires it up to the handler', () => {
			const completeInstallationStepHandler = jest.fn();
			render(
				<MobileAppLoginStepper
					{ ...baseProps }
					step="first"
					completeInstallationStepHandler={
						completeInstallationStepHandler
					}
					isJetpackPluginInstalled={ false }
					wordpressAccountEmailAddress={ undefined }
				/>
			);

			const installButton = screen.getByRole( 'button', {
				name: /App is installed/i,
			} );
			fireEvent.click( installButton );
			expect( completeInstallationStepHandler ).toHaveBeenCalledTimes(
				1
			);

			// The QR direct login is rendered for step 2, not step 1.
			expect(
				screen.queryByText( /Code expires in/i )
			).not.toBeInTheDocument();
		} );
	} );
} );
