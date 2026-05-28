/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { MobileAppLoginPage } from '../index';
import {
	QRLoginTokenStates,
	useQRLoginToken,
} from '~/homescreen/mobile-app-modal/components/useQRLoginToken';

// Drive `<QRDirectLoginCode />` from the tests by mocking its shared token
// hook. The real component is rendered so we exercise the integration
// surface of this page against the component we claim to reuse.
jest.mock( '~/homescreen/mobile-app-modal/components/useQRLoginToken', () => {
	const actual = jest.requireActual(
		'~/homescreen/mobile-app-modal/components/useQRLoginToken'
	);
	return {
		...actual,
		useQRLoginToken: jest.fn(),
	};
} );

// Short-circuit the up-front /qr-login-availability probe so these tests
// reach the QR / error / expired states the assertions care about,
// rather than getting stuck on the availability spinner. The probe has
// its own dedicated suite — `useQRLoginAvailability.test.ts`.
jest.mock(
	'~/homescreen/mobile-app-modal/components/useQRLoginAvailability',
	() => {
		const actual = jest.requireActual(
			'~/homescreen/mobile-app-modal/components/useQRLoginAvailability'
		);
		return {
			...actual,
			useQRLoginAvailability: () => ( {
				isLoading: false,
				available: true,
				reason: null,
			} ),
		};
	}
);

// Keep tests isolated from analytics side-effects.
jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

const mockedUseQRLoginToken = useQRLoginToken as jest.MockedFunction<
	typeof useQRLoginToken
>;

const makeReadyState = () => ( {
	state: QRLoginTokenStates.READY,
	qrUrl: 'woocommerce://qr-login?token=abc&siteUrl=https%3A%2F%2Fexample.test',
	secondsRemaining: 300,
	errorMessage: null,
	errorCode: null,
	deviceInfo: null,
	apUuid: null,
	candidateNumbers: null,
	challengeExpiresAt: 0,
	fetchToken: jest.fn(),
	refreshToken: jest.fn(),
	chooseNumber: jest.fn(),
	revoke: jest.fn(),
} );

