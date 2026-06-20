/**
 * External dependencies
 */
import { speak } from '@wordpress/a11y';
import apiFetch from '@wordpress/api-fetch';
import {
	act,
	fireEvent,
	render,
	screen,
	waitFor,
	within,
} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { WooPaymentsSettingsPage } from '../settings-page';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
jest.mock( '@wordpress/a11y', () => ( {
	speak: jest.fn(),
} ) );

jest.mock( '../../promotions/spotlight', () => ( {
	SpotlightPromotion: () => <div>Spotlight promotion</div>,
} ) );

const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;
const mockSpeak = speak as jest.MockedFunction< typeof speak >;

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
		'+12015555555',
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
		mockApiFetch.mockImplementation( ( options ) => {
			const path = typeof options === 'string' ? options : options?.path;

			if ( path === '/wc-admin/settings/payments/woopayments/account' ) {
				return Promise.resolve( {
					account: {
						id: 'acct_live',
						mode: 'live',
						default_currency: 'usd',
						connected: true,
						working: true,
						can_process_payments: true,
						test_mode: false,
						test_drive: false,
						sandbox: false,
						live: true,
					},
					urls: {
						setup: 'admin.php?page=wc-settings&tab=checkout&path=/woopayments/onboarding',
					},
				} );
			}

			if ( path === '/wc/v3/payments/deposits/overview-all' ) {
				return new Promise( () => {} );
			}

			return Promise.resolve( {} );
		} );
		setHookDefaults();
	} );

	it( 'renders the native settings manager sections', () => {
		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getByRole( 'heading', { name: 'WooPayments settings' } )
		).toBeInTheDocument();
		expect( screen.getByText( 'Spotlight promotion' ) ).toBeInTheDocument();
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

	it( 'renders badge payment method promotions on matching payment method rows', async () => {
		mockUseGetSettings.mockReturnValue( {
			account_country: 'US',
			pm_promotions: [
				{
					id: 'klarna-badge',
					promo_id: 'klarna-promo',
					payment_method: 'klarna',
					type: 'badge',
					title: 'Limited offer',
					description: 'Lower fees are available for Klarna.',
					tc_url: 'https://example.com/terms',
					tc_label: 'See terms',
					badge_type: 'success',
				},
				{
					id: 'affirm-spotlight',
					promo_id: 'affirm-promo',
					payment_method: 'affirm',
					type: 'spotlight',
					title: 'Activate Affirm',
				},
			],
		} );
		mockUseGetAvailablePaymentMethodIds.mockReturnValue( [
			'card',
			'klarna',
			'affirm',
		] );
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active' },
			klarna_payments: { status: 'active' },
			affirm_payments: { status: 'active' },
		} );
		mockUseGetAccountFees.mockReturnValue( {
			klarna: {
				base: {
					percentage_rate: 0.0599,
					fixed_rate: 30,
					currency: 'USD',
				},
			},
		} );

		const { container } = render( <WooPaymentsSettingsPage /> );

		const badge = screen.getByRole( 'button', {
			name: 'Limited offer promotion details',
		} );
		const klarnaRow = Array.from(
			container.querySelectorAll(
				'.woopayments-settings-payment-method-item'
			)
		).find( ( row ) => row.textContent?.includes( 'Klarna' ) );
		expect( klarnaRow ).toBeDefined();
		const feeButton = within( klarnaRow as HTMLElement ).getByRole(
			'button',
			{
				name: 'From 5.99% + $0.30 fee details',
			}
		);

		expect( badge ).toBeInTheDocument();
		expect(
			badge.closest(
				'.woopayments-settings-payment-method-item__heading'
			)
		).not.toBeNull();
		expect(
			feeButton.closest(
				'.woopayments-settings-payment-method-item__actions'
			)
		).not.toBeNull();
		expect(
			feeButton.closest(
				'.woopayments-settings-payment-method-item__heading'
			)
		).toBeNull();
		expect(
			screen.queryByText( 'Activate Affirm' )
		).not.toBeInTheDocument();

		await userEvent.click( badge );

		const detailsDialog = screen.getByRole( 'dialog', {
			name: 'Limited offer promotion details',
		} );
		expect(
			within( detailsDialog ).getByText(
				'Lower fees are available for Klarna.'
			)
		).toBeInTheDocument();
		expect(
			within( detailsDialog ).getByRole( 'link', { name: /See terms/ } )
		).toHaveAttribute( 'href', 'https://example.com/terms' );

		const termsLink = within( detailsDialog ).getByRole( 'link', {
			name: /See terms/,
		} );
		termsLink.focus();
		expect( termsLink ).toHaveFocus();
		fireEvent.keyDown( termsLink, { key: 'Escape' } );

		await waitFor( () => {
			expect(
				screen.queryByRole( 'dialog', {
					name: 'Limited offer promotion details',
				} )
			).not.toBeInTheDocument();
		} );
		expect( badge ).toHaveFocus();
	} );

	it( 'keeps active discount badges ahead of payment method promotion badges', () => {
		mockUseGetSettings.mockReturnValue( {
			account_country: 'US',
			pm_promotions: [
				{
					id: 'klarna-badge',
					promo_id: 'klarna-promo',
					payment_method: 'klarna',
					type: 'badge',
					title: 'Limited offer',
					description: 'Lower fees are available for Klarna.',
					badge_type: 'success',
				},
			],
		} );
		mockUseGetAvailablePaymentMethodIds.mockReturnValue( [
			'card',
			'klarna',
		] );
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active' },
			klarna_payments: { status: 'active' },
		} );
		mockUseGetAccountFees.mockReturnValue( {
			klarna: {
				base: {
					percentage_rate: 0.029,
					fixed_rate: 30,
					currency: 'USD',
				},
				discount: [
					{
						currency: 'USD',
						discount: 0.2,
					},
				],
			},
		} );

		render( <WooPaymentsSettingsPage /> );

		expect( screen.getByText( '20% off fees' ) ).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', {
				name: 'Limited offer promotion details',
			} )
		).not.toBeInTheDocument();
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

	it( 'renders express checkout legal links and read-more actions', () => {
		mockUseWooPayEnabledSettings.mockReturnValue( [ false, noop ] );
		mockUsePaymentRequestEnabledSettings.mockReturnValue( [ false, noop ] );
		mockUseAmazonPayEnabledSettings.mockReturnValue( [ false, noop ] );

		render( <WooPaymentsSettingsPage /> );

		const expressCheckoutsSection = screen
			.getByRole( 'heading', { name: 'Express checkouts' } )
			.closest( '.woopayments-settings-section' ) as HTMLElement;

		expect(
			within( expressCheckoutsSection ).getByRole( 'link', {
				name: /^WooPay/,
			} )
		).toHaveAttribute(
			'href',
			'https://woocommerce.com/document/woopay-merchant-documentation/'
		);
		const linkHrefs = within( expressCheckoutsSection )
			.getAllByRole( 'link' )
			.map( ( link ) => link.getAttribute( 'href' ) );

		expect( linkHrefs ).toEqual(
			expect.arrayContaining( [
				'https://wordpress.com/tos/',
				'https://automattic.com/privacy/',
				'https://woocommerce.com/usage-tracking/',
				'https://stripe.com/apple-pay/legal',
				'https://developer.apple.com/apple-pay/acceptable-use-guidelines-for-websites/',
				'https://androidpay.developers.google.com/terms/sellertos',
				'https://link.com/terms',
				'https://link.com/privacy',
				'https://woocommerce.com/document/woopayments/payment-methods/link-by-stripe/',
				'https://stripe.com/legal/ssa',
				'https://stripe.com/legal/amazon-pay',
			] )
		);
	} );

	it( 'uses payment method status to disable Amazon Pay in the express checkout overview', () => {
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active', requirements: [] },
			link_payments: { status: 'active', requirements: [] },
			amazon_pay_payments: {
				status: 'inactive',
				requirements: [ 'business_profile.url' ],
			},
		} );

		render( <WooPaymentsSettingsPage /> );

		const expressCheckoutsSection = screen
			.getByRole( 'heading', { name: 'Express checkouts' } )
			.closest( '.woopayments-settings-section' ) as HTMLElement;

		expect(
			within( expressCheckoutsSection ).getByRole( 'checkbox', {
				name: 'Amazon Pay',
			} )
		).toBeDisabled();
		expect(
			within( expressCheckoutsSection ).getByText(
				'More information is needed to finish setting up this payment method.'
			)
		).toBeInTheDocument();
		expect(
			within( expressCheckoutsSection )
				.getAllByRole( 'link', { name: /Learn more/ } )
				.find(
					( link ) =>
						link.getAttribute( 'href' ) ===
						'https://woocommerce.com/document/woopayments/payment-methods/additional-payment-methods/#method-cant-be-enabled'
				)
		).toBeInTheDocument();
	} );

	it( 'renders Amazon Pay rejected status as an error notice in the express checkout overview', () => {
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active', requirements: [] },
			link_payments: { status: 'active', requirements: [] },
			amazon_pay_payments: {
				status: 'rejected',
				requirements: [],
			},
		} );

		render( <WooPaymentsSettingsPage /> );

		const expressCheckoutsSection = screen
			.getByRole( 'heading', { name: 'Express checkouts' } )
			.closest( '.woopayments-settings-section' ) as HTMLElement;
		const rejectedNotice = within( expressCheckoutsSection )
			.getByText(
				'Your application to use Amazon Pay has been rejected. Need help? Contact support'
			)
			.closest( '.components-notice' );

		expect( rejectedNotice ).toHaveClass( 'is-error' );
	} );

	it( 'renders and dismisses duplicate notices for Apple Pay and Google Pay express buttons', async () => {
		const updateDismissedDuplicateNotices = jest.fn();
		mockUseGetDuplicatedPaymentMethodIds.mockReturnValue( {
			apple_pay_google_pay: [
				'woocommerce_payments',
				'legacy_apple_pay_gateway',
			],
		} );
		mockUseDismissedDuplicatePaymentMethodNotices.mockReturnValue( [
			{},
			updateDismissedDuplicateNotices,
		] );

		render( <WooPaymentsSettingsPage /> );

		const expressCheckoutsSection = screen
			.getByRole( 'heading', { name: 'Express checkouts' } )
			.closest( '.woopayments-settings-section' ) as HTMLElement;

		expect(
			within( expressCheckoutsSection ).getAllByText(
				( _content, element ) =>
					Boolean(
						element?.textContent?.includes(
							'This payment method is enabled by other extensions.'
						)
					)
			).length
		).toBeGreaterThan( 0 );
		expect(
			within( expressCheckoutsSection ).getByRole( 'link', {
				name: 'Review extensions',
			} )
		).toHaveAttribute( 'href', 'admin.php?page=wc-settings&tab=checkout' );

		await userEvent.click(
			within( expressCheckoutsSection ).getByRole( 'button', {
				name: 'Close',
			} )
		);

		expect( updateDismissedDuplicateNotices ).toHaveBeenCalledWith( {
			apple_pay_google_pay: [
				'woocommerce_payments',
				'legacy_apple_pay_gateway',
			],
		} );
		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/settings/wcpay_duplicate_payment_method_notices_dismissed',
			method: 'post',
			data: {
				value: {
					apple_pay_google_pay: [
						'woocommerce_payments',
						'legacy_apple_pay_gateway',
					],
				},
			},
		} );
		expect(
			within( expressCheckoutsSection ).getByRole( 'checkbox', {
				name: 'Apple Pay / Google Pay',
			} )
		).toHaveFocus();
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

	it( 'requires confirmation before enabling manual capture', async () => {
		const setManualCapture = jest.fn();
		mockUseManualCapture.mockReturnValue( [ false, setManualCapture ] );

		render( <WooPaymentsSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', {
				name: 'Issue an authorization on checkout and capture later',
			} )
		);

		expect( setManualCapture ).not.toHaveBeenCalled();
		expect(
			screen.getByRole( 'dialog', { name: 'Enable manual capture' } )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				/Payments must be captured on the order details screen within 7 days of authorization/
			)
		).toBeInTheDocument();
		expect(
			within(
				screen.getByRole( 'dialog', { name: 'Enable manual capture' } )
			).getByText(
				"Manual capture is available for card payments only. Payment methods that don't support it will be disabled."
			)
		).toBeInTheDocument();

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Enable manual capture' } )
		);

		expect( setManualCapture ).toHaveBeenCalledWith( true );
	} );

	it( 'disables manual capture without confirmation', async () => {
		const setManualCapture = jest.fn();
		mockUseManualCapture.mockReturnValue( [ true, setManualCapture ] );

		render( <WooPaymentsSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', {
				name: 'Issue an authorization on checkout and capture later',
			} )
		);

		expect(
			screen.queryByRole( 'dialog', { name: 'Enable manual capture' } )
		).not.toBeInTheDocument();
		expect( setManualCapture ).toHaveBeenCalledWith( false );
	} );

	it( 'renders the test-account switch-to-live notice and modal', async () => {
		mockApiFetch.mockImplementation( ( options ) => {
			const path = typeof options === 'string' ? options : options?.path;

			if ( path === '/wc-admin/settings/payments/woopayments/account' ) {
				return Promise.resolve( {
					account: {
						id: 'acct_test',
						mode: 'test',
						default_currency: 'usd',
						connected: true,
						working: true,
						can_process_payments: true,
						test_mode: true,
						test_drive: true,
						sandbox: false,
						live: false,
					},
					urls: {
						setup: 'admin.php?page=wc-settings&tab=checkout&path=/woopayments/onboarding',
					},
				} );
			}

			return Promise.resolve( {
				account: {
					account_link: 'https://connect.stripe.test/account',
					default_currency: 'usd',
					default_external_accounts: [],
				},
			} );
		} );

		render( <WooPaymentsSettingsPage /> );

		expect(
			await screen.findByText( 'You are using a test account.' )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Provide additional details about your business so you can begin accepting real payments.'
			)
		).toBeInTheDocument();

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Activate payments' } )
		);

		const dialog = screen.getByRole( 'dialog', {
			name: 'Activate payments on your store',
		} );
		expect(
			within( dialog ).getByText(
				"Before continuing, please make sure that you're aware of the following:"
			)
		).toBeInTheDocument();
		expect(
			within( dialog ).getByText(
				'Your test account will be deactivated, but your transactions can be found in your order history.'
			)
		).toBeInTheDocument();
		expect(
			within( dialog ).getByText(
				'To use WooPayments, you will need to verify your business details.'
			)
		).toBeInTheDocument();
		expect(
			within( dialog ).getByText(
				'In order to receive payouts, you will need to provide your bank details.'
			)
		).toBeInTheDocument();
		expect(
			within( dialog ).getByRole( 'link', {
				name: 'Activate payments',
			} )
		).toHaveAttribute(
			'href',
			expect.stringContaining( 'from=wcpay-setup-live-payments' )
		);
	} );

	it( 'opens the setup-live modal from the legacy activation event bridge', async () => {
		mockApiFetch.mockImplementation( ( options ) => {
			const path = typeof options === 'string' ? options : options?.path;

			if ( path === '/wc-admin/settings/payments/woopayments/account' ) {
				return Promise.resolve( {
					account: {
						id: 'acct_test',
						mode: 'test',
						default_currency: 'usd',
						connected: true,
						working: true,
						can_process_payments: true,
						test_mode: true,
						test_drive: true,
						sandbox: false,
						live: false,
					},
					urls: {
						setup: 'admin.php?page=wc-settings&tab=checkout&path=/woopayments/onboarding',
					},
				} );
			}

			return Promise.resolve( {
				account: {
					account_link: 'https://connect.stripe.test/account',
					default_currency: 'usd',
					default_external_accounts: [],
				},
			} );
		} );

		render( <WooPaymentsSettingsPage /> );

		await screen.findByText( 'You are using a test account.' );
		fireEvent( document, new CustomEvent( 'wcpay:activate_payments' ) );

		expect(
			await screen.findByRole( 'dialog', {
				name: 'Activate payments on your store',
			} )
		).toBeInTheDocument();
	} );

	it( 'renders the reference development-mode test-account warning copy', async () => {
		mockUseDevMode.mockReturnValue( true );
		mockApiFetch.mockImplementation( ( options ) => {
			const path = typeof options === 'string' ? options : options?.path;

			if ( path === '/wc-admin/settings/payments/woopayments/account' ) {
				return Promise.resolve( {
					account: {
						id: 'acct_test',
						mode: 'test',
						default_currency: 'usd',
						connected: true,
						working: true,
						can_process_payments: true,
						test_mode: true,
						test_drive: true,
						sandbox: false,
						live: false,
					},
					urls: {
						setup: 'admin.php?page=wc-settings&tab=checkout&path=/woopayments/onboarding',
					},
				} );
			}

			return Promise.resolve( {} );
		} );

		render( <WooPaymentsSettingsPage /> );

		const heading = await screen.findByText(
			'You are using a test account.'
		);
		expect( heading.closest( 'p' ) ).toHaveTextContent(
			'⚠️ Development mode is enabled for the store! There can be no live onboarding process while using development, testing, or staging WordPress environments!'
		);
		expect(
			screen.getByRole( 'link', {
				name: /WordPress environment/,
			} )
		).toHaveAttribute(
			'href',
			'https://make.wordpress.org/core/2020/08/27/wordpress-environment-types/'
		);
		expect(
			screen.queryByRole( 'button', { name: 'Activate payments' } )
		).not.toBeInTheDocument();
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

	it( 'renders payout schedule and bank account management parity copy', async () => {
		mockApiFetch.mockImplementation( ( options ) => {
			const path = typeof options === 'string' ? options : options?.path;

			if ( path === '/wc-admin/settings/payments/woopayments/account' ) {
				return Promise.resolve( {
					account: {
						id: 'acct_live',
						mode: 'live',
						default_currency: 'usd',
						connected: true,
						working: true,
						can_process_payments: true,
						test_mode: false,
						test_drive: false,
						sandbox: false,
						live: true,
					},
					urls: {
						setup: 'admin.php?page=wc-settings&tab=checkout&path=/woopayments/onboarding',
					},
				} );
			}

			if ( path === '/wc/v3/payments/deposits/overview-all' ) {
				return Promise.resolve( {
					account: {
						account_link: 'https://connect.stripe.test/account',
						default_currency: 'usd',
						default_external_accounts: [
							{ currency: 'usd', status: 'enabled' },
						],
					},
				} );
			}

			return Promise.resolve( {} );
		} );

		render( <WooPaymentsSettingsPage /> );

		const section = screen
			.getByRole( 'heading', { name: 'Payouts' } )
			.closest( '.woopayments-settings-section' ) as HTMLElement;

		expect(
			within( section ).getByRole( 'heading', {
				name: 'Payout schedule',
			} )
		).toBeInTheDocument();
		expect(
			within( section )
				.getByRole( 'heading', {
					name: 'Payout schedule',
				} )
				.closest( '.woopayments-settings-field-group' )
		).toHaveAttribute( 'id', 'payout-schedule' );
		expect(
			within( section ).getByRole( 'heading', {
				name: 'Payout bank account',
			} )
		).toBeInTheDocument();
		expect(
			await within( section ).findByText(
				'Manage and update your bank account information to receive payouts.'
			)
		).toBeInTheDocument();
		expect(
			await within( section ).findByRole( 'link', {
				name: /Manage in Stripe/,
			} )
		).toHaveAttribute( 'href', 'https://connect.stripe.test/account' );
	} );

	it( 'renders the failed payout bank-account notice when an external account errored', async () => {
		mockApiFetch.mockImplementation( ( options ) => {
			const path = typeof options === 'string' ? options : options?.path;

			if ( path === '/wc-admin/settings/payments/woopayments/account' ) {
				return Promise.resolve( {
					account: {
						id: 'acct_live',
						mode: 'live',
						default_currency: 'usd',
						connected: true,
						working: true,
						can_process_payments: true,
						test_mode: false,
						test_drive: false,
						sandbox: false,
						live: true,
					},
					urls: {
						setup: 'admin.php?page=wc-settings&tab=checkout&path=/woopayments/onboarding',
					},
				} );
			}

			if ( path === '/wc/v3/payments/deposits/overview-all' ) {
				return Promise.resolve( {
					account: {
						account_link: 'https://connect.stripe.test/account',
						default_currency: 'usd',
						default_external_accounts: [
							{ currency: 'usd', status: 'errored' },
						],
					},
				} );
			}

			return Promise.resolve( {} );
		} );

		render( <WooPaymentsSettingsPage /> );

		expect(
			await screen.findByText(
				'Payouts are currently paused because a recent payout failed.'
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', {
				name: 'update your bank account details',
			} )
		).toHaveAttribute(
			'href',
			expect.stringContaining( 'source=wcpay-payout-failure-notice' )
		);
		expect( mockSpeak ).toHaveBeenCalledWith(
			'Payouts are currently paused because a recent payout failed.',
			'assertive'
		);
	} );

	it( 'matches the reference settings behavior for multicurrency failed payout accounts', async () => {
		mockApiFetch.mockImplementation( ( options ) => {
			const path = typeof options === 'string' ? options : options?.path;

			if ( path === '/wc-admin/settings/payments/woopayments/account' ) {
				return Promise.resolve( {
					account: {
						id: 'acct_live',
						mode: 'live',
						default_currency: 'usd',
						connected: true,
						working: true,
						can_process_payments: true,
						test_mode: false,
						test_drive: false,
						sandbox: false,
						live: true,
					},
					urls: {
						setup: 'admin.php?page=wc-settings&tab=checkout&path=/woopayments/onboarding',
					},
				} );
			}

			if ( path === '/wc/v3/payments/deposits/overview-all' ) {
				return Promise.resolve( {
					account: {
						account_link: 'https://connect.stripe.test/account',
						default_currency: 'usd',
						default_external_accounts: [
							{ currency: 'usd', status: 'enabled' },
							{ currency: 'eur', status: 'errored' },
						],
					},
				} );
			}

			return Promise.resolve( {} );
		} );

		render( <WooPaymentsSettingsPage /> );

		expect(
			await screen.findByText(
				'Payouts are currently paused because a recent payout failed.'
			)
		).toBeInTheDocument();
		expect( mockSpeak ).toHaveBeenCalledWith(
			'Payouts are currently paused because a recent payout failed.',
			'assertive'
		);
	} );

	it( 'uses the reference payout waiting-period copy', () => {
		mockUseCompletedWaitingPeriod.mockReturnValue( false );

		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getByText(
				'Payout scheduling becomes available after the standard 7-day waiting period for new accounts is complete.'
			)
		).toBeInTheDocument();
	} );

	it.each( [
		[ 'daily', 'Payouts will occur every business day.' ],
		[
			'weekly',
			'Payouts that fall on a holiday will initiate on the next business day.',
		],
		[
			'monthly',
			'Payouts scheduled on a weekend will be sent on the next business day.',
		],
	] )(
		'uses reference payout schedule helper copy for %s payouts',
		( interval, helperCopy ) => {
			mockUseDepositScheduleInterval.mockReturnValue( [
				interval as string,
				noop,
			] );

			render( <WooPaymentsSettingsPage /> );

			expect( screen.getByText( helperCopy ) ).toBeInTheDocument();
			expect(
				screen.queryByText( /Payout currency:/ )
			).not.toBeInTheDocument();
		}
	);

	it( 'uses reference monthly payout date labels', () => {
		mockUseDepositScheduleInterval.mockReturnValue( [ 'monthly', noop ] );

		render( <WooPaymentsSettingsPage /> );

		const dateSelect = screen.getByRole( 'combobox', { name: 'Date' } );
		expect(
			within( dateSelect ).getByRole( 'option', { name: '1st' } )
		).toBeInTheDocument();
		expect(
			within( dateSelect ).getByRole( 'option', { name: '2nd' } )
		).toBeInTheDocument();
		expect(
			within( dateSelect ).getByRole( 'option', { name: '3rd' } )
		).toBeInTheDocument();
	} );

	it( 'renders notification email warning and confirmation when the email changes', async () => {
		mockUseAccountCommunicationsEmail.mockImplementation( () =>
			useState( 'owner@example.com' )
		);

		render( <WooPaymentsSettingsPage /> );

		const section = screen
			.getByRole( 'heading', { name: 'Account notifications' } )
			.closest( '.woopayments-settings-section' ) as HTMLElement;

		expect(
			within( section ).getByRole( 'heading', {
				name: 'Notifications email',
			} )
		).toBeInTheDocument();
		expect(
			within( section ).getByText(
				'Provide an email address where you would like to receive communications about your WooPayments account.'
			)
		).toBeInTheDocument();
		expect(
			within( section ).getByText(
				'Anyone with access to this email address will be treated as the account owner. Please verify the address carefully.'
			)
		).toBeInTheDocument();

		const emailInput = within( section ).getByRole( 'textbox', {
			name: 'Email address',
		} );
		await userEvent.clear( emailInput );
		await userEvent.type( emailInput, 'new-owner@example.com' );

		const confirmInput = within( section ).getByRole( 'textbox', {
			name: 'Confirm email address',
		} );
		await userEvent.type( confirmInput, 'someone-else@example.com' );
		fireEvent.blur( confirmInput );

		expect(
			within( section ).getByText(
				'Email addresses do not match. Please re-enter your email address.'
			)
		).toBeInTheDocument();
		expect( confirmInput ).toHaveAttribute( 'aria-invalid', 'true' );
		expect(
			screen.getByRole( 'button', { name: 'Save changes' } )
		).toHaveAttribute( 'aria-disabled', 'true' );

		await userEvent.clear( confirmInput );
		await userEvent.type( confirmInput, 'new-owner@example.com' );

		await waitFor( () =>
			expect(
				screen.getByRole( 'button', { name: 'Save changes' } )
			).toBeEnabled()
		);
	} );

	it( 'validates notification email format after the field is blurred', async () => {
		mockUseAccountCommunicationsEmail.mockImplementation( () =>
			useState( 'owner@example.com' )
		);

		render( <WooPaymentsSettingsPage /> );

		const section = screen
			.getByRole( 'heading', { name: 'Account notifications' } )
			.closest( '.woopayments-settings-section' ) as HTMLElement;
		const emailInput = within( section ).getByRole( 'textbox', {
			name: 'Email address',
		} );

		await userEvent.clear( emailInput );
		await userEvent.type( emailInput, 'mailto:owner@example.com' );
		fireEvent.blur( emailInput );

		expect(
			within( section ).getByText( 'Please enter a valid email address.' )
		).toBeInTheDocument();
		expect( emailInput ).toHaveAttribute( 'aria-invalid', 'true' );
		expect( emailInput ).toHaveAttribute(
			'aria-describedby',
			'woopayments-notifications-email-error'
		);
		expect(
			screen.getByRole( 'button', { name: 'Save changes' } )
		).toHaveAttribute( 'aria-disabled', 'true' );
	} );

	it( 'renders transaction helper copy and validates support contact inputs', async () => {
		mockUseAccountBusinessSupportEmail.mockImplementation( () =>
			useState( 'support@example.com' )
		);
		mockUseAccountBusinessSupportPhone.mockImplementation( () =>
			useState( '+15555555555' )
		);

		render( <WooPaymentsSettingsPage /> );

		const section = screen
			.getByRole( 'heading', { name: 'Transactions' } )
			.closest( '.woopayments-settings-section' ) as HTMLElement;

		expect(
			within( section ).getByText(
				'When enabled, users will be able to pay with a saved card during checkout. Card details are stored in our platform, not on your store.'
			)
		).toBeInTheDocument();
		expect(
			within( section ).getByText(
				"Edit the way your store name appears on your customers' bank statements."
			)
		).toBeInTheDocument();
		expect(
			within( section ).getByText(
				'Provide contact information where customers can reach you for support.'
			)
		).toBeInTheDocument();

		const supportEmail = within( section ).getByRole( 'textbox', {
			name: 'Support email',
		} );
		await userEvent.clear( supportEmail );
		await userEvent.type( supportEmail, 'not-email' );
		fireEvent.blur( supportEmail );

		expect(
			within( section ).getByText( 'Please enter a valid email address.' )
		).toBeInTheDocument();
		expect( supportEmail ).toHaveAttribute( 'aria-invalid', 'true' );
		expect(
			screen.getByRole( 'button', { name: 'Save changes' } )
		).toHaveAttribute( 'aria-disabled', 'true' );

		await userEvent.clear( supportEmail );
		await userEvent.type( supportEmail, 'support@example.com' );

		const supportPhone = within( section ).getByRole( 'textbox', {
			name: 'Support phone number',
		} );
		await userEvent.clear( supportPhone );
		await userEvent.type( supportPhone, '12345' );
		fireEvent.blur( supportPhone );

		expect(
			within( section ).getByText( 'Please enter a valid phone number.' )
		).toBeInTheDocument();
		expect( supportPhone ).toHaveAttribute( 'aria-invalid', 'true' );
		expect(
			screen.getByRole( 'button', { name: 'Save changes' } )
		).toHaveAttribute( 'aria-disabled', 'true' );
	} );

	it( 'focuses the first field with server validation details after a failed save', async () => {
		const scrollIntoView = jest.fn();
		const originalScrollIntoView = Element.prototype.scrollIntoView;
		const originalMatchMedia = window.matchMedia;
		let savingError: unknown = null;

		Element.prototype.scrollIntoView = scrollIntoView;
		window.matchMedia = jest.fn().mockReturnValue( { matches: true } );
		mockSaveSettings.mockImplementation( async () => {
			savingError = {
				data: {
					details: {
						unknown_server_field: {
							message: 'This field is not rendered.',
						},
						account_business_support_email: {
							message:
								'Please enter a valid support email address.',
						},
						account_business_support_phone: {
							message:
								'Please enter a valid support phone number.',
						},
					},
				},
			};

			return false;
		} );
		mockUseGetSavingError.mockImplementation( () => savingError );

		try {
			render( <WooPaymentsSettingsPage /> );

			await act( async () => {
				await userEvent.click(
					screen.getByRole( 'button', { name: 'Save changes' } )
				);
			} );

			const supportEmail = screen.getByRole( 'textbox', {
				name: 'Support email',
			} );

			await waitFor( () => expect( supportEmail ).toHaveFocus() );
			expect( scrollIntoView ).toHaveBeenCalledWith( {
				behavior: 'auto',
				block: 'center',
			} );
		} finally {
			Element.prototype.scrollIntoView = originalScrollIntoView;
			window.matchMedia = originalMatchMedia;
		}
	} );

	it( 'focuses the notifications email field for server validation details', async () => {
		const scrollIntoView = jest.fn();
		const originalScrollIntoView = Element.prototype.scrollIntoView;
		const originalMatchMedia = window.matchMedia;
		let savingError: unknown = null;

		Element.prototype.scrollIntoView = scrollIntoView;
		window.matchMedia = jest.fn().mockReturnValue( { matches: true } );
		mockSaveSettings.mockImplementation( async () => {
			savingError = {
				data: {
					details: {
						account_communications_email: {
							message:
								'Please enter a valid notifications email address.',
						},
					},
				},
			};

			return false;
		} );
		mockUseGetSavingError.mockImplementation( () => savingError );

		try {
			render( <WooPaymentsSettingsPage /> );

			await act( async () => {
				await userEvent.click(
					screen.getByRole( 'button', { name: 'Save changes' } )
				);
			} );

			const notificationsEmail = screen.getByRole( 'textbox', {
				name: 'Email address',
			} );

			await waitFor( () => expect( notificationsEmail ).toHaveFocus() );
			expect( scrollIntoView ).toHaveBeenCalledWith( {
				behavior: 'auto',
				block: 'center',
			} );
		} finally {
			Element.prototype.scrollIntoView = originalScrollIntoView;
			window.matchMedia = originalMatchMedia;
		}
	} );

	it( 'requires a support phone number before settings can be saved', () => {
		mockUseAccountBusinessSupportPhone.mockImplementation( () =>
			useState( '' )
		);

		render( <WooPaymentsSettingsPage /> );

		const section = screen
			.getByRole( 'heading', { name: 'Transactions' } )
			.closest( '.woopayments-settings-section' ) as HTMLElement;

		expect(
			within( section ).getByText(
				'Support phone number cannot be empty.'
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Save changes' } )
		).toHaveAttribute( 'aria-disabled', 'true' );
	} );

	it( 'announces the page-level save busy state accessibly', () => {
		mockUseSettings.mockReturnValue( {
			isLoading: false,
			isSaving: true,
			isDirty: true,
			saveSettings: mockSaveSettings,
		} );

		render( <WooPaymentsSettingsPage /> );

		const status = document.querySelector(
			'.woopayments-settings-busy-state__status'
		) as HTMLElement;
		expect( status ).toHaveTextContent( 'Saving…' );
		expect( status.parentElement ).not.toHaveAttribute( 'aria-busy' );
		expect(
			status.parentElement?.querySelector(
				'.woopayments-settings-busy-state__content'
			)
		).toHaveAttribute( 'aria-busy', 'true' );
	} );

	it( 'opens WooPay feedback after a successful save disables WooPay', async () => {
		let isWooPayEnabled = true;
		const setWooPayEnabled = jest.fn( ( value: boolean ) => {
			isWooPayEnabled = value;
		} );
		mockUseGetSettings.mockImplementation( () => ( {
			account_country: 'US',
			is_woopay_enabled: isWooPayEnabled,
			woopay_last_disable_date: '',
			available_payment_method_ids: [
				'card',
				'link',
				'affirm',
				'amazon_pay',
				'apple_pay',
				'google_pay',
			],
		} ) );
		mockUseWooPayEnabledSettings.mockImplementation( () => [
			isWooPayEnabled,
			setWooPayEnabled,
		] );
		mockSaveSettings.mockResolvedValue( true );

		const { rerender } = render( <WooPaymentsSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', { name: 'WooPay' } )
		);
		rerender( <WooPaymentsSettingsPage /> );
		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Save changes' } )
			);
		} );

		const dialog = await screen.findByRole( 'dialog', {
			name: 'WooPay feedback',
		} );
		expect(
			within( dialog ).getByTitle( 'WooPay disable feedback' )
		).toHaveAttribute(
			'src',
			'https://woocommerce.survey.fm/woopay-disabled-merchants-feedback-triggered'
		);
	} );

	it( 'does not open WooPay feedback after a failed disable save', async () => {
		let isWooPayEnabled = true;
		const setWooPayEnabled = jest.fn( ( value: boolean ) => {
			isWooPayEnabled = value;
		} );
		mockUseGetSettings.mockImplementation( () => ( {
			account_country: 'US',
			is_woopay_enabled: isWooPayEnabled,
			woopay_last_disable_date: '',
			available_payment_method_ids: [
				'card',
				'link',
				'affirm',
				'amazon_pay',
				'apple_pay',
				'google_pay',
			],
		} ) );
		mockUseWooPayEnabledSettings.mockImplementation( () => [
			isWooPayEnabled,
			setWooPayEnabled,
		] );
		mockSaveSettings.mockResolvedValue( false );

		const { rerender } = render( <WooPaymentsSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', { name: 'WooPay' } )
		);
		rerender( <WooPaymentsSettingsPage /> );
		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Save changes' } )
			);
		} );

		await waitFor( () => expect( mockSaveSettings ).toHaveBeenCalled() );
		expect(
			screen.queryByRole( 'dialog', { name: 'WooPay feedback' } )
		).not.toBeInTheDocument();
	} );

	it( 'does not open WooPay feedback when the last disable date is recent', async () => {
		let isWooPayEnabled = true;
		const setWooPayEnabled = jest.fn( ( value: boolean ) => {
			isWooPayEnabled = value;
		} );
		mockUseGetSettings.mockImplementation( () => ( {
			account_country: 'US',
			is_woopay_enabled: isWooPayEnabled,
			woopay_last_disable_date: new Date().toISOString().slice( 0, 10 ),
			available_payment_method_ids: [
				'card',
				'link',
				'affirm',
				'amazon_pay',
				'apple_pay',
				'google_pay',
			],
		} ) );
		mockUseWooPayEnabledSettings.mockImplementation( () => [
			isWooPayEnabled,
			setWooPayEnabled,
		] );
		mockSaveSettings.mockResolvedValue( true );

		const { rerender } = render( <WooPaymentsSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', { name: 'WooPay' } )
		);
		rerender( <WooPaymentsSettingsPage /> );
		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Save changes' } )
			);
		} );

		await waitFor( () => expect( mockSaveSettings ).toHaveBeenCalled() );
		expect(
			screen.queryByRole( 'dialog', { name: 'WooPay feedback' } )
		).not.toBeInTheDocument();
	} );

	it( 'renders fraud protection with the reference Basic and Advanced level controls', () => {
		mockUseCurrentProtectionLevel.mockReturnValue( [ 'basic', noop ] );
		mockUseAdvancedFraudProtectionSettings.mockReturnValue( [ [], noop ] );

		render( <WooPaymentsSettingsPage /> );

		const section = screen
			.getByRole( 'heading', { name: 'Fraud protection' } )
			.closest( '.woopayments-settings-section' ) as HTMLElement;

		expect(
			within( section ).getByText(
				'Help avoid unauthorized transactions and disputes by setting your fraud protection level.'
			)
		).toBeInTheDocument();
		expect(
			within( section ).getByRole( 'link', {
				name: /Learn more about fraud protection/,
			} )
		).toHaveAttribute(
			'href',
			'https://woocommerce.com/document/woopayments/fraud-and-disputes/fraud-protection/'
		);
		expect(
			within( section ).getByRole( 'heading', {
				name: 'Set your payment risk level',
			} )
		).toBeInTheDocument();
		expect(
			within( section ).getByRole( 'group', {
				name: 'Fraud protection level',
			} )
		).toHaveClass( 'woopayments-fraud-protection-levels' );
		expect(
			within( section ).getByRole( 'radio', { name: 'Basic' } )
		).toBeChecked();
		expect(
			within( section ).getByRole( 'radio', { name: 'Advanced' } )
		).toBeInTheDocument();
		expect(
			within( section ).queryByRole( 'combobox', {
				name: 'Protection level',
			} )
		).not.toBeInTheDocument();
		expect(
			within( section ).queryByText( 'Standard' )
		).not.toBeInTheDocument();
		expect(
			within( section ).getByText(
				'Provides the base level of platform protection.'
			)
		).toBeInTheDocument();
		expect(
			within( section ).getByText(
				'Allows you to fine-tune the level of filtering according to your business needs.'
			)
		).toBeInTheDocument();
	} );

	it( 'links advanced fraud protection to the native provider settings route', () => {
		mockUseCurrentProtectionLevel.mockReturnValue( [ 'advanced', noop ] );
		mockUseAdvancedFraudProtectionSettings.mockReturnValue( [ [], noop ] );

		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getByRole( 'link', { name: 'Configure' } )
		).toHaveAttribute(
			'href',
			expect.stringContaining(
				'path=%2Fwoopayments%2Fsettings%2Ffraud-protection'
			)
		);
	} );

	it( 'does not link advanced fraud protection while fraud settings failed to load', () => {
		mockUseCurrentProtectionLevel.mockReturnValue( [ 'advanced', noop ] );
		mockUseAdvancedFraudProtectionSettings.mockReturnValue( [
			'error',
			noop,
		] );

		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.queryByRole( 'link', { name: 'Configure' } )
		).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Configure' } )
		).toBeDisabled();
	} );

	it( 'uses Edit copy for configured advanced fraud protection settings', () => {
		mockUseCurrentProtectionLevel.mockReturnValue( [ 'advanced', noop ] );
		mockUseAdvancedFraudProtectionSettings.mockReturnValue( [
			[ { key: 'avs_verification' } ],
			noop,
		] );

		render( <WooPaymentsSettingsPage /> );

		expect( screen.getByRole( 'link', { name: 'Edit' } ) ).toHaveAttribute(
			'href',
			expect.stringContaining(
				'path=%2Fwoopayments%2Fsettings%2Ffraud-protection'
			)
		);
	} );

	it( 'opens the Basic fraud protection help modal', async () => {
		mockUseCurrentProtectionLevel.mockReturnValue( [ 'basic', noop ] );

		render( <WooPaymentsSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Basic level help icon' } )
		);

		expect(
			screen.getByRole( 'heading', { name: 'Basic filter level' } )
		).toBeInTheDocument();
		const dialog = screen.getByRole( 'dialog', {
			name: 'Basic filter level',
		} );
		expect(
			within( dialog ).getByText(
				'Provides basic anti-fraud protection only.'
			)
		).toBeInTheDocument();
		expect(
			within( dialog ).getByText( 'Payments will be blocked if:' )
		).toBeInTheDocument();
		expect(
			within( dialog ).getByText(
				'The billing address does not match what is on file with the card issuer.'
			)
		).toBeInTheDocument();
		expect(
			within( dialog ).getByText(
				"The card's issuing bank cannot verify the CVV."
			)
		).toBeInTheDocument();
		expect(
			within( dialog ).getByRole( 'button', { name: 'Got it' } )
		).toBeInTheDocument();
	} );

	it( 'honors explicitly disabled Basic fraud checks from the native settings contract', async () => {
		mockUseCurrentProtectionLevel.mockReturnValue( [ 'basic', noop ] );
		mockUseGetSettings.mockReturnValue( {
			fraud_protection: {
				decline_on_avs_failure: false,
				decline_on_cvc_failure: false,
			},
			account_status: {
				fraudProtection: {
					declineOnAVSFailure: true,
					declineOnCVCFailure: true,
				},
			},
		} );

		render( <WooPaymentsSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Basic level help icon' } )
		);

		const dialog = screen.getByRole( 'dialog', {
			name: 'Basic filter level',
		} );
		expect(
			within( dialog ).queryByText( 'Payments will be blocked if:' )
		).not.toBeInTheDocument();
		expect(
			within( dialog ).queryByText(
				'The billing address does not match what is on file with the card issuer.'
			)
		).not.toBeInTheDocument();
		expect(
			within( dialog ).queryByText(
				"The card's issuing bank cannot verify the CVV."
			)
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

	it( 'renders reference advanced settings copy and development-mode debug behavior', () => {
		mockUseDevMode.mockReturnValue( true );
		mockUseWCPaySubscriptions.mockReturnValue( [ true, true, noop ] );

		render( <WooPaymentsSettingsPage /> );

		const section = screen
			.getByRole( 'heading', { name: 'Advanced settings' } )
			.closest( '.woopayments-settings-section' ) as HTMLElement;

		expect(
			within( section ).getByText(
				/Allow customers to shop and pay in multiple currencies./
			)
		).toBeInTheDocument();
		expect(
			within( section ).getByRole( 'link', { name: /Learn more/ } )
		).toHaveAttribute(
			'href',
			'https://woocommerce.com/document/woopayments/currencies/multi-currency-setup/'
		);
		expect(
			within( section ).getByText(
				/This feature is deprecated. Existing subscription renewals will continue to work, but creating or managing subscriptions is no longer available./
			)
		).toBeInTheDocument();

		const debugLog = within( section ).getByRole( 'checkbox', {
			name: 'Log error messages (defaulted on for test accounts)',
		} );
		expect( debugLog ).toBeChecked();
		expect( debugLog ).toBeDisabled();
		expect(
			within( section ).getByText(
				'When enabled, payment error logs will be saved to WooCommerce > Status > Logs.'
			)
		).toBeInTheDocument();
	} );

	it( 'prevents enabling deprecated bundled subscriptions from the native settings page', () => {
		const setSubscriptionsEnabled = jest.fn();
		mockUseWCPaySubscriptions.mockReturnValue( [
			false,
			true,
			setSubscriptionsEnabled,
		] );

		render( <WooPaymentsSettingsPage /> );

		const subscriptionsToggle = screen.getByRole( 'checkbox', {
			name: 'Enable Subscriptions with WooPayments',
		} );

		expect( subscriptionsToggle ).toBeDisabled();
		fireEvent.click( subscriptionsToggle );

		expect( setSubscriptionsEnabled ).not.toHaveBeenCalled();
	} );
} );
