/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { WooPaymentsExpressCheckoutSettings } from '../express-checkout/express-checkout-settings';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const mockCreateErrorNotice = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	dispatch: jest.fn( () => ( {
		createErrorNotice: mockCreateErrorNotice,
	} ) ),
} ) );

const mockSaveSettings = jest.fn();
const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;
const mockDispatch = dispatch as jest.MockedFunction< typeof dispatch >;
let mockSettingsBootstrap: Record< string, unknown > = {};
const mockUseSettings = jest.fn();
const mockUseGetSettings = jest.fn();
const mockUseEnabledPaymentMethodIds = jest.fn();
const mockUsePaymentRequestEnabledSettings = jest.fn();
const mockUseExpressCheckoutInPaymentMethodsEnabledSettings = jest.fn();
const mockUsePaymentRequestButtonType = jest.fn();
const mockUsePaymentRequestButtonSize = jest.fn();
const mockUsePaymentRequestButtonTheme = jest.fn();
const mockUsePaymentRequestButtonBorderRadius = jest.fn();
const mockUseWooPayEnabledSettings = jest.fn();
const mockUseWooPayGlobalThemeSupportEnabledSettings = jest.fn();
const mockUseWooPayCustomMessage = jest.fn();
const mockUseWooPayStoreLogo = jest.fn();
const mockUsePaymentRequestLocations = jest.fn();
const mockUseWooPayLocations = jest.fn();
const mockUseAmazonPayLocations = jest.fn();
const mockUseAmazonPayEnabledSettings = jest.fn();
const mockUseLinkEnabledSettings = jest.fn();
const mockUseWooPayShowIncompatibilityNotice = jest.fn();
const mockUseGetAvailablePaymentMethodIds = jest.fn();
const originalLocation = window.location;
const mockExpressCheckoutMount = jest.fn();
const mockExpressCheckoutUnmount = jest.fn();
const mockExpressCheckoutOn = jest.fn();
const mockElementsCreate = jest.fn();
const mockStripeElements = jest.fn();
const mockStripe = jest.fn();
const STRIPE_SCRIPT_URL = 'https://js.stripe.com/v3/';

jest.mock( '../data/hooks', () => ( {
	useSettings: () => mockUseSettings(),
	useGetSettings: () => mockUseGetSettings(),
	useEnabledPaymentMethodIds: () => mockUseEnabledPaymentMethodIds(),
	usePaymentRequestEnabledSettings: () =>
		mockUsePaymentRequestEnabledSettings(),
	useExpressCheckoutInPaymentMethodsEnabledSettings: () =>
		mockUseExpressCheckoutInPaymentMethodsEnabledSettings(),
	usePaymentRequestButtonType: () => mockUsePaymentRequestButtonType(),
	usePaymentRequestButtonSize: () => mockUsePaymentRequestButtonSize(),
	usePaymentRequestButtonTheme: () => mockUsePaymentRequestButtonTheme(),
	usePaymentRequestButtonBorderRadius: () =>
		mockUsePaymentRequestButtonBorderRadius(),
	useWooPayEnabledSettings: () => mockUseWooPayEnabledSettings(),
	useWooPayGlobalThemeSupportEnabledSettings: () =>
		mockUseWooPayGlobalThemeSupportEnabledSettings(),
	useWooPayCustomMessage: () => mockUseWooPayCustomMessage(),
	useWooPayStoreLogo: () => mockUseWooPayStoreLogo(),
	usePaymentRequestLocations: () => mockUsePaymentRequestLocations(),
	useWooPayLocations: () => mockUseWooPayLocations(),
	useAmazonPayLocations: () => mockUseAmazonPayLocations(),
	useAmazonPayEnabledSettings: () => mockUseAmazonPayEnabledSettings(),
	useLinkEnabledSettings: () => mockUseLinkEnabledSettings(),
	useWooPayShowIncompatibilityNotice: () =>
		mockUseWooPayShowIncompatibilityNotice(),
	useGetAvailablePaymentMethodIds: () =>
		mockUseGetAvailablePaymentMethodIds(),
} ) );

jest.mock( '../bootstrap', () => ( {
	getWooPaymentsSettingsBootstrap: () => mockSettingsBootstrap,
} ) );

