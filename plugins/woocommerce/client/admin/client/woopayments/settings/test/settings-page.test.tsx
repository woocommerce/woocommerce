/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { fireEvent, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { WooPaymentsSettingsPage } from '../settings-page';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

const mockSaveSettings = jest.fn();
const mockUseSettings = jest.fn();
const mockUseGetSettings = jest.fn();
const mockUseSavedCards = jest.fn();
const mockUseCardPresentEligible = jest.fn();
const mockUseEnabledPaymentMethodIds = jest.fn();
const mockUseDebugLog = jest.fn();
const mockUseTestMode = jest.fn();
const mockUseMultiCurrency = jest.fn();
const mockUseAccountStatementDescriptor = jest.fn();
const mockUseAccountStatementDescriptorKanji = jest.fn();
const mockUseAccountStatementDescriptorKana = jest.fn();
const mockUseAccountBusinessSupportEmail = jest.fn();
const mockUseAccountBusinessSupportPhone = jest.fn();
const mockUseDepositScheduleInterval = jest.fn();
const mockUseDepositScheduleWeeklyAnchor = jest.fn();
const mockUseDepositScheduleMonthlyAnchor = jest.fn();
const mockUseManualCapture = jest.fn();
const mockUseIsWCPayEnabled = jest.fn();
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
const mockUseCurrentProtectionLevel = jest.fn();
const mockUseAdvancedFraudProtectionSettings = jest.fn();
const mockUseAccountCommunicationsEmail = jest.fn();
const mockUseAccountDomesticCurrency = jest.fn();
const mockUseSelectedPaymentMethod = jest.fn();
const mockUseUnselectedPaymentMethod = jest.fn();
const mockUseTestModeOnboarding = jest.fn();
const mockUseDevMode = jest.fn();
const mockUseWCPaySubscriptions = jest.fn();
const mockUseDepositDelayDays = jest.fn();
const mockUseCompletedWaitingPeriod = jest.fn();
const mockUseDepositStatus = jest.fn();
const mockUseDepositRestrictions = jest.fn();
const mockUseGetAvailablePaymentMethodIds = jest.fn();
const mockUseGetPaymentMethodStatuses = jest.fn();
const mockUseGetDuplicatedPaymentMethodIds = jest.fn();
const mockUseGetAccountFees = jest.fn();
const mockUseDismissedDuplicatePaymentMethodNotices = jest.fn();
const mockUsePaymentRequestLocations = jest.fn();
const mockUseWooPayLocations = jest.fn();
const mockUseAmazonPayLocations = jest.fn();
const mockUseAmazonPayEnabledSettings = jest.fn();
const mockUseLinkEnabledSettings = jest.fn();
const mockUseWooPayShowIncompatibilityNotice = jest.fn();
const mockUseGetSavingError = jest.fn();

jest.mock( '../data/hooks', () => ( {
	useSettings: () => mockUseSettings(),
	useGetSettings: () => mockUseGetSettings(),
	useSavedCards: () => mockUseSavedCards(),
	useCardPresentEligible: () => mockUseCardPresentEligible(),
	useEnabledPaymentMethodIds: () => mockUseEnabledPaymentMethodIds(),
	useDebugLog: () => mockUseDebugLog(),
	useTestMode: () => mockUseTestMode(),
	useMultiCurrency: () => mockUseMultiCurrency(),
	useAccountStatementDescriptor: () => mockUseAccountStatementDescriptor(),
	useAccountStatementDescriptorKanji: () =>
		mockUseAccountStatementDescriptorKanji(),
	useAccountStatementDescriptorKana: () =>
		mockUseAccountStatementDescriptorKana(),
	useAccountBusinessSupportEmail: () => mockUseAccountBusinessSupportEmail(),
	useAccountBusinessSupportPhone: () => mockUseAccountBusinessSupportPhone(),
	useDepositScheduleInterval: () => mockUseDepositScheduleInterval(),
	useDepositScheduleWeeklyAnchor: () => mockUseDepositScheduleWeeklyAnchor(),
	useDepositScheduleMonthlyAnchor: () =>
		mockUseDepositScheduleMonthlyAnchor(),
	useManualCapture: () => mockUseManualCapture(),
	useIsWCPayEnabled: () => mockUseIsWCPayEnabled(),
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
	useCurrentProtectionLevel: () => mockUseCurrentProtectionLevel(),
	useAdvancedFraudProtectionSettings: () =>
		mockUseAdvancedFraudProtectionSettings(),
	useAccountCommunicationsEmail: () => mockUseAccountCommunicationsEmail(),
	useAccountDomesticCurrency: () => mockUseAccountDomesticCurrency(),
	useSelectedPaymentMethod: () => mockUseSelectedPaymentMethod(),
	useUnselectedPaymentMethod: () => mockUseUnselectedPaymentMethod(),
	useTestModeOnboarding: () => mockUseTestModeOnboarding(),
	useDevMode: () => mockUseDevMode(),
	useWCPaySubscriptions: () => mockUseWCPaySubscriptions(),
	useDepositDelayDays: () => mockUseDepositDelayDays(),
	useCompletedWaitingPeriod: () => mockUseCompletedWaitingPeriod(),
	useDepositStatus: () => mockUseDepositStatus(),
	useDepositRestrictions: () => mockUseDepositRestrictions(),
	useGetAvailablePaymentMethodIds: () =>
		mockUseGetAvailablePaymentMethodIds(),
	useGetPaymentMethodStatuses: () => mockUseGetPaymentMethodStatuses(),
	useGetDuplicatedPaymentMethodIds: () =>
		mockUseGetDuplicatedPaymentMethodIds(),
	useGetAccountFees: () => mockUseGetAccountFees(),
	useDismissedDuplicatePaymentMethodNotices: () =>
		mockUseDismissedDuplicatePaymentMethodNotices(),
	usePaymentRequestLocations: () => mockUsePaymentRequestLocations(),
	useWooPayLocations: () => mockUseWooPayLocations(),
	useAmazonPayLocations: () => mockUseAmazonPayLocations(),
	useAmazonPayEnabledSettings: () => mockUseAmazonPayEnabledSettings(),
	useLinkEnabledSettings: () => mockUseLinkEnabledSettings(),
	useWooPayShowIncompatibilityNotice: () =>
		mockUseWooPayShowIncompatibilityNotice(),
	useGetSavingError: () => mockUseGetSavingError(),
} ) );

const noop = jest.fn();

const setHookDefaults = () => {
	mockUseSettings.mockReturnValue( {
		isLoading: false,
		isSaving: false,
		isDirty: true,
		saveSettings: mockSaveSettings,
	} );
	mockUseGetSettings.mockReturnValue( {
		account_country: 'US',
		available_payment_method_ids: [
			'card',
			'link',
			'affirm',
			'amazon_pay',
			'apple_pay',
			'google_pay',
		],
	} );
	mockUseSavedCards.mockReturnValue( [ true, noop ] );
	mockUseCardPresentEligible.mockReturnValue( [ true, noop ] );
	mockUseEnabledPaymentMethodIds.mockReturnValue( [
		[ 'card', 'link', 'amazon_pay', 'affirm' ],
		noop,
	] );
	mockUseDebugLog.mockReturnValue( [ false, noop ] );
	mockUseTestMode.mockReturnValue( [ false, noop ] );
	mockUseMultiCurrency.mockReturnValue( [ true, noop ] );
	mockUseAccountStatementDescriptor.mockReturnValue( [ 'MY STORE', noop ] );
	mockUseAccountStatementDescriptorKanji.mockReturnValue( [ '', noop ] );
	mockUseAccountStatementDescriptorKana.mockReturnValue( [ '', noop ] );
	mockUseAccountBusinessSupportEmail.mockReturnValue( [
		'support@example.com',
		noop,
	] );
	mockUseAccountBusinessSupportPhone.mockReturnValue( [
		'+15555555555',
		noop,
	] );
	mockUseDepositScheduleInterval.mockReturnValue( [ 'weekly', noop ] );
	mockUseDepositScheduleWeeklyAnchor.mockReturnValue( [ 'monday', noop ] );
	mockUseDepositScheduleMonthlyAnchor.mockReturnValue( [ '1', noop ] );
	mockUseManualCapture.mockReturnValue( [ false, noop ] );
	mockUseIsWCPayEnabled.mockReturnValue( [ true, noop ] );
	mockUsePaymentRequestEnabledSettings.mockReturnValue( [ true, noop ] );
	mockUseExpressCheckoutInPaymentMethodsEnabledSettings.mockReturnValue( [
		true,
		noop,
	] );
	mockUsePaymentRequestButtonType.mockReturnValue( [ 'default', noop ] );
	mockUsePaymentRequestButtonSize.mockReturnValue( [ 'default', noop ] );
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
	mockUseCurrentProtectionLevel.mockReturnValue( [ 'standard', noop ] );
	mockUseAdvancedFraudProtectionSettings.mockReturnValue( [ [], noop ] );
	mockUseAccountCommunicationsEmail.mockReturnValue( [
		'owner@example.com',
		noop,
	] );
	mockUseAccountDomesticCurrency.mockReturnValue( 'USD' );
	mockUseSelectedPaymentMethod.mockReturnValue( [
		[ 'card', 'link', 'amazon_pay', 'affirm' ],
		noop,
	] );
	mockUseUnselectedPaymentMethod.mockReturnValue( [
		[ 'card', 'link', 'amazon_pay', 'affirm' ],
		noop,
	] );
	mockUseTestModeOnboarding.mockReturnValue( false );
	mockUseDevMode.mockReturnValue( false );
	mockUseWCPaySubscriptions.mockReturnValue( [ true, true, noop ] );
	mockUseDepositDelayDays.mockReturnValue( 2 );
	mockUseCompletedWaitingPeriod.mockReturnValue( true );
	mockUseDepositStatus.mockReturnValue( 'enabled' );
	mockUseDepositRestrictions.mockReturnValue( '' );
	mockUseGetAvailablePaymentMethodIds.mockReturnValue( [
		'card',
		'link',
		'affirm',
		'amazon_pay',
		'apple_pay',
		'google_pay',
	] );
	mockUseGetPaymentMethodStatuses.mockReturnValue( {
		card_payments: { status: 'active' },
		link_payments: { status: 'active' },
		affirm_payments: { status: 'active' },
		amazon_pay_payments: { status: 'active' },
	} );
	mockUseGetDuplicatedPaymentMethodIds.mockReturnValue( [] );
	mockUseGetAccountFees.mockReturnValue( {} );
	mockUseDismissedDuplicatePaymentMethodNotices.mockReturnValue( [
		{},
		noop,
	] );
	mockUsePaymentRequestLocations.mockReturnValue( [
		[ 'product', 'cart', 'checkout' ],
		noop,
	] );
	mockUseWooPayLocations.mockReturnValue( [
		[ 'product', 'cart', 'checkout' ],
		noop,
	] );
	mockUseAmazonPayLocations.mockReturnValue( [ [ 'checkout' ], noop ] );
	mockUseAmazonPayEnabledSettings.mockReturnValue( [ true, noop ] );
	mockUseLinkEnabledSettings.mockReturnValue( [ false, noop, true ] );
	mockUseWooPayShowIncompatibilityNotice.mockReturnValue( false );
	mockUseGetSavingError.mockReturnValue( null );
};

describe( 'WooPaymentsSettingsPage', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockApiFetch.mockResolvedValue( {} );
		setHookDefaults();
	} );

	it( 'renders the native settings manager sections', () => {
		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getByRole( 'heading', { name: 'WooPayments settings' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'heading', { name: 'General' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'heading', {
				name: 'Payments accepted on checkout',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'heading', { name: 'Buy now, pay later' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'heading', { name: 'Express checkouts' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'heading', { name: 'WooPay' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'heading', { name: 'Transactions' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'heading', { name: 'Payouts' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'heading', { name: 'Account notifications' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'heading', { name: 'Fraud protection' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'heading', { name: 'Advanced settings' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Save changes' } )
		).toBeInTheDocument();
	} );

	it( 'keeps express payment methods out of the standard payment methods list', () => {
		render( <WooPaymentsSettingsPage /> );

		const paymentMethodsGroup = screen
			.getByRole( 'heading', { name: 'Payment methods' } )
			.closest( '.woopayments-settings-field-group' ) as HTMLElement;
		const expressCheckoutsSection = screen
			.getByRole( 'heading', { name: 'Express checkouts' } )
			.closest( '.woopayments-settings-section' ) as HTMLElement;

		expect(
			within( paymentMethodsGroup ).getByRole( 'checkbox', {
				name: /Credit \/ Debit Cards/,
			} )
		).toBeInTheDocument();
		expect(
			within( paymentMethodsGroup ).queryByRole( 'checkbox', {
				name: 'Amazon Pay',
			} )
		).not.toBeInTheDocument();
		expect(
			within( paymentMethodsGroup ).queryByRole( 'checkbox', {
				name: 'Link by Stripe',
			} )
		).not.toBeInTheDocument();
		expect(
			within( expressCheckoutsSection ).getByRole( 'checkbox', {
				name: 'Amazon Pay',
			} )
		).toBeInTheDocument();
		expect(
			within( expressCheckoutsSection ).getByRole( 'checkbox', {
				name: 'Link by Stripe',
			} )
		).toBeDisabled();
	} );

	it( 'hides Link by Stripe when card payments are not enabled', () => {
		mockUseEnabledPaymentMethodIds.mockReturnValue( [
			[ 'amazon_pay', 'affirm' ],
			noop,
		] );
		mockUseSelectedPaymentMethod.mockReturnValue( [
			[ 'amazon_pay', 'affirm' ],
			noop,
		] );
		mockUseUnselectedPaymentMethod.mockReturnValue( [
			[ 'amazon_pay', 'affirm' ],
			noop,
		] );
		mockUseLinkEnabledSettings.mockReturnValue( [ false, noop, false ] );

		render( <WooPaymentsSettingsPage /> );

		const expressCheckoutsSection = screen
			.getByRole( 'heading', { name: 'Express checkouts' } )
			.closest( '.woopayments-settings-section' ) as HTMLElement;

		expect(
			within( expressCheckoutsSection ).queryByRole( 'checkbox', {
				name: 'Link by Stripe',
			} )
		).not.toBeInTheDocument();
	} );

	it( 'renders payment methods with the reference row content', () => {
		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getByText(
				'Let your customers pay with major credit and debit cards without leaving your store.'
			)
		).toBeInTheDocument();
		expect( screen.getByText( '(Required)' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'img', { name: 'Credit / Debit Cards logo' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'img', { name: 'Visa' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'img', { name: 'Mastercard' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'img', { name: 'American Express' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'img', { name: 'Cartes Bancaires' } )
		).toBeInTheDocument();
	} );

	it( 'renders reference fee and discount details on payment method rows', async () => {
		mockUseGetAvailablePaymentMethodIds.mockReturnValue( [
			'card',
			'alipay',
			'affirm',
			'amazon_pay',
			'apple_pay',
			'google_pay',
		] );
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active' },
			alipay_payments: { status: 'active' },
			affirm_payments: { status: 'active' },
			amazon_pay_payments: { status: 'active' },
		} );
		mockUseGetAccountFees.mockReturnValue( {
			card: {
				base: {
					percentage_rate: 0.029,
					fixed_rate: 30,
					currency: 'USD',
				},
				additional: {
					percentage_rate: 0.01,
					fixed_rate: 0,
					currency: 'USD',
				},
				fx: {
					percentage_rate: 0.01,
					fixed_rate: 0,
					currency: 'USD',
				},
				discount: [
					{
						currency: 'USD',
						discount: 0.1,
						end_time: '2026-02-27 04:20:49',
						volume_allowance: 100000,
						volume_currency: 'USD',
					},
				],
			},
			alipay: {
				base: {
					percentage_rate: 0,
					fixed_rate: 300,
					currency: 'JPY',
				},
				additional: {
					percentage_rate: 0,
					fixed_rate: 0,
					currency: 'JPY',
				},
				fx: {
					percentage_rate: 0,
					fixed_rate: 0,
					currency: 'JPY',
				},
				discount: [],
			},
		} );

		render( <WooPaymentsSettingsPage /> );

		const cardFeeButton = screen.getByRole( 'button', {
			name: 'From 2.61% + $0.27 fee details',
		} );
		const zeroDecimalFeeButton = screen.getByRole( 'button', {
			name: 'From 0% + ¥300 fee details',
		} );
		const discountBadge = screen.getByText( /10% off fees through/ );

		expect( cardFeeButton ).toBeInTheDocument();
		expect( zeroDecimalFeeButton ).toBeInTheDocument();
		expect( discountBadge ).not.toHaveAttribute( 'title' );
		expect( discountBadge ).toHaveAccessibleDescription(
			expect.stringContaining(
				'first $1,000.00 of total payment volume or through'
			)
		);

		await userEvent.click( cardFeeButton );
		expect(
			screen.getAllByText( 'Base fee' ).length
		).toBeGreaterThanOrEqual( 1 );
		expect( screen.getByText( '2.61% + $0.27' ) ).toBeInTheDocument();
		expect( screen.getByText( '0.9%' ) ).toBeInTheDocument();
		expect( screen.getAllByText( '1%' ).length ).toBeGreaterThanOrEqual(
			1
		);
		expect(
			screen.getAllByText( 'Total per transaction' ).length
		).toBeGreaterThanOrEqual( 1 );
		expect( screen.getByText( '4.51% + $0.27' ) ).toBeInTheDocument();

		await userEvent.keyboard( '{Escape}' );
		expect( cardFeeButton ).toHaveAttribute( 'aria-expanded', 'false' );
	} );

	it( 'renders and dismisses duplicate payment method notices', async () => {
		const updateDismissedDuplicateNotices = jest.fn();
		mockUseGetAvailablePaymentMethodIds.mockReturnValue( [
			'card',
			'alipay',
			'affirm',
			'amazon_pay',
			'apple_pay',
			'google_pay',
		] );
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active' },
			alipay_payments: { status: 'active' },
			affirm_payments: { status: 'active' },
			amazon_pay_payments: { status: 'active' },
		} );
		mockUseGetDuplicatedPaymentMethodIds.mockReturnValue( {
			alipay: [ 'woocommerce_payments_alipay', 'legacy_alipay_gateway' ],
		} );
		mockUseDismissedDuplicatePaymentMethodNotices.mockReturnValue( [
			{},
			updateDismissedDuplicateNotices,
		] );

		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getAllByText( ( _content, element ) =>
				Boolean(
					element?.textContent?.includes(
						'This payment method is enabled by other extensions.'
					)
				)
			).length
		).toBeGreaterThan( 0 );
		expect(
			screen.getByRole( 'link', { name: 'Review extensions' } )
		).toHaveAttribute( 'href', 'admin.php?page=wc-settings&tab=checkout' );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Close' } )
		);

		expect( updateDismissedDuplicateNotices ).toHaveBeenCalledWith( {
			alipay: [ 'woocommerce_payments_alipay', 'legacy_alipay_gateway' ],
		} );
		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/settings/wcpay_duplicate_payment_method_notices_dismissed',
			method: 'post',
			data: {
				value: {
					alipay: [
						'woocommerce_payments_alipay',
						'legacy_alipay_gateway',
					],
				},
			},
		} );
		expect(
			screen.getByRole( 'checkbox', { name: 'Alipay' } )
		).toHaveFocus();
	} );

	it( 'does not stack duplicate payment method notices with status notices', () => {
		mockUseGetDuplicatedPaymentMethodIds.mockReturnValue( {
			affirm: [ 'woocommerce_payments_affirm', 'legacy_affirm_gateway' ],
		} );
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active' },
			affirm_payments: {
				status: 'inactive',
				requirements: [ 'business_profile.url' ],
			},
		} );

		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getByText(
				'More information is needed to finish setting up this payment method.'
			)
		).toBeInTheDocument();
		expect(
			screen.queryByText( ( _content, element ) =>
				Boolean(
					element?.textContent?.includes(
						'This payment method is enabled by other extensions.'
					)
				)
			)
		).not.toBeInTheDocument();
	} );

	it( 'links customizable express checkout rows to native provider settings routes', () => {
		render( <WooPaymentsSettingsPage /> );

		const customizeLinks = [
			screen.getByRole( 'link', { name: 'Customize WooPay' } ),
			screen.getByRole( 'link', {
				name: 'Customize Apple Pay / Google Pay',
			} ),
			screen.getByRole( 'link', { name: 'Customize Amazon Pay' } ),
		];

		expect( customizeLinks ).toHaveLength( 3 );
		expect( customizeLinks[ 0 ] ).toHaveAttribute(
			'href',
			expect.stringContaining(
				'path=%2Fwoopayments%2Fsettings%2Fexpress-checkout%2Fwoopay'
			)
		);
		expect( customizeLinks[ 1 ] ).toHaveAttribute(
			'href',
			expect.stringContaining(
				'path=%2Fwoopayments%2Fsettings%2Fexpress-checkout%2Fpayment_request'
			)
		);
		expect( customizeLinks[ 2 ] ).toHaveAttribute(
			'href',
			expect.stringContaining(
				'path=%2Fwoopayments%2Fsettings%2Fexpress-checkout%2Famazon_pay'
			)
		);
	} );

	it( 'shows the manual-capture conflict banner and disables incompatible methods', () => {
		mockUseManualCapture.mockReturnValue( [ true, noop ] );
		mockUseGetAvailablePaymentMethodIds.mockReturnValue( [
			'card',
			'affirm',
			'klarna',
		] );

		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getAllByText(
				"Manual capture is enabled, so any payment methods that don't support it have been automatically disabled."
			).length
		).toBeGreaterThanOrEqual( 1 );
		expect(
			screen.getByRole( 'checkbox', { name: /Affirm/ } )
		).toBeDisabled();
		expect(
			screen.getByRole( 'checkbox', { name: /Klarna/ } )
		).toBeDisabled();
		expect(
			screen.getAllByText( 'Unavailable with manual capture' )
		).toHaveLength( 2 );
		expect(
			screen.queryByRole( 'button', { name: 'Dismiss this notice' } )
		).not.toBeInTheDocument();
	} );

	it( 'uses account-country branding for Afterpay and Clearpay settings rows', () => {
		mockUseGetAvailablePaymentMethodIds.mockReturnValue( [
			'card',
			'afterpay_clearpay',
		] );
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active', requirements: [] },
			afterpay_clearpay_payments: {
				status: 'active',
				requirements: [],
			},
		} );

		const { rerender } = render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getByRole( 'checkbox', { name: 'Cash App Afterpay' } )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Allow customers to pay over time with Cash App Afterpay.'
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'img', { name: 'Cash App Afterpay logo' } )
		).toBeInTheDocument();

		mockUseGetSettings.mockReturnValue( {
			account_country: 'GB',
		} );

		rerender( <WooPaymentsSettingsPage /> );

		expect(
			screen.getByRole( 'checkbox', { name: 'Clearpay' } )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Allow customers to pay over time with Clearpay.'
			)
		).toBeInTheDocument();
	} );

	it( 'confirms unrequested payment method activation before enabling the method', () => {
		const selectPaymentMethod = jest.fn();
		mockUseEnabledPaymentMethodIds.mockReturnValue( [ [ 'card' ], noop ] );
		mockUseSelectedPaymentMethod.mockReturnValue( [
			[ 'card' ],
			selectPaymentMethod,
		] );
		mockUseGetAvailablePaymentMethodIds.mockReturnValue( [
			'card',
			'affirm',
		] );
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active', requirements: [] },
			affirm_payments: {
				status: 'unrequested',
				requirements: [ 'business_profile.mcc' ],
			},
		} );

		render( <WooPaymentsSettingsPage /> );

		fireEvent.click( screen.getByRole( 'checkbox', { name: /Affirm/ } ) );

		expect( selectPaymentMethod ).not.toHaveBeenCalled();
		expect(
			screen.getByRole( 'heading', {
				name: 'One more step to enable Affirm',
			} )
		).toBeInTheDocument();

		fireEvent.click( screen.getByRole( 'button', { name: 'Continue' } ) );

		expect( selectPaymentMethod ).toHaveBeenCalledWith( 'affirm' );
	} );

	it( 'renders disabled notices for payment methods that need account information', () => {
		mockUseGetAvailablePaymentMethodIds.mockReturnValue( [
			'card',
			'alipay',
		] );
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active', requirements: [] },
			alipay_payments: { status: 'inactive', requirements: [] },
		} );

		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getByText( 'More information needed' )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'More information is needed to finish setting up this payment method.'
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'checkbox', { name: 'Alipay' } )
		).toBeDisabled();
	} );

	it( 'requires confirmation before enabling test mode', () => {
		const setTestMode = jest.fn();
		mockUseTestMode.mockReturnValue( [ false, setTestMode ] );

		render( <WooPaymentsSettingsPage /> );

		fireEvent.click(
			screen.getByRole( 'checkbox', { name: 'Enable test mode' } )
		);

		expect( setTestMode ).not.toHaveBeenCalled();
		expect(
			screen.getByRole( 'heading', {
				name: 'Are you sure you want to enable test mode?',
			} )
		).toBeInTheDocument();

		fireEvent.click( screen.getByRole( 'button', { name: 'Enable' } ) );

		expect( setTestMode ).toHaveBeenCalledWith( true );
	} );

	it( 'keeps express checkout detail controls out of the overview page', () => {
		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.queryByLabelText( 'Show on product page' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByLabelText( 'Show on cart page' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByLabelText( 'Show on checkout page' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'combobox', { name: 'Call to action' } )
		).not.toBeInTheDocument();
	} );

	it( 'does not render payout schedule controls while scheduling is unavailable', () => {
		mockUseDepositStatus.mockReturnValue( 'restricted' );

		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getByText(
				'Payout scheduling is currently unavailable for this account.'
			)
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'combobox', { name: 'Frequency' } )
		).not.toBeInTheDocument();
	} );

	it( 'keeps Stripe Billing migration UI out of the native settings page', () => {
		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.queryByText( /Stripe Billing/i )
		).not.toBeInTheDocument();
		expect( screen.queryByText( /migration/i ) ).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: /migrate/i } )
		).not.toBeInTheDocument();
	} );
} );
