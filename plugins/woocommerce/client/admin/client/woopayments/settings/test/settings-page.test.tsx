/**
 * External dependencies
 */
import { speak } from '@wordpress/a11y';
import apiFetch from '@wordpress/api-fetch';
import fs from 'fs';
import nodePath from 'path';
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
import { recordEvent } from '@woocommerce/tracks';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { WooPaymentsSettingsPage } from '../settings-page';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
jest.mock( '@wordpress/a11y', () => ( {
	speak: jest.fn(),
} ) );
jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

type TourStep = {
	focusElement?: {
		desktop?: string;
	};
	meta?: {
		heading?: string;
		descriptions?: {
			desktop?: ReactNode;
		};
	};
};

type TourConfig = {
	steps: TourStep[];
	options?: {
		callbacks?: {
			onMinimize?: ( currentStepIndex: number ) => void;
		};
		effects?: {
			autoScroll?:
				| boolean
				| {
						behavior?: ScrollBehavior;
						block?: ScrollLogicalPosition;
				  };
		};
	};
	closeHandler: (
		steps: TourStep[],
		currentIndex: number,
		element: string
	) => void;
};

const mockTourKitConfigs: TourConfig[] = [];

jest.mock( '@woocommerce/components', () => {
	const actualComponents = jest.requireActual( '@woocommerce/components' );

	return {
		...actualComponents,
		TourKit: ( { config }: { config: TourConfig } ) => {
			mockTourKitConfigs.push( config );

			return (
				<div data-testid="fraud-protection-tour">
					{ config.steps.map( ( step ) => (
						<div key={ step.meta?.heading }>
							{ step.meta?.heading }
							{ step.meta?.descriptions?.desktop && (
								<div>{ step.meta.descriptions.desktop }</div>
							) }
						</div>
					) ) }
					<button
						type="button"
						onClick={ () =>
							config.closeHandler(
								config.steps,
								config.steps.length - 1,
								'done-btn'
							)
						}
					>
						Finish fraud tour
					</button>
					<button
						type="button"
						onClick={ () =>
							config.closeHandler( config.steps, 0, 'close-btn' )
						}
					>
						Dismiss fraud tour
					</button>
				</div>
			);
		},
	};
} );

const mockCreateErrorNotice = jest.fn();
const mockCreateInfoNotice = jest.fn();

jest.mock( '@wordpress/data', () => {
	const actualData = jest.requireActual( '@wordpress/data' );

	return {
		...actualData,
		dispatch: jest.fn( ( storeName, ...args ) =>
			storeName === 'core/notices'
				? {
						createErrorNotice: mockCreateErrorNotice,
						createInfoNotice: mockCreateInfoNotice,
				  }
				: actualData.dispatch( storeName, ...args )
		),
	};
} );

jest.mock( '../../promotions/spotlight', () => ( {
	SpotlightPromotion: () => <div>Spotlight promotion</div>,
} ) );

const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;
const mockSpeak = speak as jest.MockedFunction< typeof speak >;
const mockRecordEvent = recordEvent as jest.MockedFunction<
	typeof recordEvent
>;
const FRAUD_TOUR_DISMISSAL_PATH =
	'/wc/v3/payments/settings/wcpay_fraud_protection_welcome_tour_dismissed';

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
	useLinkEnabledSettings: ( isWooPayBlockingLink?: boolean ) =>
		mockUseLinkEnabledSettings( isWooPayBlockingLink ),
	useWooPayShowIncompatibilityNotice: () =>
		mockUseWooPayShowIncompatibilityNotice(),
	useGetSavingError: () => mockUseGetSavingError(),
} ) );

const noop = jest.fn();
const DEFAULT_FEATURE_FLAGS = {
	woopay: true,
	woopayExpressCheckout: true,
	isDynamicCheckoutPlaceOrderButtonEnabled: true,
	amazonPay: true,
};

const getDefaultAccountResponse = ( {
	documentsEnabled = true,
	hasSubmittedVatData = true,
	country = 'US',
}: {
	documentsEnabled?: boolean;
	hasSubmittedVatData?: boolean;
	country?: string;
} = {} ) => ( {
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
	documents: {
		enabled: documentsEnabled,
		has_submitted_vat_data: hasSubmittedVatData,
		country,
	},
	urls: {
		setup: 'admin.php?page=wc-settings&tab=checkout&path=/woopayments/onboarding',
	},
} );

const setSettingsPageUrl = ( query = '' ) => {
	const suffix = query ? `&${ query }` : '';

	window.history.replaceState(
		null,
		'',
		`/wp-admin/admin.php?page=wc-settings&tab=checkout&path=/woopayments/settings${ suffix }`
	);
};

type MockIntersectionObserverInstance = IntersectionObserver & {
	observe: jest.Mock;
	unobserve: jest.Mock;
	disconnect: jest.Mock;
};

let mockIntersectionObserverCallbacks: IntersectionObserverCallback[] = [];
let mockIntersectionObserverInstances: MockIntersectionObserverInstance[] = [];

const installMockIntersectionObserver = () => {
	mockIntersectionObserverCallbacks = [];
	mockIntersectionObserverInstances = [];

	window.IntersectionObserver = jest.fn(
		( callback: IntersectionObserverCallback ) => {
			const instance = {
				root: null,
				rootMargin: '',
				thresholds: [ 1 ],
				observe: jest.fn(),
				unobserve: jest.fn(),
				disconnect: jest.fn(),
				takeRecords: jest.fn( () => [] ),
			} as MockIntersectionObserverInstance;

			mockIntersectionObserverCallbacks.push( callback );
			mockIntersectionObserverInstances.push( instance );

			return instance;
		}
	);
};

const intersectObservedElement = ( target: Element ) => {
	act( () => {
		mockIntersectionObserverCallbacks[ 0 ](
			[
				{
					isIntersecting: true,
					target,
				} as IntersectionObserverEntry,
			],
			mockIntersectionObserverInstances[ 0 ]
		);
	} );
};

const getFraudTourDismissalCalls = () =>
	mockApiFetch.mock.calls.filter( ( [ options ] ) => {
		const path = typeof options === 'string' ? options : options?.path;

		return path === FRAUD_TOUR_DISMISSAL_PATH;
	} );

const getFraudTourEventNames = () =>
	mockRecordEvent.mock.calls
		.map( ( [ eventName ] ) => eventName )
		.filter(
			( eventName ): eventName is string =>
				typeof eventName === 'string' &&
				eventName.startsWith( 'wcpay_fraud_protection_tour_' )
		);

const getRecordedEventCalls = ( expectedEventName: string ) =>
	mockRecordEvent.mock.calls.filter(
		( [ eventName ] ) => eventName === expectedEventName
	);

const getGatewayToggleEventCalls = ( expectedAction: string ) =>
	getRecordedEventCalls( 'wcpay_gateway_toggle' ).filter(
		( [ , properties ] ) =>
			properties &&
			typeof properties === 'object' &&
			'action' in properties &&
			properties.action === expectedAction
	);