const noop = jest.fn();

const setWindowProtocol = ( protocol: 'http:' | 'https:' ) => {
	Object.defineProperty( window, 'location', {
		configurable: true,
		value: new URL( `${ protocol }//example.test/wp-admin/admin.php` ),
	} );
};

const installStripeMock = () => {
	mockExpressCheckoutMount.mockClear();
	mockExpressCheckoutUnmount.mockClear();
	mockExpressCheckoutOn.mockClear();
	mockElementsCreate.mockClear();
	mockStripeElements.mockClear();
	mockStripe.mockClear();
	mockExpressCheckoutOn.mockImplementation( ( eventName, callback ) => {
		if ( eventName === 'ready' ) {
			callback( {
				availablePaymentMethods: {
					applePay: true,
					googlePay: true,
				},
			} );
		}
	} );
	mockElementsCreate.mockReturnValue( {
		mount: mockExpressCheckoutMount,
		unmount: mockExpressCheckoutUnmount,
		on: mockExpressCheckoutOn,
	} );
	mockStripeElements.mockReturnValue( { create: mockElementsCreate } );
	mockStripe.mockReturnValue( { elements: mockStripeElements } );
	( window as typeof window & { Stripe?: typeof mockStripe } ).Stripe =
		mockStripe;
};

const setHookDefaults = () => {
	mockSettingsBootstrap = {
		isExpressCheckoutInPaymentMethodsListEnabled: true,
		isWooPayGlobalThemeSupportEligible: true,
		restUrl: 'https://example.test/wp-json/',
		siteLogoUrl: '',
		storeName: 'Native test store',
		woopayAppearance: {
			variables: {
				colorBackground: '#ffffff',
				colorText: '#111111',
			},
			rules: {
				'.Header': {
					backgroundColor: '#f6f7f7',
					color: '#111111',
				},
				'.Link': {
					color: '#674399',
				},
			},
		},
		woopayFontRules: [],
	};
	mockUseSettings.mockReturnValue( {
		isLoading: false,
		isSaving: false,
		isDirty: true,
		saveSettings: mockSaveSettings,
	} );
	mockUseGetSettings.mockReturnValue( {
		is_express_checkout_in_payment_methods_list_supported: true,
		is_woopay_global_theme_support_eligible: true,
		site_logo_url: '',
		store_name: 'Native test store',
		woopay_appearance: {
			variables: {
				colorBackground: '#ffffff',
				colorText: '#111111',
			},
			rules: {
				'.Header': {
					backgroundColor: '#f6f7f7',
					color: '#111111',
				},
				'.Link': {
					color: '#674399',
				},
			},
		},
		woopay_font_rules: [],
		express_checkout_preview: {
			stripe: {
				publishableKey: 'pk_test_native',
				accountId: 'acct_native_test',
				locale: 'en-us',
			},
		},
	} );
	mockUseEnabledPaymentMethodIds.mockReturnValue( [ [ 'card' ], noop ] );
	mockUsePaymentRequestEnabledSettings.mockReturnValue( [ true, noop ] );
	mockUseExpressCheckoutInPaymentMethodsEnabledSettings.mockReturnValue( [
		false,
		noop,
	] );
	mockUsePaymentRequestButtonType.mockReturnValue( [ 'default', noop ] );
	mockUsePaymentRequestButtonSize.mockReturnValue( [ 'small', noop ] );
	mockUsePaymentRequestButtonTheme.mockReturnValue( [ 'dark', noop ] );
	mockUsePaymentRequestButtonBorderRadius.mockReturnValue( [ 4, noop ] );
	mockUseWooPayEnabledSettings.mockReturnValue( [ true, noop ] );
	mockUseWooPayGlobalThemeSupportEnabledSettings.mockReturnValue( [
		false,
		noop,
	] );
	mockUseWooPayCustomMessage.mockReturnValue( [
		'Fast checkout with WooPay',
		noop,
	] );
	mockUseWooPayStoreLogo.mockReturnValue( [ 'file_123', noop ] );
	mockUsePaymentRequestLocations.mockReturnValue( [
		[ 'product', 'cart', 'checkout' ],
		noop,
	] );
	mockUseWooPayLocations.mockReturnValue( [
		[ 'product', 'cart', 'checkout' ],
		noop,
	] );
	mockUseAmazonPayLocations.mockReturnValue( [
		[ 'product', 'cart', 'checkout' ],
		noop,
	] );
	mockUseAmazonPayEnabledSettings.mockReturnValue( [ true, noop ] );
	mockUseLinkEnabledSettings.mockReturnValue( [ false, noop, true ] );
	mockUseWooPayShowIncompatibilityNotice.mockReturnValue( false );
	mockUseGetAvailablePaymentMethodIds.mockReturnValue( [
		'amazon_pay',
		'apple_pay',
		'google_pay',
	] );
	mockSaveSettings.mockResolvedValue( true );
};

