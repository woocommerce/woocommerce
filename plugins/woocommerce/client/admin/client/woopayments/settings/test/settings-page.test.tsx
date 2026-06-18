/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { WooPaymentsSettingsPage } from '../settings-page';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

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

		expect(
			screen.getByRole( 'checkbox', {
				name: /Credit \/ Debit Cards/,
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'checkbox', { name: /Affirm/ } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'checkbox', { name: 'Amazon Pay' } )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'checkbox', { name: 'Link by Stripe' } )
		).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'checkbox', { name: 'Enable Amazon Pay' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'checkbox', { name: 'Enable Link by Stripe' } )
		).toBeDisabled();
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

	it( 'groups repeated express checkout location controls by payment method', () => {
		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getByRole( 'group', {
				name: 'Apple Pay and Google Pay locations',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'group', { name: 'Amazon Pay locations' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'group', { name: 'WooPay locations' } )
		).toBeInTheDocument();
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