const getGeneralSettingsSection = () =>
	screen
		.getByRole( 'heading', { name: 'General' } )
		.closest( '.woopayments-settings-section' ) as HTMLElement;

const setHookDefaults = () => {
	mockUseSettings.mockReturnValue( {
		isLoading: false,
		isSaving: false,
		isDirty: true,
		saveSettings: mockSaveSettings,
	} );
	mockUseGetSettings.mockReturnValue( {
		account_country: 'US',
		store_currency: 'USD',
		is_multi_currency_enabled: true,
		feature_flags: DEFAULT_FEATURE_FLAGS,
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
		mockTourKitConfigs.length = 0;
		delete (
			window as typeof window & {
				IntersectionObserver?: typeof IntersectionObserver;
			}
		 ).IntersectionObserver;
		setSettingsPageUrl();
		mockApiFetch.mockImplementation( ( options ) => {
			const path = typeof options === 'string' ? options : options?.path;

			if ( path === '/wc-admin/settings/payments/woopayments/account' ) {
				return Promise.resolve( getDefaultAccountResponse() );
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

	it( 'keeps the tax details modal in a dedicated optional settings chunk with its styles', () => {
		const settingsPageSource = fs.readFileSync(
			nodePath.resolve( __dirname, '../settings-page.tsx' ),
			'utf8'
		);
		const vatModalSource = fs.readFileSync(
			nodePath.resolve(
				__dirname,
				'../../admin/documents/vat-modal.tsx'
			),
			'utf8'
		);

		expect( settingsPageSource ).toContain(
			'webpackChunkName: "settings-payments-woopayments-vat-modal"'
		);
		expect( settingsPageSource ).not.toContain(
			"import { WooPaymentsVatModal } from '../admin/documents/vat-modal'"
		);
		expect( vatModalSource ).toContain( "import './vat-modal.scss'" );
	} );

	it( 'opens the tax details modal from the VAT settings deep link when tax details are missing', async () => {
		setSettingsPageUrl( 'woopayments-vat-details-modal=true' );
		mockApiFetch.mockImplementation( ( options ) => {
			const path = typeof options === 'string' ? options : options?.path;

			if ( path === '/wc-admin/settings/payments/woopayments/account' ) {
				return Promise.resolve(
					getDefaultAccountResponse( {
						documentsEnabled: true,
						hasSubmittedVatData: false,
						country: 'DE',
					} )
				);
			}

			return Promise.resolve( {} );
		} );

		render( <WooPaymentsSettingsPage /> );

		const dialog = await screen.findByRole( 'dialog', {
			name: 'Set your tax details',
		} );

		expect(
			within( dialog ).getByRole( 'checkbox', {
				name: 'I have a valid VAT Number',
			} )
		).toBeInTheDocument();
		expect( mockCreateErrorNotice ).not.toHaveBeenCalled();
		expect( mockCreateInfoNotice ).not.toHaveBeenCalled();
	} );

	it( 'shows the unavailable tax details notice from the VAT settings deep link when documents are disabled', async () => {
		setSettingsPageUrl( 'woopayments-vat-details-modal=true' );
		mockApiFetch.mockImplementation( ( options ) => {
			const path = typeof options === 'string' ? options : options?.path;

			if ( path === '/wc-admin/settings/payments/woopayments/account' ) {
				return Promise.resolve(
					getDefaultAccountResponse( {
						documentsEnabled: false,
						hasSubmittedVatData: false,
					} )
				);
			}

			return Promise.resolve( {} );
		} );

		render( <WooPaymentsSettingsPage /> );

		await waitFor( () =>
			expect( mockCreateErrorNotice ).toHaveBeenCalledWith(
				'Tax details collection is not available for your account.'
			)
		);
		expect(
			screen.queryByRole( 'dialog', { name: 'Set your tax details' } )
		).not.toBeInTheDocument();
	} );

	it( 'shows the unavailable tax details notice from the VAT settings deep link when account data fails to load', async () => {
		setSettingsPageUrl( 'woopayments-vat-details-modal=true' );
		mockApiFetch.mockImplementation( ( options ) => {
			const path = typeof options === 'string' ? options : options?.path;

			if ( path === '/wc-admin/settings/payments/woopayments/account' ) {
				return Promise.reject( new Error( 'Account unavailable' ) );
			}

			return Promise.resolve( {} );
		} );

		render( <WooPaymentsSettingsPage /> );

		await waitFor( () =>
			expect( mockCreateErrorNotice ).toHaveBeenCalledWith(
				'Tax details collection is not available for your account.'
			)
		);
		expect(
			screen.queryByRole( 'dialog', { name: 'Set your tax details' } )
		).not.toBeInTheDocument();
	} );

	it( 'shows the already-submitted tax details notice from the VAT settings deep link', async () => {
		setSettingsPageUrl( 'woopayments-vat-details-modal=true' );
		mockApiFetch.mockImplementation( ( options ) => {
			const path = typeof options === 'string' ? options : options?.path;

			if ( path === '/wc-admin/settings/payments/woopayments/account' ) {
				return Promise.resolve(
					getDefaultAccountResponse( {
						documentsEnabled: true,
						hasSubmittedVatData: true,
					} )
				);
			}

			return Promise.resolve( {} );
		} );

		render( <WooPaymentsSettingsPage /> );

		await waitFor( () =>
			expect( mockCreateInfoNotice ).toHaveBeenCalledWith(
				'Tax details are already submitted.'
			)
		);
		expect(
			screen.queryByRole( 'dialog', { name: 'Set your tax details' } )
		).not.toBeInTheDocument();
	} );

	it( 'removes only the VAT modal query arg when the tax details modal closes', async () => {
		setSettingsPageUrl(
			'woopayments-vat-details-modal=true&source=platform-email'
		);
		mockApiFetch.mockImplementation( ( options ) => {
			const path = typeof options === 'string' ? options : options?.path;

			if ( path === '/wc-admin/settings/payments/woopayments/account' ) {
				return Promise.resolve(
					getDefaultAccountResponse( {
						documentsEnabled: true,
						hasSubmittedVatData: false,
					} )
				);
			}

			return Promise.resolve( {} );
		} );

		render( <WooPaymentsSettingsPage /> );

		await screen.findByRole( 'dialog', { name: 'Set your tax details' } );
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Cancel' } )
		);
		const query = new URLSearchParams( window.location.search );

		expect( query.get( 'path' ) ).toBe( '/woopayments/settings' );
		expect( query.get( 'source' ) ).toBe( 'platform-email' );
		expect( query.has( 'woopayments-vat-details-modal' ) ).toBe( false );
	} );

	it( 'saves tax details from the deep-linked modal and clears the URL state', async () => {
		setSettingsPageUrl( 'woopayments-vat-details-modal=true' );
		mockApiFetch.mockImplementation( ( options ) => {
			const path = typeof options === 'string' ? options : options?.path;

			if ( path === '/wc-admin/settings/payments/woopayments/account' ) {
				return Promise.resolve(
					getDefaultAccountResponse( {
						documentsEnabled: true,
						hasSubmittedVatData: false,
					} )
				);
			}

			if ( path === '/wc/v3/payments/vat' ) {
				return Promise.resolve( {
					vat_number: null,
					name: 'Example GmbH',
					address: 'Alexanderplatz 1, Berlin',
				} );
			}

			return Promise.resolve( {} );
		} );

		render( <WooPaymentsSettingsPage /> );

		await screen.findByRole( 'dialog', { name: 'Set your tax details' } );
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Continue' } )
		);
		await userEvent.type(
			await screen.findByRole( 'textbox', { name: 'Business name' } ),
			'Example GmbH'
		);
		await userEvent.type(
			screen.getByRole( 'textbox', { name: 'Address' } ),
			'Alexanderplatz 1, Berlin'
		);
		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Confirm' } )
			);
		} );

		await waitFor( () =>
			expect( mockCreateInfoNotice ).toHaveBeenCalledWith(
				'Tax details updated'
			)
		);
		const getVatRequest = () =>
			mockApiFetch.mock.calls
				.map( ( [ options ] ) => options )
				.find(
					( options ) =>
						typeof options !== 'string' &&
						options?.path === '/wc/v3/payments/vat'
				);
		await waitFor( () =>
			expect( getVatRequest() ).toMatchObject( {
				method: 'POST',
				data: {
					vat_number: null,
					name: 'Example GmbH',
					address: 'Alexanderplatz 1, Berlin',
				},
			} )
		);
		await waitFor( () =>
			expect(
				screen.queryByRole( 'dialog', { name: 'Set your tax details' } )
			).not.toBeInTheDocument()
		);
		expect(
			new URLSearchParams( window.location.search ).has(
				'woopayments-vat-details-modal'
			)
		).toBe( false );
	} );

	it( 'does not open or announce VAT details without the VAT settings deep link', async () => {
		render( <WooPaymentsSettingsPage /> );

		await waitFor( () =>
			expect(
				mockApiFetch.mock.calls.some( ( [ options ] ) => {
					const path =
						typeof options === 'string' ? options : options?.path;

					return (
						path ===
						'/wc-admin/settings/payments/woopayments/account'
					);
				} )
			).toBe( true )
		);
		expect(
			screen.queryByRole( 'dialog', { name: 'Set your tax details' } )
		).not.toBeInTheDocument();
		expect( mockCreateErrorNotice ).not.toHaveBeenCalledWith(
			'Tax details collection is not available for your account.'
		);
		expect( mockCreateInfoNotice ).not.toHaveBeenCalledWith(
			'Tax details are already submitted.'
		);
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

	it( 'hides the WooPay express checkout row when the WooPay feature flag is disabled', async () => {
		const setIsLinkEnabled = jest.fn();

		mockUseGetSettings.mockReturnValue( {
			account_country: 'US',
			store_currency: 'USD',
			is_multi_currency_enabled: true,
			feature_flags: {
				...DEFAULT_FEATURE_FLAGS,
				woopay: false,
			},
			available_payment_method_ids: [
				'card',
				'link',
				'amazon_pay',
				'apple_pay',
				'google_pay',
			],
		} );
		mockUseLinkEnabledSettings.mockImplementation(
			( isWooPayBlockingLink?: boolean ) => {
				const shouldBlockLink = isWooPayBlockingLink ?? true;
				const setLinkEnabledIfNotBlocked = ( isEnabled: boolean ) => {
					if ( shouldBlockLink ) {
						return;
					}

					setIsLinkEnabled( isEnabled );
				};

				return [ false, setLinkEnabledIfNotBlocked, shouldBlockLink ];
			}
		);

		render( <WooPaymentsSettingsPage /> );

		const expressCheckoutsSection = screen
			.getByRole( 'heading', { name: 'Express checkouts' } )
			.closest( '.woopayments-settings-section' ) as HTMLElement;

		expect(
			within( expressCheckoutsSection ).queryByRole( 'checkbox', {
				name: 'WooPay',
			} )
		).not.toBeInTheDocument();
		expect(
			within( expressCheckoutsSection ).queryByRole( 'link', {
				name: 'Customize WooPay',
			} )
		).not.toBeInTheDocument();
		expect(
			within( expressCheckoutsSection ).getByRole( 'checkbox', {
				name: 'Amazon Pay',
			} )
		).toBeInTheDocument();
		expect(
			within( expressCheckoutsSection ).queryByText(
				'To enable Link by Stripe, you must first disable WooPay.'
			)
		).not.toBeInTheDocument();

		const linkCheckbox = within( expressCheckoutsSection ).getByRole(
			'checkbox',
			{
				name: 'Link by Stripe',
			}
		);

		expect( linkCheckbox ).toBeEnabled();
		await userEvent.click( linkCheckbox );
		expect( setIsLinkEnabled ).toHaveBeenCalledWith( true );
	} );

	it( 'hides the WooPay express checkout row when the WooPay Express Checkout feature flag is disabled', async () => {
		const setIsLinkEnabled = jest.fn();

		mockUseGetSettings.mockReturnValue( {
			account_country: 'US',
			store_currency: 'USD',
			is_multi_currency_enabled: true,
			feature_flags: {
				...DEFAULT_FEATURE_FLAGS,
				woopayExpressCheckout: false,
			},
			available_payment_method_ids: [
				'card',
				'link',
				'amazon_pay',
				'apple_pay',
				'google_pay',
			],
		} );
		mockUseLinkEnabledSettings.mockImplementation(
			( isWooPayBlockingLink?: boolean ) => {
				const shouldBlockLink = isWooPayBlockingLink ?? true;
				const setLinkEnabledIfNotBlocked = ( isEnabled: boolean ) => {
					if ( shouldBlockLink ) {
						return;
					}

					setIsLinkEnabled( isEnabled );
				};

				return [ false, setLinkEnabledIfNotBlocked, shouldBlockLink ];
			}
		);

		render( <WooPaymentsSettingsPage /> );

		const expressCheckoutsSection = screen
			.getByRole( 'heading', { name: 'Express checkouts' } )
			.closest( '.woopayments-settings-section' ) as HTMLElement;

		expect(
			within( expressCheckoutsSection ).queryByRole( 'checkbox', {
				name: 'WooPay',
			} )
		).not.toBeInTheDocument();
		expect(
			within( expressCheckoutsSection ).queryByRole( 'link', {
				name: 'Customize WooPay',
			} )
		).not.toBeInTheDocument();
		expect(
			within( expressCheckoutsSection ).getByRole( 'checkbox', {
				name: 'Amazon Pay',
			} )
		).toBeInTheDocument();

		const linkCheckbox = within( expressCheckoutsSection ).getByRole(
			'checkbox',
			{
				name: 'Link by Stripe',
			}
		);

		expect( linkCheckbox ).toBeEnabled();
		await userEvent.click( linkCheckbox );
		expect( setIsLinkEnabled ).toHaveBeenCalledWith( true );
	} );

	it( 'hides the Amazon Pay express checkout row when the Amazon Pay feature flag is disabled', () => {
		mockUseGetSettings.mockReturnValue( {
			account_country: 'US',
			store_currency: 'USD',
			is_multi_currency_enabled: true,
			feature_flags: {
				...DEFAULT_FEATURE_FLAGS,
				amazonPay: false,
			},
			available_payment_method_ids: [
				'card',
				'link',
				'amazon_pay',
				'apple_pay',
				'google_pay',
			],
		} );

		render( <WooPaymentsSettingsPage /> );

		const expressCheckoutsSection = screen
			.getByRole( 'heading', { name: 'Express checkouts' } )
			.closest( '.woopayments-settings-section' ) as HTMLElement;

		expect(
			within( expressCheckoutsSection ).queryByRole( 'checkbox', {
				name: 'Amazon Pay',
			} )
		).not.toBeInTheDocument();
		expect(
			within( expressCheckoutsSection ).queryByRole( 'link', {
				name: 'Customize Amazon Pay',
			} )
		).not.toBeInTheDocument();
		expect(
			within( expressCheckoutsSection ).getByRole( 'checkbox', {
				name: 'Apple Pay / Google Pay',
			} )
		).toBeInTheDocument();
		expect(
			within( expressCheckoutsSection ).getByRole( 'checkbox', {
				name: 'WooPay',
			} )
		).toBeInTheDocument();
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
			.getByText( /Your application to use Amazon Pay has been rejected/ )
			.closest( '.components-notice' );

		expect( rejectedNotice ).toHaveClass( 'is-error' );
		expect(
			within( expressCheckoutsSection ).getByRole( 'link', {
				name: /Contact support/,
			} )
		).toHaveAttribute(
			'href',
			'https://woocommerce.com/my-account/contact-support/'
		);
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

	it( 'links inactive buy now pay later methods to the BNPL guidance', () => {
		mockUseGetAvailablePaymentMethodIds.mockReturnValue( [
			'card',
			'affirm',
		] );
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active', requirements: [] },
			affirm_payments: { status: 'inactive', requirements: [] },
		} );

		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getByRole( 'checkbox', { name: 'Affirm' } )
		).toBeDisabled();
		expect(
			screen
				.getAllByRole( 'link', { name: /Learn more/ } )
				.find(
					( link ) =>
						link.getAttribute( 'href' ) ===
						'https://woocommerce.com/document/woopayments/payment-methods/buy-now-pay-later/#contact-support'
				)
		).toHaveAttribute(
			'href',
			'https://woocommerce.com/document/woopayments/payment-methods/buy-now-pay-later/#contact-support'
		);
	} );

	it( 'renders delayed approval guidance for Alipay when approval is pending', () => {
		mockUseGetAvailablePaymentMethodIds.mockReturnValue( [
			'card',
			'alipay',
		] );
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active', requirements: [] },
			alipay_payments: { status: 'pending', requirements: [] },
		} );

		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getByRole( 'checkbox', { name: 'Alipay' } )
		).toBeDisabled();
		expect( screen.getByText( 'Approval pending' ) ).toBeInTheDocument();
		expect(
			screen.getByText(
				/Your store must be live and fully functional before this payment method can be offered/
			)
		).toBeInTheDocument();
		expect(
			screen.getByText( /Approval typically takes 2–3 days/ )
		).toBeInTheDocument();
		expect(
			screen
				.getAllByRole( 'link', { name: /Learn more/ } )
				.find(
					( link ) =>
						link.getAttribute( 'href' ) ===
						'https://woocommerce.com/document/woopayments/payment-methods/local-payment-methods/#approval-delays'
				)
		).toHaveAttribute(
			'href',
			'https://woocommerce.com/document/woopayments/payment-methods/local-payment-methods/#approval-delays'
		);
	} );

	it( 'keeps generic pending guidance for non-delayed approval methods', () => {
		mockUseGetAvailablePaymentMethodIds.mockReturnValue( [
			'card',
			'klarna',
		] );
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active', requirements: [] },
			klarna_payments: { status: 'pending', requirements: [] },
		} );

		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getByRole( 'checkbox', { name: 'Klarna' } )
		).toBeDisabled();
		expect(
			screen.getByText(
				"This payment method is pending approval. It won't be available at checkout until it's approved."
			)
		).toBeInTheDocument();
		expect(
			screen.queryByText( /Approval typically takes 2–3 days/ )
		).not.toBeInTheDocument();
	} );

	it( 'links pending verification guidance to the native payments overview route', () => {
		mockUseGetAvailablePaymentMethodIds.mockReturnValue( [
			'card',
			'sepa_debit',
		] );
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active', requirements: [] },
			sepa_debit_payments: {
				status: 'pending_verification',
				requirements: [],
			},
		} );

		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getByRole( 'checkbox', { name: 'SEPA Direct Debit' } )
		).toBeDisabled();
		expect(
			screen.getByText(
				/SEPA Direct Debit won't be available at checkout yet/
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: /Payments overview/ } )
		).toHaveAttribute(
			'href',
			expect.stringContaining( 'path=%2Fwoopayments%2Foverview' )
		);
	} );

	it( 'renders rejected payment methods with a contact support link', () => {
		mockUseGetAvailablePaymentMethodIds.mockReturnValue( [
			'card',
			'affirm',
		] );
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active', requirements: [] },
			affirm_payments: {
				status: 'rejected',
				requirements: [],
			},
		} );

		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getByRole( 'checkbox', { name: 'Affirm' } )
		).toBeDisabled();
		expect(
			screen
				.getByText( /Your application to use Affirm has been rejected/ )
				.closest( '.components-notice' )
		).toHaveClass( 'is-error' );
		expect(
			screen.getByRole( 'link', { name: /Contact support/ } )
		).toHaveAttribute(
			'href',
			'https://woocommerce.com/my-account/contact-support/'
		);
	} );

	it( 'renders a missing-currency warning for enabled methods when multi-currency is off', () => {
		mockUseGetSettings.mockReturnValue( {
			account_country: 'US',
			store_currency: 'USD',
			is_multi_currency_enabled: false,
		} );
		mockUseGetAvailablePaymentMethodIds.mockReturnValue( [
			'card',
			'bancontact',
		] );
		mockUseEnabledPaymentMethodIds.mockReturnValue( [
			[ 'card', 'bancontact' ],
			noop,
		] );
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active', requirements: [] },
			bancontact_payments: { status: 'active', requirements: [] },
		} );

		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getByRole( 'checkbox', { name: 'Bancontact' } )
		).not.toBeDisabled();
		expect(
			screen.getByText(
				'Bancontact requires the EUR currency. Add EUR to your store to offer this payment method.'
			)
		).toBeInTheDocument();
	} );

	it( 'does not render a missing-currency warning for methods that are not enabled', () => {
		mockUseGetSettings.mockReturnValue( {
			account_country: 'US',
			store_currency: 'USD',
			is_multi_currency_enabled: false,
		} );
		mockUseGetAvailablePaymentMethodIds.mockReturnValue( [
			'card',
			'bancontact',
		] );
		mockUseEnabledPaymentMethodIds.mockReturnValue( [ [ 'card' ], noop ] );
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active', requirements: [] },
			bancontact_payments: { status: 'active', requirements: [] },
		} );

		render( <WooPaymentsSettingsPage /> );

		const bancontactCheckbox = screen.getByRole( 'checkbox', {
			name: 'Bancontact',
		} );

		expect( bancontactCheckbox ).toBeInTheDocument();
		expect( bancontactCheckbox ).not.toBeDisabled();
		expect( bancontactCheckbox ).not.toBeChecked();
		expect(
			screen.queryByText(
				'Bancontact requires the EUR currency. Add EUR to your store to offer this payment method.'
			)
		).not.toBeInTheDocument();
	} );

	it( 'renders a missing-currency warning with multiple supported currencies', () => {
		mockUseGetSettings.mockReturnValue( {
			account_country: 'US',
			store_currency: 'AUD',
			is_multi_currency_enabled: false,
		} );
		mockUseGetAvailablePaymentMethodIds.mockReturnValue( [
			'card',
			'klarna',
		] );
		mockUseEnabledPaymentMethodIds.mockReturnValue( [
			[ 'card', 'klarna' ],
			noop,
		] );
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active', requirements: [] },
			klarna_payments: { status: 'active', requirements: [] },
		} );

		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getByText(
				'Klarna requires at least one of the following currencies: USD, GBP, EUR, DKK, NOK, or SEK. Add at least one of these currencies to your store to offer this payment method.'
			)
		).toBeInTheDocument();
	} );

	it( 'uses account-country payment method currencies for missing-currency warnings', () => {
		mockUseGetSettings.mockReturnValue( {
			account_country: 'JP',
			store_currency: 'USD',
			is_multi_currency_enabled: false,
		} );
		mockUseGetAvailablePaymentMethodIds.mockReturnValue( [
			'card',
			'alipay',
		] );
		mockUseEnabledPaymentMethodIds.mockReturnValue( [
			[ 'card', 'alipay' ],
			noop,
		] );
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active', requirements: [] },
			alipay_payments: { status: 'active', requirements: [] },
		} );

		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getByText(
				'Alipay requires the JPY currency. Add JPY to your store to offer this payment method.'
			)
		).toBeInTheDocument();
	} );

	it( 'renders a missing-currency warning for JCB on non-JPY stores', () => {
		mockUseGetSettings.mockReturnValue( {
			account_country: 'US',
			store_currency: 'USD',
			is_multi_currency_enabled: false,
		} );
		mockUseGetAvailablePaymentMethodIds.mockReturnValue( [
			'card',
			'jcb',
		] );
		mockUseEnabledPaymentMethodIds.mockReturnValue( [
			[ 'card', 'jcb' ],
			noop,
		] );
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active', requirements: [] },
			jcb_payments: { status: 'active', requirements: [] },
		} );

		render( <WooPaymentsSettingsPage /> );

		expect(
			screen.getByText(
				'JCB requires the JPY currency. Add JPY to your store to offer this payment method.'
			)
		).toBeInTheDocument();
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
			getRecordedEventCalls( 'wcpay_test_mode_enabled' )
		).toHaveLength( 0 );
		expect(
			screen.getByRole( 'heading', {
				name: 'Are you sure you want to enable test mode?',
			} )
		).toBeInTheDocument();

		fireEvent.click( screen.getByRole( 'button', { name: 'Enable' } ) );

		expect( setTestMode ).toHaveBeenCalledWith( true );
		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_test_mode_enabled',
			{ source: 'wcadmin-settings-page' }
		);
	} );

	it( 'records Tracks when canceling the test mode confirmation modal', async () => {
		const setTestMode = jest.fn();
		mockUseTestMode.mockReturnValue( [ false, setTestMode ] );

		render( <WooPaymentsSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', { name: 'Enable test mode' } )
		);
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Cancel' } )
		);

		expect( setTestMode ).not.toHaveBeenCalled();
		expect(
			getRecordedEventCalls( 'wcpay_test_mode_enabled' )
		).toHaveLength( 0 );
		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_test_mode_modal_exit',
			{ source: 'wcadmin-settings-page' }
		);
	} );

	it( 'records Tracks when disabling test mode', async () => {
		const setTestMode = jest.fn();
		mockUseTestMode.mockReturnValue( [ true, setTestMode ] );

		render( <WooPaymentsSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', { name: 'Enable test mode' } )
		);

		expect( setTestMode ).toHaveBeenCalledWith( false );
		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_test_mode_disabled',
			{ source: 'wcadmin-settings-page' }
		);
	} );

	it( 'renders reference test-mode help links outside development mode', () => {
		render( <WooPaymentsSettingsPage /> );

		const section = getGeneralSettingsSection();

		expect(
			within( section ).getByRole( 'link', {
				name: /test card numbers/,
			} )
		).toHaveAttribute(
			'href',
			'https://woocommerce.com/document/woopayments/testing-and-troubleshooting/testing/#test-cards'
		);
		expect(
			within( section ).getByRole( 'link', { name: /Learn more/ } )
		).toHaveAttribute(
			'href',
			'https://woocommerce.com/document/woopayments/testing-and-troubleshooting/testing/'
		);
	} );

	it( 'renders reference test-mode help links in development mode', () => {
		mockUseDevMode.mockReturnValue( true );

		render( <WooPaymentsSettingsPage /> );

		const section = getGeneralSettingsSection();

		expect(
			within( section ).getByRole( 'link', {
				name: /WordPress environment/,
			} )
		).toHaveAttribute(
			'href',
			'https://make.wordpress.org/core/2020/08/27/wordpress-environment-types/'
		);
		expect(
			within( section ).getByRole( 'link', { name: /Learn more/ } )
		).toHaveAttribute(
			'href',
			'https://woocommerce.com/document/woopayments/testing-and-troubleshooting/testing/'
		);
		expect( section ).toHaveTextContent( 'WCPAY_DEV_MODE' );
	} );

	it( 'records Tracks when enabling WooPayments', async () => {
		const setIsWCPayEnabled = jest.fn();
		mockUseIsWCPayEnabled.mockReturnValue( [ false, setIsWCPayEnabled ] );

		render( <WooPaymentsSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', { name: 'Enable WooPayments' } )
		);

		expect( setIsWCPayEnabled ).toHaveBeenCalledWith( true );
		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_gateway_toggle',
			{
				action: 'enable',
				context: 'wcpay-settings',
			}
		);
	} );

	it( 'requires confirmation before disabling WooPayments and records Tracks after confirm', async () => {
		const setIsWCPayEnabled = jest.fn();
		mockUseIsWCPayEnabled.mockReturnValue( [ true, setIsWCPayEnabled ] );

		render( <WooPaymentsSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', { name: 'Enable WooPayments' } )
		);

		expect( setIsWCPayEnabled ).not.toHaveBeenCalled();
		expect( getGatewayToggleEventCalls( 'disable' ) ).toHaveLength( 0 );

		const dialog = screen.getByRole( 'dialog', {
			name: 'Disable WooPayments',
		} );
		expect(
			within( dialog ).getByText(
				'Payment methods that need WooPayments:'
			)
		).toBeInTheDocument();
		expect(
			within( dialog ).getByText( 'Credit / Debit Cards' )
		).toBeInTheDocument();
		expect( within( dialog ).getByText( 'Affirm' ) ).toBeInTheDocument();
		expect(
			within( dialog ).getByText( 'Apple Pay / Google Pay' )
		).toBeInTheDocument();
		expect(
			within( dialog ).getByText( 'Amazon Pay' )
		).toBeInTheDocument();
		expect(
			within( dialog ).getByText( 'Link by Stripe' )
		).toBeInTheDocument();
		expect( within( dialog ).getByText( 'WooPay' ) ).toBeInTheDocument();

		await userEvent.click(
			within( dialog ).getByRole( 'button', { name: 'Disable' } )
		);

		expect( setIsWCPayEnabled ).toHaveBeenCalledWith( false );
		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_gateway_toggle',
			{
				action: 'disable',
				context: 'wcpay-settings',
			}
		);
	} );

	it( 'does not disable WooPayments or record disable telemetry when canceling the confirmation', async () => {
		const setIsWCPayEnabled = jest.fn();
		mockUseIsWCPayEnabled.mockReturnValue( [ true, setIsWCPayEnabled ] );

		render( <WooPaymentsSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', { name: 'Enable WooPayments' } )
		);

		const dialog = screen.getByRole( 'dialog', {
			name: 'Disable WooPayments',
		} );
		await userEvent.click(
			within( dialog ).getByRole( 'button', { name: 'Cancel' } )
		);

		expect( setIsWCPayEnabled ).not.toHaveBeenCalled();
		expect( getGatewayToggleEventCalls( 'disable' ) ).toHaveLength( 0 );
		expect(
			screen.queryByRole( 'dialog', { name: 'Disable WooPayments' } )
		).not.toBeInTheDocument();
	} );

	it( 'uses effective express availability for affected methods in the disable confirmation', async () => {
		const setIsWCPayEnabled = jest.fn();
		mockUseIsWCPayEnabled.mockReturnValue( [ true, setIsWCPayEnabled ] );
		mockUseGetSettings.mockReturnValue( {
			account_country: 'US',
			store_currency: 'USD',
			is_multi_currency_enabled: true,
			feature_flags: {
				...DEFAULT_FEATURE_FLAGS,
				woopayExpressCheckout: false,
			},
			available_payment_method_ids: [
				'card',
				'link',
				'affirm',
				'amazon_pay',
				'apple_pay',
				'google_pay',
			],
		} );

		render( <WooPaymentsSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', { name: 'Enable WooPayments' } )
		);

		const dialog = screen.getByRole( 'dialog', {
			name: 'Disable WooPayments',
		} );

		expect(
			within( dialog ).getByText( 'Amazon Pay' )
		).toBeInTheDocument();
		expect(
			within( dialog ).queryByText( 'WooPay' )
		).not.toBeInTheDocument();
	} );

	it( 'uses Amazon Pay actionability for affected methods in the disable confirmation', async () => {
		const setIsWCPayEnabled = jest.fn();
		mockUseIsWCPayEnabled.mockReturnValue( [ true, setIsWCPayEnabled ] );
		mockUseGetPaymentMethodStatuses.mockReturnValue( {
			card_payments: { status: 'active' },
			link_payments: { status: 'active' },
			affirm_payments: { status: 'active' },
			amazon_pay_payments: { status: 'pending' },
		} );

		render( <WooPaymentsSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', { name: 'Enable WooPayments' } )
		);

		const dialog = screen.getByRole( 'dialog', {
			name: 'Disable WooPayments',
		} );

		expect(
			within( dialog ).queryByText( 'Amazon Pay' )
		).not.toBeInTheDocument();
		expect( within( dialog ).getByText( 'WooPay' ) ).toBeInTheDocument();
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
						setup: '#distinct-live-onboarding',
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
			'#distinct-live-onboarding?source=wcadmin-settings-page&from=wcpay-setup-live-payments'
		);
		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_setup_live_payments_modal_open',
			{
				from: 'WCPAY_SETTINGS',
				source: 'wcadmin-settings-page',
			}
		);
	} );

	it( 'records setup-live Tracks when activating payments from the modal', async () => {
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
						setup: '#distinct-live-onboarding',
					},
				} );
			}

			return Promise.resolve( {} );
		} );

		render( <WooPaymentsSettingsPage /> );

		await screen.findByText( 'You are using a test account.' );
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Activate payments' } )
		);

		const dialog = screen.getByRole( 'dialog', {
			name: 'Activate payments on your store',
		} );
		const activateButton = within( dialog ).getByRole( 'link', {
			name: 'Activate payments',
		} );
		expect( activateButton ).toHaveAttribute(
			'href',
			'#distinct-live-onboarding?source=wcadmin-settings-page&from=wcpay-setup-live-payments'
		);

		await userEvent.click( activateButton );

		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_onboarding_flow_setup_live_payments',
			{
				from: 'WCPAY_SETTINGS',
				source: 'wcadmin-settings-page',
			}
		);
		expect( window.location.hash ).toBe(
			'#distinct-live-onboarding?source=wcadmin-settings-page&from=wcpay-setup-live-payments'
		);
		expect( activateButton ).toHaveAttribute( 'aria-disabled', 'true' );
	} );

	it( 'records setup-live modal exit Tracks', async () => {
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

		await screen.findByText( 'You are using a test account.' );
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Activate payments' } )
		);

		const dialog = screen.getByRole( 'dialog', {
			name: 'Activate payments on your store',
		} );
		await userEvent.click(
			within( dialog ).getByRole( 'button', { name: 'Close' } )
		);

		await waitFor( () =>
			expect( mockRecordEvent ).toHaveBeenCalledWith(
				'wcpay_setup_live_payments_modal_exit',
				{
					from: 'WCPAY_SETTINGS',
					source: 'wcadmin-settings-page',
				}
			)
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
		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_settings_setup_live_payments_click',
			{ source: 'wcadmin-settings-page' }
		);
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
		const noticeCopy = heading.closest( 'p' ) as HTMLElement;
		expect(
			within( noticeCopy ).getByRole( 'link', {
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

	it( 'records payment request setting changes after a successful save', async () => {
		let isPaymentRequestEnabled = true;
		mockUseGetSettings.mockImplementation( () => ( {
			account_country: 'US',
			store_currency: 'USD',
			is_multi_currency_enabled: true,
			is_payment_request_enabled: isPaymentRequestEnabled,
			feature_flags: DEFAULT_FEATURE_FLAGS,
			available_payment_method_ids: [
				'card',
				'link',
				'affirm',
				'amazon_pay',
				'apple_pay',
				'google_pay',
			],
		} ) );
		mockSaveSettings.mockResolvedValue( true );

		const { rerender } = render( <WooPaymentsSettingsPage /> );

		isPaymentRequestEnabled = false;
		rerender( <WooPaymentsSettingsPage /> );

		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Save changes' } )
			);
		} );

		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_payment_request_settings_change',
			{ enabled: 'no' }
		);
	} );

	it( 'does not record payment request setting changes after a failed save', async () => {
		let isPaymentRequestEnabled = true;
		mockUseGetSettings.mockImplementation( () => ( {
			account_country: 'US',
			store_currency: 'USD',
			is_multi_currency_enabled: true,
			is_payment_request_enabled: isPaymentRequestEnabled,
			feature_flags: DEFAULT_FEATURE_FLAGS,
			available_payment_method_ids: [
				'card',
				'link',
				'affirm',
				'amazon_pay',
				'apple_pay',
				'google_pay',
			],
		} ) );
		mockSaveSettings.mockResolvedValue( false );

		const { rerender } = render( <WooPaymentsSettingsPage /> );

		isPaymentRequestEnabled = false;
		rerender( <WooPaymentsSettingsPage /> );

		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Save changes' } )
			);
		} );

		expect( mockRecordEvent ).not.toHaveBeenCalledWith(
			'wcpay_payment_request_settings_change',
			expect.anything()
		);
	} );

	it( 'does not record unchanged payment request settings after a successful save', async () => {
		mockUseGetSettings.mockImplementation( () => ( {
			account_country: 'US',
			store_currency: 'USD',
			is_multi_currency_enabled: true,
			is_payment_request_enabled: true,
			feature_flags: DEFAULT_FEATURE_FLAGS,
			available_payment_method_ids: [
				'card',
				'link',
				'affirm',
				'amazon_pay',
				'apple_pay',
				'google_pay',
			],
		} ) );
		mockSaveSettings.mockResolvedValue( true );

		render( <WooPaymentsSettingsPage /> );

		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Save changes' } )
			);
		} );

		expect( mockRecordEvent ).not.toHaveBeenCalledWith(
			'wcpay_payment_request_settings_change',
			expect.anything()
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

	it( 'records fraud protection risk level preset changes', async () => {
		const setProtectionLevel = jest.fn();
		mockUseCurrentProtectionLevel.mockReturnValue( [
			'basic',
			setProtectionLevel,
		] );
		mockUseAdvancedFraudProtectionSettings.mockReturnValue( [ [], noop ] );

		render( <WooPaymentsSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'radio', { name: 'Advanced' } )
		);

		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_fraud_protection_risk_level_preset_enabled',
			{ preset: 'advanced' }
		);
		expect( setProtectionLevel ).toHaveBeenCalledWith( 'advanced' );
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
		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_fraud_protection_basic_modal_viewed'
		);
	} );

	it( 'records fraud tour completion after the fraud section becomes visible', async () => {
		installMockIntersectionObserver();
		mockUseGetSettings.mockReturnValue( {
			account_country: 'US',
			store_currency: 'USD',
			feature_flags: DEFAULT_FEATURE_FLAGS,
			available_payment_method_ids: [
				'card',
				'link',
				'affirm',
				'amazon_pay',
				'apple_pay',
				'google_pay',
			],
			fraud_protection: {
				decline_on_avs_failure: true,
				decline_on_cvc_failure: true,
				is_welcome_tour_dismissed: false,
			},
		} );

		render( <WooPaymentsSettingsPage /> );

		const tourAnchor = document.getElementById(
			'fraud-protection-card-options'
		);
		expect( tourAnchor ).not.toBeNull();
		await waitFor( () => {
			expect( mockIntersectionObserverInstances ).toHaveLength( 1 );
		} );
		expect(
			mockIntersectionObserverInstances[ 0 ].observe
		).toHaveBeenCalledWith( tourAnchor );

		intersectObservedElement( tourAnchor as Element );

		expect(
			await screen.findByTestId( 'fraud-protection-tour' )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Enhanced fraud protection' )
		).toBeInTheDocument();
		expect(
			screen.queryByText( /\{\{strong\}\}/ )
		).not.toBeInTheDocument();
		expect(
			screen.getByText( /Payments > Transactions/ )
		).toBeInTheDocument();
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Finish fraud tour' } )
		);

		await waitFor( () => {
			expect( getFraudTourDismissalCalls() ).toHaveLength( 1 );
		} );
		expect( getFraudTourDismissalCalls()[ 0 ][ 0 ] ).toEqual( {
			path: FRAUD_TOUR_DISMISSAL_PATH,
			method: 'post',
			data: { value: true },
		} );
		expect( getFraudTourEventNames() ).toEqual( [
			'wcpay_fraud_protection_tour_clicked_through',
		] );
		expect(
			screen.queryByTestId( 'fraud-protection-tour' )
		).not.toBeInTheDocument();
	} );

	it( 'keeps the fraud tour recoverable when dismissal persistence fails', async () => {
		installMockIntersectionObserver();
		mockApiFetch.mockImplementation( ( options ) => {
			const path = typeof options === 'string' ? options : options?.path;

			if ( path === FRAUD_TOUR_DISMISSAL_PATH ) {
				return Promise.reject( new Error( 'option save failed' ) );
			}

			if ( path === '/wc-admin/settings/payments/woopayments/account' ) {
				return Promise.resolve( getDefaultAccountResponse() );
			}

			if ( path === '/wc/v3/payments/deposits/overview-all' ) {
				return new Promise( () => {} );
			}

			return Promise.resolve( {} );
		} );
		mockUseGetSettings.mockReturnValue( {
			account_country: 'US',
			store_currency: 'USD',
			feature_flags: DEFAULT_FEATURE_FLAGS,
			available_payment_method_ids: [
				'card',
				'link',
				'affirm',
				'amazon_pay',
				'apple_pay',
				'google_pay',
			],
			fraud_protection: {
				decline_on_avs_failure: true,
				decline_on_cvc_failure: true,
				is_welcome_tour_dismissed: false,
			},
		} );

		render( <WooPaymentsSettingsPage /> );

		const tourAnchor = document.getElementById(
			'fraud-protection-card-options'
		);
		expect( tourAnchor ).not.toBeNull();
		await waitFor( () => {
			expect( mockIntersectionObserverInstances ).toHaveLength( 1 );
		} );
		intersectObservedElement( tourAnchor as Element );
		await screen.findByTestId( 'fraud-protection-tour' );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Finish fraud tour' } )
		);

		await waitFor( () => {
			expect( getFraudTourDismissalCalls() ).toHaveLength( 1 );
		} );
		await waitFor( () => {
			expect( mockCreateErrorNotice ).toHaveBeenCalledWith(
				'Error saving option'
			);
		} );
		expect( getFraudTourEventNames() ).toEqual( [] );
		expect(
			screen.getByTestId( 'fraud-protection-tour' )
		).toBeInTheDocument();
	} );

	it( 'records fraud tour abandonment after the fraud section becomes visible', async () => {
		installMockIntersectionObserver();
		mockUseGetSettings.mockReturnValue( {
			feature_flags: DEFAULT_FEATURE_FLAGS,
			fraud_protection: {
				decline_on_avs_failure: true,
				decline_on_cvc_failure: true,
				is_welcome_tour_dismissed: false,
			},
		} );

		render( <WooPaymentsSettingsPage /> );

		const tourAnchor = document.getElementById(
			'fraud-protection-card-options'
		);
		expect( tourAnchor ).not.toBeNull();
		await waitFor( () => {
			expect( mockIntersectionObserverInstances ).toHaveLength( 1 );
		} );
		intersectObservedElement( tourAnchor as Element );
		await screen.findByTestId( 'fraud-protection-tour' );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Dismiss fraud tour' } )
		);

		await waitFor( () => {
			expect( getFraudTourDismissalCalls() ).toHaveLength( 1 );
		} );
		expect( getFraudTourDismissalCalls()[ 0 ][ 0 ] ).toEqual( {
			path: FRAUD_TOUR_DISMISSAL_PATH,
			method: 'post',
			data: { value: true },
		} );
		expect( getFraudTourEventNames() ).toEqual( [
			'wcpay_fraud_protection_tour_abandoned',
		] );
	} );

	it( 'records fraud tour abandonment when TourKit minimizes the visible tour', async () => {
		installMockIntersectionObserver();
		mockUseGetSettings.mockReturnValue( {
			feature_flags: DEFAULT_FEATURE_FLAGS,
			fraud_protection: {
				decline_on_avs_failure: true,
				decline_on_cvc_failure: true,
				is_welcome_tour_dismissed: false,
			},
		} );

		render( <WooPaymentsSettingsPage /> );

		const tourAnchor = document.getElementById(
			'fraud-protection-card-options'
		);
		expect( tourAnchor ).not.toBeNull();
		await waitFor( () => {
			expect( mockIntersectionObserverInstances ).toHaveLength( 1 );
		} );
		intersectObservedElement( tourAnchor as Element );
		await screen.findByTestId( 'fraud-protection-tour' );

		expect(
			mockTourKitConfigs[ 0 ].options?.callbacks?.onMinimize
		).toEqual( expect.any( Function ) );
		mockTourKitConfigs[ 0 ].options?.callbacks?.onMinimize?.( 0 );

		await waitFor( () => {
			expect( getFraudTourDismissalCalls() ).toHaveLength( 1 );
		} );
		expect( getFraudTourEventNames() ).toEqual( [
			'wcpay_fraud_protection_tour_abandoned',
		] );
		await waitFor( () =>
			expect(
				screen.queryByTestId( 'fraud-protection-tour' )
			).not.toBeInTheDocument()
		);
	} );

	it( 'does not start the fraud tour after it has been dismissed', () => {
		installMockIntersectionObserver();
		mockUseGetSettings.mockReturnValue( {
			feature_flags: DEFAULT_FEATURE_FLAGS,
			fraud_protection: {
				decline_on_avs_failure: true,
				decline_on_cvc_failure: true,
				is_welcome_tour_dismissed: true,
			},
		} );

		render( <WooPaymentsSettingsPage /> );

		expect( window.IntersectionObserver ).not.toHaveBeenCalled();
		expect(
			screen.queryByTestId( 'fraud-protection-tour' )
		).not.toBeInTheDocument();
	} );

	it( 'does not start the fraud tour while fraud settings failed to load', async () => {
		installMockIntersectionObserver();
		mockUseCurrentProtectionLevel.mockReturnValue( [ 'advanced', noop ] );
		mockUseAdvancedFraudProtectionSettings.mockReturnValue( [
			'error',
			noop,
		] );
		mockUseGetSettings.mockReturnValue( {
			feature_flags: DEFAULT_FEATURE_FLAGS,
			fraud_protection: {
				decline_on_avs_failure: true,
				decline_on_cvc_failure: true,
				is_welcome_tour_dismissed: false,
			},
		} );

		render( <WooPaymentsSettingsPage /> );

		const section = screen
			.getByRole( 'heading', { name: 'Fraud protection' } )
			.closest( '.woopayments-settings-section' ) as HTMLElement;
		expect(
			within( section ).getByText(
				'There was an error retrieving your fraud protection settings. Please refresh the page to try again.'
			)
		).toBeInTheDocument();
		expect(
			within( section ).getByRole( 'group', {
				name: 'Fraud protection level',
			} )
		).toBeDisabled();
		await act( async () => {
			await Promise.resolve();
			await Promise.resolve();
		} );

		expect( window.IntersectionObserver ).not.toHaveBeenCalled();
		expect( mockTourKitConfigs ).toHaveLength( 0 );
		expect(
			screen.queryByTestId( 'fraud-protection-tour' )
		).not.toBeInTheDocument();
	} );

	it( 'uses focusable targets and reduced-motion autoscroll for the fraud tour', async () => {
		installMockIntersectionObserver();
		const originalMatchMedia = window.matchMedia;
		window.matchMedia = jest.fn().mockReturnValue( { matches: true } );
		mockUseGetSettings.mockReturnValue( {
			feature_flags: DEFAULT_FEATURE_FLAGS,
			fraud_protection: {
				decline_on_avs_failure: true,
				decline_on_cvc_failure: true,
				is_welcome_tour_dismissed: false,
			},
		} );

		try {
			render( <WooPaymentsSettingsPage /> );

			const tourAnchor = document.getElementById(
				'fraud-protection-card-options'
			);
			expect( tourAnchor ).not.toBeNull();
			await waitFor( () => {
				expect( mockIntersectionObserverInstances ).toHaveLength( 1 );
			} );
			intersectObservedElement( tourAnchor as Element );
			await screen.findByTestId( 'fraud-protection-tour' );

			expect(
				mockTourKitConfigs[ 0 ].options?.effects?.autoScroll
			).toMatchObject( { behavior: 'auto', block: 'nearest' } );

			mockTourKitConfigs[ 0 ].steps.forEach( ( step ) => {
				const selector = step.focusElement?.desktop;

				if ( ! selector ) {
					return;
				}

				const focusTarget = document.querySelector( selector );
				expect( focusTarget ).toBeInstanceOf( window.HTMLInputElement );
			} );
		} finally {
			window.matchMedia = originalMatchMedia;
		}
	} );

	it( 'loads the fraud tour through an optional settings chunk', () => {
		const source = fs.readFileSync(
			nodePath.resolve( __dirname, '../fraud-protection/index.tsx' ),
			'utf8'
		);

		expect( source ).toContain(
			'webpackChunkName: "settings-payments-woopayments-fraud-tour"'
		);
		expect( source ).not.toContain(
			"import { FraudProtectionTour } from './tour'"
		);
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
