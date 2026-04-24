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
	fetchToken: jest.fn(),
	refreshToken: jest.fn(),
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

	it( 'renders the troubleshooting FAQ link pointing at the help doc', () => {
		render( <MobileAppLoginPage /> );

		const faqLink = screen.getByRole( 'link', {
			name: /troubleshooting guide/i,
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
			fetchToken: jest.fn(),
			refreshToken,
		} );

		render( <MobileAppLoginPage /> );

		fireEvent.click(
			screen.getByRole( 'button', { name: /Generate new code/i } )
		);

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
			fetchToken: jest.fn(),
			refreshToken: jest.fn(),
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
			screen.getByRole( 'link', { name: /troubleshooting guide/i } )
		).toBeInTheDocument();

		// Error text from the hook leaks through the shared component.
		expect(
			screen.getByText( /QR login requires an HTTPS connection/i )
		).toBeInTheDocument();
	} );
} );