describe( 'MobileAppLoginPage', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockedUseQRLoginToken.mockReturnValue( makeReadyState() );
	} );

	it( 'renders the heading, scan-first intro, and the QR code', () => {
		render( <MobileAppLoginPage /> );

		expect(
			screen.getByRole( 'heading', {
				name: /Sign in to the Woo mobile app/i,
				level: 1,
			} )
		).toBeInTheDocument();

		// The scan-first intro mentions the in-app action merchants have to
		// tap — the exact phrasing is what engineering Happiness reads back
		// to users on support tickets, so we assert on it literally.
		expect( screen.getByText( /Scan QR code/ ) ).toBeInTheDocument();
		expect(
			screen.getByText( /Open the Woo mobile app on your phone/i )
		).toBeInTheDocument();

		// `<QRDirectLoginCode />` in READY state renders its countdown copy.
		// That copy is the load-bearing signal that the QR is on screen
		// because the SVG payload itself is not easily queryable.
		expect( screen.getByText( /Code expires in/i ) ).toBeInTheDocument();
	} );

	it( 'renders the FAQ link pointing at the help doc', () => {
		render( <MobileAppLoginPage /> );

		// Copy synced with the homescreen modal so both surfaces share the
		// same wording ("Any troubles signing in? Check out the FAQ.").
		const faqLink = screen.getByRole( 'link', {
			name: /FAQ/i,
		} );
		expect( faqLink ).toHaveAttribute(
			'href',
			'https://woocommerce.com/document/android-ios-apps-login-help-faq/'
		);
	} );

	it( 'does not offer a manual refresh while a QR code is still valid', () => {
		const fetchToken = jest.fn();
		mockedUseQRLoginToken.mockReturnValue( {
			...makeReadyState(),
			fetchToken,
		} );

		render( <MobileAppLoginPage /> );

		// First mount fires exactly one fetch (from QRDirectLoginCode's
		// initial `useEffect`).
		expect( fetchToken ).toHaveBeenCalledTimes( 1 );

		expect(
			screen.queryByRole( 'button', { name: /Refresh code/i } )
		).not.toBeInTheDocument();
	} );

	it( 'lets the shared QR component generate a new code after expiry', () => {
		const refreshToken = jest.fn();
		mockedUseQRLoginToken.mockReturnValue( {
			state: QRLoginTokenStates.EXPIRED,
			qrUrl: null,
			secondsRemaining: 0,
			errorMessage: null,
			errorCode: null,
			deviceInfo: null,
			apUuid: null,
			candidateNumbers: null,
			challengeExpiresAt: 0,
			fetchToken: jest.fn(),
			refreshToken,
			chooseNumber: jest.fn(),
			revoke: jest.fn(),
		} );

		render( <MobileAppLoginPage /> );

		fireEvent.click(
			screen.getByRole( 'button', { name: /Generate new code/i } )
		);

		expect( refreshToken ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'renders a recovery action when READY has no QR URL', () => {
		const refreshToken = jest.fn();
		mockedUseQRLoginToken.mockReturnValue( {
			...makeReadyState(),
			qrUrl: null,
			refreshToken,
		} );

		render( <MobileAppLoginPage /> );

		expect(
			screen.getByText( /could not generate the login code/i )
		).toBeInTheDocument();

		fireEvent.click(
			screen.getByRole( 'button', { name: /Renew code/i } )
		);

		expect( refreshToken ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'renders a recovery action when SCANNED has no candidate numbers', () => {
		const refreshToken = jest.fn();
		mockedUseQRLoginToken.mockReturnValue( {
			...makeReadyState(),
			state: QRLoginTokenStates.SCANNED,
			qrUrl: null,
			candidateNumbers: null,
			refreshToken,
		} );

		render( <MobileAppLoginPage /> );

		expect(
			screen.getByText( /could not load the confirmation challenge/i )
		).toBeInTheDocument();

		fireEvent.click( screen.getByRole( 'button', { name: /Try again/i } ) );

		expect( refreshToken ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not render the magic-link button (regression guard — modal-only feature)', () => {
		render( <MobileAppLoginPage /> );

		// The onboarding modal ships a "Send the sign-in link" button when
		// a WordPress.com account is linked. That button must never appear
		// on this standalone page — the audience here is app-install-ready
		// merchants who just need to scan, not magic-link recipients.
		expect(
			screen.queryByRole( 'button', {
				name: /Send the sign-in link/i,
			} )
		).not.toBeInTheDocument();
		expect(
			screen.queryByText(
				/Or get a WordPress\.com sign-in link by email/i
			)
		).not.toBeInTheDocument();
	} );

	it( 'surfaces the QR error state from useQRLoginToken without breaking the page shell', () => {
		mockedUseQRLoginToken.mockReturnValue( {
			state: QRLoginTokenStates.ERROR,
			qrUrl: null,
			secondsRemaining: 0,
			errorMessage: 'QR login requires an HTTPS connection.',
			errorCode: 'ssl_required',
			deviceInfo: null,
			apUuid: null,
			candidateNumbers: null,
			challengeExpiresAt: 0,
			fetchToken: jest.fn(),
			refreshToken: jest.fn(),
			chooseNumber: jest.fn(),
			revoke: jest.fn(),
		} );

		render( <MobileAppLoginPage /> );

		// The heading and FAQ link are static shell — they must still render
		// even when the QR surfaces an error from the backend.
		expect(
			screen.getByRole( 'heading', {
				name: /Sign in to the Woo mobile app/i,
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: /FAQ/i } )
		).toBeInTheDocument();

		// Error text from the hook leaks through the shared component.
		expect(
			screen.getByText( /QR login requires an HTTPS connection/i )
		).toBeInTheDocument();
	} );

	it( 'renders consumed-state revoke errors on the standalone page', () => {
		mockedUseQRLoginToken.mockReturnValue( {
			state: QRLoginTokenStates.CONSUMED,
			qrUrl: null,
			secondsRemaining: 0,
			errorMessage: 'Failed to revoke access.',
			errorCode: null,
			deviceInfo: { model: 'iPhone 15' },
			apUuid: 'ap-uuid',
			candidateNumbers: null,
			challengeExpiresAt: 0,
			fetchToken: jest.fn(),
			refreshToken: jest.fn(),
			chooseNumber: jest.fn(),
			revoke: jest.fn(),
		} );

		render( <MobileAppLoginPage /> );

		expect(
			screen.getByText( /Signed in successfully on iPhone 15/i )
		).toBeInTheDocument();
		expect(
			screen.getByText( /Failed to revoke access/i )
		).toBeInTheDocument();
	} );
} );