describe( 'WooPaymentsExpressCheckoutSettings', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockApiFetch.mockResolvedValue( { id: 'file_logo' } );
		setWindowProtocol( 'http:' );
		delete ( window as typeof window & { Stripe?: typeof mockStripe } )
			.Stripe;
		setHookDefaults();
	} );

	afterEach( () => {
		Object.defineProperty( window, 'location', {
			configurable: true,
			value: originalLocation,
		} );
		delete ( window as typeof window & { Stripe?: typeof mockStripe } )
			.Stripe;
		document
			.querySelectorAll( `script[src="${ STRIPE_SCRIPT_URL }"]` )
			.forEach( ( script ) => script.remove() );
	} );

	it( 'fails closed for invalid express checkout method IDs', () => {
		render( <WooPaymentsExpressCheckoutSettings methodId="invalid" /> );

		expect(
			screen.getByText( 'Invalid express checkout method ID specified.' )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Save changes' } )
		).not.toBeInTheDocument();
	} );

	it( 'does not render writable controls while settings are loading', () => {
		mockUseSettings.mockReturnValue( {
			isLoading: true,
			isSaving: false,
			isDirty: false,
			saveSettings: mockSaveSettings,
		} );

		render(
			<WooPaymentsExpressCheckoutSettings methodId="payment_request" />
		);

		expect(
			screen.getByText( 'Loading WooPayments settings…' )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'checkbox', {
				name: 'Enable Apple Pay / Google Pay as express payment buttons',
			} )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Save changes' } )
		).not.toBeInTheDocument();
	} );

	it( 'does not render writable controls when settings failed to load', () => {
		mockUseGetSettings.mockReturnValue( {} );

		render(
			<WooPaymentsExpressCheckoutSettings methodId="payment_request" />
		);

		expect(
			screen.getAllByText( 'Unable to load WooPayments settings.' ).length
		).toBeGreaterThan( 0 );
		expect(
			screen.queryByRole( 'checkbox', {
				name: 'Enable Apple Pay / Google Pay as express payment buttons',
			} )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Save changes' } )
		).not.toBeInTheDocument();
	} );

	it( 'renders Apple Pay and Google Pay detail controls with reference copy', async () => {
		render(
			<WooPaymentsExpressCheckoutSettings methodId="payment_request" />
		);

		expect(
			await screen.findByRole( 'heading', {
				level: 1,
				name: 'Apple Pay / Google Pay',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'Return to payments' } )
		).toHaveAttribute(
			'href',
			expect.stringContaining( 'path=%2Fwoopayments%2Fsettings' )
		);
		expect(
			await screen.findByRole( 'img', { name: 'Apple Pay' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'img', { name: 'Google Pay' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'checkbox', {
				name: 'Enable Apple Pay / Google Pay as express payment buttons',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'checkbox', {
				name: 'Enable express checkout methods as options in the payment methods list',
			} )
		).toBeInTheDocument();
		expect( screen.getByLabelText( 'Show on product page' ) ).toBeChecked();
		expect( screen.getByLabelText( 'Show on cart page' ) ).toBeChecked();
		expect(
			screen.getByLabelText( 'Show on checkout page' )
		).toBeChecked();
	} );

	it( 'hides Apple Pay and Google Pay payment-methods-list mode when native settings do not support it', async () => {
		mockUseGetSettings.mockReturnValue( {
			is_express_checkout_in_payment_methods_list_supported: false,
		} );

		render(
			<WooPaymentsExpressCheckoutSettings methodId="payment_request" />
		);

		expect(
			await screen.findByRole( 'checkbox', {
				name: 'Enable Apple Pay / Google Pay as express payment buttons',
			} )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'checkbox', {
				name: 'Enable express checkout methods as options in the payment methods list',
			} )
		).not.toBeInTheDocument();
	} );

	it( 'applies the payment-methods-list override to Apple Pay and Google Pay locations', async () => {
		mockUseExpressCheckoutInPaymentMethodsEnabledSettings.mockReturnValue( [
			true,
			noop,
		] );

		render(
			<WooPaymentsExpressCheckoutSettings methodId="payment_request" />
		);

		await screen.findByRole( 'heading', {
			level: 1,
			name: 'Apple Pay / Google Pay',
		} );
		expect(
			screen.getByLabelText( 'Show on product page' )
		).toBeDisabled();
		expect( screen.getByLabelText( 'Show on cart page' ) ).toBeDisabled();
		expect(
			screen.getByLabelText( 'Show on checkout page' )
		).toBeDisabled();
		expect(
			screen.getByLabelText( 'Show on product page' )
		).not.toBeChecked();
		expect(
			screen.getByLabelText( 'Show on cart page' )
		).not.toBeChecked();
		expect(
			screen.getByLabelText( 'Show on checkout page' )
		).toBeChecked();
	} );

	it( 'keeps Apple Pay and Google Pay locations interactive outside the payment-methods-list override', async () => {
		const updatePaymentRequestLocation = jest.fn();
		mockUsePaymentRequestLocations.mockReturnValue( [
			[ 'product', 'cart', 'checkout' ],
			updatePaymentRequestLocation,
		] );

		render(
			<WooPaymentsExpressCheckoutSettings methodId="payment_request" />
		);

		await screen.findByRole( 'heading', {
			level: 1,
			name: 'Apple Pay / Google Pay',
		} );
		await userEvent.click(
			screen.getByLabelText( 'Show on product page' )
		);

		expect( updatePaymentRequestLocation ).toHaveBeenCalledWith(
			'product',
			false
		);
	} );

	it( 'renders shared appearance controls with cross-method notices', async () => {
		mockUseWooPayEnabledSettings.mockReturnValue( [ true, noop ] );

		render(
			<WooPaymentsExpressCheckoutSettings methodId="payment_request" />
		);

		await screen.findByRole( 'heading', {
			level: 1,
			name: 'Apple Pay / Google Pay',
		} );
		expect(
			screen.getByRole( 'combobox', { name: 'Call to action' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'option', { name: 'Only icon' } )
		).toHaveValue( 'default' );
		expect(
			screen.getByRole( 'option', { name: 'Buy with' } )
		).toHaveValue( 'buy' );
		expect(
			screen.getByRole( 'option', { name: 'Donate with' } )
		).toHaveValue( 'donate' );
		expect(
			screen.getByRole( 'option', { name: 'Book with' } )
		).toHaveValue( 'book' );
		expect( screen.getByLabelText( 'Small (40 px)' ) ).toBeChecked();
		expect( screen.getByLabelText( /Dark/ ) ).toBeChecked();
		expect(
			screen.getByLabelText( 'Border radius, number input' )
		).toHaveValue( 4 );
		expect(
			screen.getByText(
				'These settings will also apply to the WooPay and Amazon Pay buttons on your store.'
			)
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Some appearance settings may be overridden in the express payment section of the Cart & Checkout blocks.'
			)
		).toBeInTheDocument();
	} );

	it( 'shows the activate-express-checkout notice when no previewable express checkouts are enabled', async () => {
		mockUseWooPayEnabledSettings.mockReturnValue( [ false, noop ] );
		mockUsePaymentRequestEnabledSettings.mockReturnValue( [ false, noop ] );

		render(
			<WooPaymentsExpressCheckoutSettings methodId="payment_request" />
		);

		expect(
			await screen.findByText(
				'To preview the express checkout buttons, activate at least one express checkout.',
				{ selector: '.components-notice__content' }
			)
		).toBeInTheDocument();
	} );

	it( 'renders a WooPay button preview and HTTP requirements notice without initializing Stripe', async () => {
		installStripeMock();

		render(
			<WooPaymentsExpressCheckoutSettings methodId="payment_request" />
		);

		expect(
			await screen.findByRole( 'button', {
				name: /^WooPay$/i,
			} )
		).toHaveAccessibleDescription( 'Express checkout preview' );
		expect(
			screen.getByText(
				/To preview the express checkout buttons, ensure your store uses HTTPS/,
				{ selector: '.components-notice__content' }
			)
		).toBeInTheDocument();
		expect( mockStripe ).not.toHaveBeenCalled();
	} );

	it( 'keeps the visible WooPay call to action in the preview accessible name', async () => {
		mockUsePaymentRequestButtonType.mockReturnValue( [ 'buy', noop ] );

		render(
			<WooPaymentsExpressCheckoutSettings methodId="payment_request" />
		);

		expect(
			await screen.findByRole( 'button', {
				name: /^Buy with WooPay$/i,
			} )
		).toHaveAccessibleDescription( 'Express checkout preview' );
	} );

	it( 'mounts the live Stripe Express Checkout preview with native checkout loader behavior on HTTPS', async () => {
		setWindowProtocol( 'https:' );
		installStripeMock();

		render(
			<WooPaymentsExpressCheckoutSettings methodId="payment_request" />
		);

		await waitFor( () =>
			expect( mockExpressCheckoutMount ).toHaveBeenCalled()
		);
		expect( mockStripe ).toHaveBeenCalledWith( 'pk_test_native', {
			locale: 'en-us',
			stripeAccount: 'acct_native_test',
		} );
		expect( mockStripeElements ).toHaveBeenCalledWith(
			expect.objectContaining( {
				mode: 'payment',
				amount: 1000,
				currency: 'usd',
				loader: 'never',
				appearance: {
					variables: {
						borderRadius: '4px',
						spacingUnit: '6px',
					},
				},
			} )
		);
		expect( mockElementsCreate ).toHaveBeenCalledWith(
			'expressCheckout',
			expect.objectContaining( {
				buttonHeight: 40,
				buttonTheme: {
					applePay: 'black',
					googlePay: 'black',
				},
				buttonType: {
					applePay: 'plain',
					googlePay: 'plain',
				},
				layout: { overflow: 'never' },
				paymentMethods: expect.objectContaining( {
					applePay: 'always',
					googlePay: 'always',
					amazonPay: 'never',
					link: 'never',
					paypal: 'never',
					klarna: 'never',
				} ),
			} )
		);
	} );

	it( 'loads Stripe.js before mounting the live preview when Stripe is not already available', async () => {
		setWindowProtocol( 'https:' );
		installStripeMock();
		const stripeFactory = (
			window as typeof window & { Stripe?: typeof mockStripe }
		 ).Stripe;
		delete ( window as typeof window & { Stripe?: typeof mockStripe } )
			.Stripe;

		render(
			<WooPaymentsExpressCheckoutSettings methodId="payment_request" />
		);

		const stripeScript = document.querySelector(
			`script[src="${ STRIPE_SCRIPT_URL }"]`
		) as HTMLScriptElement;

		expect( stripeScript ).toBeInTheDocument();

		( window as typeof window & { Stripe?: typeof mockStripe } ).Stripe =
			stripeFactory;
		stripeScript.dispatchEvent( new Event( 'load' ) );

		await waitFor( () =>
			expect( mockExpressCheckoutMount ).toHaveBeenCalled()
		);
	} );

	it( 'allows the Stripe.js preview to retry after a script load failure', async () => {
		setWindowProtocol( 'https:' );

		const { unmount } = render(
			<WooPaymentsExpressCheckoutSettings methodId="payment_request" />
		);

		const failedStripeScript = document.querySelector(
			`script[src="${ STRIPE_SCRIPT_URL }"]`
		) as HTMLScriptElement;
		failedStripeScript.dispatchEvent( new Event( 'error' ) );

		expect(
			await screen.findByText(
				/Failed to preview the Apple Pay or Google Pay button/,
				{ selector: '.components-notice__content' }
			)
		).toBeInTheDocument();

		unmount();
		installStripeMock();
		const stripeFactory = (
			window as typeof window & { Stripe?: typeof mockStripe }
		 ).Stripe;
		delete ( window as typeof window & { Stripe?: typeof mockStripe } )
			.Stripe;

		render(
			<WooPaymentsExpressCheckoutSettings methodId="payment_request" />
		);

		const retryStripeScript = document.querySelector(
			`script[src="${ STRIPE_SCRIPT_URL }"]`
		) as HTMLScriptElement;

		expect( retryStripeScript ).toBeInTheDocument();
		expect( retryStripeScript ).not.toBe( failedStripeScript );

		( window as typeof window & { Stripe?: typeof mockStripe } ).Stripe =
			stripeFactory;
		retryStripeScript.dispatchEvent( new Event( 'load' ) );

		await waitFor( () =>
			expect( mockExpressCheckoutMount ).toHaveBeenCalled()
		);
	} );

	it( 'shows the failed-preview notice when Stripe reports no available wallets', async () => {
		setWindowProtocol( 'https:' );
		installStripeMock();
		mockExpressCheckoutOn.mockImplementation( ( eventName, callback ) => {
			if ( eventName === 'ready' ) {
				callback( { availablePaymentMethods: null } );
			}
		} );

		render(
			<WooPaymentsExpressCheckoutSettings methodId="payment_request" />
		);

		expect(
			await screen.findByText(
				/Failed to preview the Apple Pay or Google Pay button/,
				{ selector: '.components-notice__content' }
			)
		).toBeInTheDocument();
	} );

	it( 'applies the payment-methods-list override to Amazon Pay locations', async () => {
		mockUseExpressCheckoutInPaymentMethodsEnabledSettings.mockReturnValue( [
			true,
			noop,
		] );

		render( <WooPaymentsExpressCheckoutSettings methodId="amazon_pay" /> );

		expect(
			await screen.findByRole( 'heading', {
				level: 1,
				name: 'Amazon Pay',
			} )
		).toBeInTheDocument();
		expect(
			await screen.findByRole( 'img', { name: 'Amazon Pay' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'checkbox', {
				name: 'Enable Amazon Pay as an express payment button',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByLabelText( 'Show on product page' )
		).toBeDisabled();
		expect( screen.getByLabelText( 'Show on cart page' ) ).toBeDisabled();
		expect(
			screen.getByLabelText( 'Show on checkout page' )
		).toBeChecked();
		expect( screen.getByText( 'Button size' ) ).toBeInTheDocument();
	} );

	it( 'hides Amazon Pay payment-methods-list mode when native settings do not support it', async () => {
		mockUseGetSettings.mockReturnValue( {
			is_express_checkout_in_payment_methods_list_supported: false,
		} );

		render( <WooPaymentsExpressCheckoutSettings methodId="amazon_pay" /> );

		await screen.findByRole( 'heading', { level: 1, name: 'Amazon Pay' } );
		expect(
			screen.queryByRole( 'checkbox', {
				name: 'Enable express checkout methods as options in the payment methods list',
			} )
		).not.toBeInTheDocument();
	} );

	it( 'renders WooPay detail controls and blocks WooPay while Link is enabled', async () => {
		mockUseEnabledPaymentMethodIds.mockReturnValue( [
			[ 'card', 'link' ],
			noop,
		] );
		mockUseWooPayEnabledSettings.mockReturnValue( [ false, noop ] );
		mockUseWooPayStoreLogo.mockReturnValue( [ '', noop ] );

		render( <WooPaymentsExpressCheckoutSettings methodId="woopay" /> );

		expect(
			await screen.findByRole( 'heading', { level: 1, name: 'WooPay' } )
		).toBeInTheDocument();
		expect(
			( await screen.findAllByRole( 'img', { name: 'WooPay' } ) ).length
		).toBeGreaterThan( 0 );
		expect(
			screen.getByRole( 'checkbox', { name: 'Enable WooPay' } )
		).toBeDisabled();
		expect(
			screen.getByText(
				'To enable WooPay, you must first disable Link by Stripe.'
			)
		).toBeInTheDocument();
		expect(
			screen.getByText( /WooCommerce Terms of Service/ )
		).toBeInTheDocument();
		expect(
			screen.getByLabelText( 'Checkout policies' )
		).toBeInTheDocument();
		expect( screen.getByLabelText( 'Checkout logo' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'checkbox', {
				name: /Enable global theme support/,
			} )
		).toBeInTheDocument();
		expect( screen.getByText( 'Preview of checkout' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'img', { name: 'WooPay checkout preview' } )
		).toBeInTheDocument();
		expect( screen.getByText( 'Native test store' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Place order' ) ).toBeInTheDocument();
		expect(
			screen.queryByText(
				'WooPay checkout preview is shown when native preview data is available.'
			)
		).not.toBeInTheDocument();
	} );

	it( 'applies WooPay preview theme only when global theme support is enabled', async () => {
		const { container, rerender } = render(
			<WooPaymentsExpressCheckoutSettings methodId="woopay" />
		);
		const getPreviewBody = () =>
			container.querySelector( '.preview-layout__body' ) as HTMLElement;

		await screen.findByRole( 'heading', { level: 1, name: 'WooPay' } );
		expect( getPreviewBody() ).not.toHaveStyle( {
			backgroundColor: '#ffffff',
		} );

		mockUseWooPayGlobalThemeSupportEnabledSettings.mockReturnValue( [
			true,
			noop,
		] );

		rerender( <WooPaymentsExpressCheckoutSettings methodId="woopay" /> );

		await waitFor( () =>
			expect( getPreviewBody() ).toHaveStyle( {
				backgroundColor: '#ffffff',
			} )
		);
	} );

	it( 'uploads WooPay checkout logos through the payments file endpoint', async () => {
		const setLogoId = jest.fn();
		mockUseWooPayStoreLogo.mockReturnValue( [ '', setLogoId ] );

		render( <WooPaymentsExpressCheckoutSettings methodId="woopay" /> );

		const logoFile = new File( [ 'logo' ], 'logo.png', {
			type: 'image/png',
		} );

		await act( async () => {
			await userEvent.upload(
				await screen.findByLabelText( 'Checkout logo' ),
				logoFile
			);
		} );

		await waitFor( () =>
			expect( mockApiFetch ).toHaveBeenCalledWith(
				expect.objectContaining( {
					path: '/wc/v3/payments/file',
					method: 'post',
					body: expect.any( FormData ),
				} )
			)
		);

		const uploadBody = mockApiFetch.mock.calls[ 0 ][ 0 ].body as FormData;

		expect( uploadBody.get( 'file' ) ).toBe( logoFile );
		expect( uploadBody.get( 'purpose' ) ).toBe( 'business_logo' );
		expect( setLogoId ).toHaveBeenCalledWith( 'file_logo' );
		expect(
			await screen.findByText( 'Logo uploaded: logo.png' )
		).toBeInTheDocument();
	} );

	it( 'rejects WooPay checkout logos over the reference file size limit', async () => {
		const setLogoId = jest.fn();
		mockUseWooPayStoreLogo.mockReturnValue( [ '', setLogoId ] );

		render( <WooPaymentsExpressCheckoutSettings methodId="woopay" /> );

		const logoFile = new File( [ new Uint8Array( 510001 ) ], 'large.png', {
			type: 'image/png',
		} );

		await act( async () => {
			await userEvent.upload(
				await screen.findByLabelText( 'Checkout logo' ),
				logoFile
			);
		} );

		expect( mockApiFetch ).not.toHaveBeenCalled();
		expect( setLogoId ).not.toHaveBeenCalled();
		expect( mockDispatch ).not.toHaveBeenCalledWith( 'core/notices' );
		expect( mockCreateErrorNotice ).not.toHaveBeenCalled();
		expect(
			screen.getByText(
				'The selected logo exceeds the maximum file size.',
				{
					selector: '.components-notice__content',
				}
			)
		).toBeInTheDocument();
	} );

	it( 'accepts only PNG and JPEG WooPay checkout logo uploads', async () => {
		render( <WooPaymentsExpressCheckoutSettings methodId="woopay" /> );

		expect(
			await screen.findByLabelText( 'Checkout logo' )
		).toHaveAttribute( 'accept', 'image/png,image/jpeg' );
	} );
} );
