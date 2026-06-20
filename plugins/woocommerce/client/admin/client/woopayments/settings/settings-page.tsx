/**
 * External dependencies
 */
import {
	BaseControl,
	Button,
	Card,
	CheckboxControl,
	ExternalLink,
	Modal,
	Notice,
	SelectControl,
	Spinner,
	TextControl,
} from '@wordpress/components';
import {
	createInterpolateElement,
	lazy,
	Suspense,
	useEffect,
	useRef,
	useState,
} from '@wordpress/element';
import { dispatch } from '@wordpress/data';
import { __, _x, sprintf } from '@wordpress/i18n';
import { PhoneNumberInput, validatePhoneNumber } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { getSettingsPaymentsProviderRouteUrl } from '../admin/utils';
import { AccountModeNotice } from './account-mode-notice';
import { getWooPaymentsSettingsBootstrap } from './bootstrap';
import {
	saveOption,
	updateDismissedDuplicatePaymentMethodNotices,
} from './data/actions';
import { FraudProtectionSettings } from './fraud-protection';
import {
	DuplicatePaymentMethodNotice,
	getPaymentMethodAvailability,
	WooPaymentsPaymentMethodsList,
} from './payment-methods-list';
import type { WooPaymentsPaymentMethodDefinition } from './payment-method-definitions';
import { PayoutBankAccount } from './payout-bank-account';
import { SettingsBusyState } from './settings-busy-state';
import { WooPayDisableFeedback } from './woopay-disable-feedback';
import {
	isAmazonPayExpressCheckoutAvailable,
	isWooPayExpressCheckoutAvailable,
} from './express-checkout/settings-utils';
import type { PmPromotion } from '../promotions/types';
import { SpotlightPromotion } from '../promotions/spotlight';
import {
	useAccountBusinessSupportEmail,
	useAccountBusinessSupportPhone,
	useAccountCommunicationsEmail,
	useAccountStatementDescriptor,
	useAccountStatementDescriptorKana,
	useAccountStatementDescriptorKanji,
	useAmazonPayEnabledSettings,
	useCardPresentEligible,
	useCompletedWaitingPeriod,
	useDebugLog,
	useDepositDelayDays,
	useDepositRestrictions,
	useDepositScheduleInterval,
	useDepositScheduleMonthlyAnchor,
	useDepositScheduleWeeklyAnchor,
	useDepositStatus,
	useDevMode,
	useDismissedDuplicatePaymentMethodNotices,
	useEnabledPaymentMethodIds,
	useGetAccountFees,
	useGetAvailablePaymentMethodIds,
	useGetDuplicatedPaymentMethodIds,
	useGetPaymentMethodStatuses,
	useGetSavingError,
	useGetSettings,
	useIsWCPayEnabled,
	useLinkEnabledSettings,
	useManualCapture,
	useMultiCurrency,
	usePaymentRequestEnabledSettings,
	useSavedCards,
	useSelectedPaymentMethod as getSelectedPaymentMethodSetting,
	useSettings,
	useTestMode,
	useTestModeOnboarding,
	useUnselectedPaymentMethod as getUnselectedPaymentMethodSetting,
	useWooPayEnabledSettings,
	useWooPayShowIncompatibilityNotice,
	useWCPaySubscriptions,
} from './data/hooks';
import { getWooPaymentsAccountSettings } from './api';
import type { WooPaymentsVatDetails } from '../admin/documents/types';
import './data/store';
import './settings-page-only.scss';
import './style.scss';

const PROVIDER_NAME = 'WooPayments';
const VAT_DETAILS_MODAL_QUERY_PARAM = 'woopayments-vat-details-modal';
const WooPaymentsVatModal = lazy( () =>
	import(
		/* webpackChunkName: "settings-payments-woopayments-vat-modal" */ '../admin/documents/vat-modal'
	).then( ( module ) => ( { default: module.WooPaymentsVatModal } ) )
);
const HEADING_ID = 'woopayments-settings-page-heading';
const ACCOUNT_STATEMENT_MAX_LENGTH = 22;
const ACCOUNT_STATEMENT_MAX_LENGTH_KANJI = 17;
const ACCOUNT_STATEMENT_MAX_LENGTH_KANA = 22;
const NOTIFICATIONS_EMAIL_ERROR_ID = 'woopayments-notifications-email-error';
const NOTIFICATIONS_EMAIL_CONFIRM_ERROR_ID =
	'woopayments-notifications-email-confirm-error';
const NOTIFICATIONS_EMAIL_INPUT_ID = 'account-communications-email-input';
const SUPPORT_EMAIL_ERROR_ID = 'woopayments-support-email-error';
const SUPPORT_PHONE_ERROR_ID = 'woopayments-support-phone-error';
const ACCOUNT_STATEMENT_INPUT_ID = 'account-statement-descriptor-input';
const ACCOUNT_STATEMENT_KANJI_INPUT_ID =
	'account-statement-descriptor-kanji-input';
const ACCOUNT_STATEMENT_KANA_INPUT_ID =
	'account-statement-descriptor-kana-input';
const SUPPORT_EMAIL_INPUT_ID = 'account-business-support-email-input';
const SUPPORT_PHONE_INPUT_ID = 'account-business-support-phone-input';
const FEEDBACK_THROTTLE_DAYS = 7;
const MANUAL_CAPTURE_DOC_URL =
	'https://woocommerce.com/document/woopayments/settings-guide/authorize-and-capture/';
const EMAIL_ADDRESS_PATTERN =
	/^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9.-]+$/;
type SettingsRecord = Record< string, unknown >;
type StringSetter = ( value: string ) => void;
type BooleanSetter = ( value: boolean ) => void;
type ValidationSetter = ( isValid: boolean ) => void;
type StringArraySetter = ( value: string[] ) => void;
type BooleanSetting = [ boolean, BooleanSetter ];
type StringSetting = [ string, StringSetter ];
type StringArraySetting = [ string[], StringArraySetter ];
type PayoutInterval = 'daily' | 'weekly' | 'monthly';
type PayoutWeeklyAnchor =
	| 'monday'
	| 'tuesday'
	| 'wednesday'
	| 'thursday'
	| 'friday';
type PaymentMethodStatus = {
	status?: string;
	requirements?: unknown[];
};
type AccountFees = Record< string, Record< string, unknown > | undefined >;
type DuplicatePaymentMethodNotices = Record< string, string[] | undefined >;
type CustomizableExpressCheckoutMethod =
	| 'woopay'
	| 'payment_request'
	| 'amazon_pay';
type ExpressCheckoutOverviewMethod = CustomizableExpressCheckoutMethod | 'link';
type ExpressCheckoutOverviewRow = {
	id: ExpressCheckoutOverviewMethod;
	title: string;
	checked: boolean;
	disabled: boolean;
	onChange: ( value: boolean ) => void;
	description: React.ReactNode;
	notice: React.ReactNode;
	noticeStatus?: 'info' | 'warning' | 'error';
	action?: React.ReactNode;
	duplicatePaymentMethodId?: string;
};
type VatDetailsModalState = {
	isOpen: boolean;
	country: string;
	hasSubmittedVatData: boolean;
};

const BNPL_METHOD_IDS = [ 'affirm', 'afterpay_clearpay', 'klarna' ];
const EXPRESS_METHOD_IDS = [ 'payment_request', 'woopay', 'amazon_pay' ];
const STANDARD_PAYMENT_METHOD_EXCLUDED_IDS = [
	...EXPRESS_METHOD_IDS,
	'apple_pay',
	'google_pay',
	'link',
];
const AMAZON_PAY_DEFINITION: WooPaymentsPaymentMethodDefinition = {
	id: 'amazon_pay',
	label: __( 'Amazon Pay', 'woocommerce' ),
	description: __(
		'Allow customers to make payments using Amazon Pay.',
		'woocommerce'
	),
	iconUrl: '',
	stripeKey: 'amazon_pay_payments',
	currencies: [],
	allowsManualCapture: false,
	allowsPayLater: false,
};
const asString = ( value: unknown, fallback = '' ) =>
	typeof value === 'string' ? value : fallback;
const asBoolean = ( value: unknown, fallback = false ) =>
	typeof value === 'boolean' ? value : fallback;

const createTermsLink = ( href: string ) => (
	<ExternalLink href={ href }>
		<></>
	</ExternalLink>
);

const isVatDetailsModalDeepLinkActive = () => {
	if ( typeof window === 'undefined' ) {
		return false;
	}

	return (
		new URLSearchParams( window.location.search ).get(
			VAT_DETAILS_MODAL_QUERY_PARAM
		) === 'true'
	);
};

const removeVatDetailsModalQueryParam = () => {
	if ( typeof window === 'undefined' ) {
		return;
	}

	const url = new URL( window.location.href );
	url.searchParams.delete( VAT_DETAILS_MODAL_QUERY_PARAM );
	window.history.replaceState(
		window.history.state,
		'',
		`${ url.pathname }${ url.search }${ url.hash }`
	);
};

const getDaysSinceDate = ( date: string, now = new Date() ) => {
	const parsedDate = new Date( date );

	if ( Number.isNaN( parsedDate.getTime() ) ) {
		return Number.POSITIVE_INFINITY;
	}

	const diffTime = Math.abs( now.getTime() - parsedDate.getTime() );

	return Math.ceil( diffTime / ( 1000 * 60 * 60 * 24 ) );
};

const isWooPayDisableFeedbackThrottled = ( date: string ) =>
	date !== '' && getDaysSinceDate( date ) < FEEDBACK_THROTTLE_DAYS;

const asStringArray = ( value: unknown ) =>
	Array.isArray( value )
		? value.filter( ( item ): item is string => typeof item === 'string' )
		: [];

const asPayoutInterval = ( value: string ): PayoutInterval =>
	[ 'daily', 'weekly', 'monthly' ].includes( value )
		? ( value as PayoutInterval )
		: 'daily';

const asPayoutWeeklyAnchor = ( value: string ): PayoutWeeklyAnchor =>
	[ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday' ].includes( value )
		? ( value as PayoutWeeklyAnchor )
		: 'monday';

const getMonthlyAnchorLabel = ( anchor: number ) => {
	if ( anchor === 31 ) {
		return __( 'Last day of the month', 'woocommerce' );
	}

	if ( [ 1, 21 ].includes( anchor ) ) {
		return sprintf(
			/* translators: %d: Day of the month. */
			_x( '%dst', 'monthly payout schedule day option', 'woocommerce' ),
			anchor
		);
	}

	if ( [ 2, 22 ].includes( anchor ) ) {
		return sprintf(
			/* translators: %d: Day of the month. */
			_x( '%dnd', 'monthly payout schedule day option', 'woocommerce' ),
			anchor
		);
	}

	if ( [ 3, 23 ].includes( anchor ) ) {
		return sprintf(
			/* translators: %d: Day of the month. */
			_x( '%drd', 'monthly payout schedule day option', 'woocommerce' ),
			anchor
		);
	}

	return sprintf(
		/* translators: %d: Day of the month. */
		_x( '%dth', 'monthly payout schedule day option', 'woocommerce' ),
		anchor
	);
};

const getPayoutScheduleHelpText = ( payoutInterval: PayoutInterval ) => {
	if ( payoutInterval === 'monthly' ) {
		return __(
			'Payouts scheduled on a weekend will be sent on the next business day.',
			'woocommerce'
		);
	}

	if ( payoutInterval === 'weekly' ) {
		return __(
			'Payouts that fall on a holiday will initiate on the next business day.',
			'woocommerce'
		);
	}

	return __( 'Payouts will occur every business day.', 'woocommerce' );
};

const asSettingsRecord = ( value: unknown ): SettingsRecord =>
	value && typeof value === 'object' ? ( value as SettingsRecord ) : {};

const asAccountFees = ( value: unknown ): AccountFees =>
	value && typeof value === 'object' ? ( value as AccountFees ) : {};

const asDuplicatePaymentMethodNotices = (
	value: unknown
): DuplicatePaymentMethodNotices =>
	value && typeof value === 'object'
		? ( value as DuplicatePaymentMethodNotices )
		: {};
const asPmPromotions = ( value: unknown ): PmPromotion[] =>
	Array.isArray( value )
		? value.filter(
				( promotion ): promotion is PmPromotion =>
					Boolean( promotion ) &&
					typeof promotion === 'object' &&
					'id' in promotion &&
					'payment_method' in promotion &&
					'type' in promotion &&
					'title' in promotion
		  )
		: [];

const getSavingErrorDetailMessage = ( value: unknown, key: string ) => {
	const error = asSettingsRecord( value );
	const data = asSettingsRecord( error.data );
	const details = asSettingsRecord( data.details );
	const detail = asSettingsRecord( details[ key ] );

	return asString( detail.message );
};

const getSavingErrorDetails = ( value: unknown ) => {
	const error = asSettingsRecord( value );
	const data = asSettingsRecord( error.data );

	return asSettingsRecord( data.details );
};

const getFieldInputId = ( settingKey: string ) => {
	const fieldInputIds: Record< string, string > = {
		account_statement_descriptor: ACCOUNT_STATEMENT_INPUT_ID,
		account_statement_descriptor_kanji: ACCOUNT_STATEMENT_KANJI_INPUT_ID,
		account_statement_descriptor_kana: ACCOUNT_STATEMENT_KANA_INPUT_ID,
		account_communications_email: NOTIFICATIONS_EMAIL_INPUT_ID,
		account_business_support_email: SUPPORT_EMAIL_INPUT_ID,
		account_business_support_phone: SUPPORT_PHONE_INPUT_ID,
	};

	return (
		fieldInputIds[ settingKey ] ||
		`${ settingKey.replace( /_/g, '-' ) }-input`
	);
};

const focusFirstSavingErrorField = ( savingError: unknown ) => {
	const details = getSavingErrorDetails( savingError );
	const element = Object.keys( details )
		.map( ( fieldKey ) =>
			document.getElementById( getFieldInputId( fieldKey ) )
		)
		.find(
			( field ): field is HTMLElement =>
				!! field && typeof field.focus === 'function'
		);

	if ( ! element ) {
		return;
	}

	const reduceMotion =
		typeof window.matchMedia === 'function' &&
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	element.scrollIntoView?.( {
		behavior: reduceMotion ? 'auto' : 'smooth',
		block: 'center',
	} );
	element.focus( { preventScroll: true } );
};

const isValidEmailAddress = ( value: string ) =>
	value === '' ||
	( value.length <= 254 && EMAIL_ADDRESS_PATTERN.test( value ) );

const isCustomizableExpressCheckoutMethod = (
	methodId: ExpressCheckoutOverviewMethod
): methodId is CustomizableExpressCheckoutMethod => methodId !== 'link';

const getExpressCheckoutCheckboxId = (
	methodId: ExpressCheckoutOverviewMethod
) => `woopayments-express-checkout-${ methodId }-input`;

const SettingsSection = ( {
	id,
	title,
	description,
	children,
}: {
	id: string;
	title: string;
	description: React.ReactNode;
	children: React.ReactNode;
} ) => (
	<section className="woopayments-settings-section" id={ id }>
		<div className="woopayments-settings-section__details">
			<h2>{ title }</h2>
			<div className="woopayments-settings-section__description">
				{ description }
			</div>
		</div>
		<Card className="woopayments-settings-section__controls">
			{ children }
		</Card>
	</section>
);

const FieldGroup = ( {
	id,
	title,
	children,
}: {
	id?: string;
	title: string;
	children: React.ReactNode;
} ) => (
	<div className="woopayments-settings-field-group" id={ id }>
		<h3>{ title }</h3>
		{ children }
	</div>
);

const TestModeConfirmationModal = ( {
	onClose,
	onConfirm,
}: {
	onClose: () => void;
	onConfirm: () => void;
} ) => (
	<Modal
		title={ __( 'Enable test mode', 'woocommerce' ) }
		onRequestClose={ onClose }
		shouldCloseOnClickOutside={ false }
	>
		<div className="woopayments-settings-modal">
			<h2>
				{ __(
					'Are you sure you want to enable test mode?',
					'woocommerce'
				) }
			</h2>
			<p>
				{ __(
					"Test mode lets you try out payments, refunds, disputes, and similar processes while you're working on your store without handling live payment information. All incoming orders will be simulated, and test mode must be disabled before you can accept real orders.",
					'woocommerce'
				) }
			</p>
			<ExternalLink href="https://woocommerce.com/document/woopayments/testing-and-troubleshooting/testing/">
				{ __( 'Learn more about test mode', 'woocommerce' ) }
			</ExternalLink>
			<div className="woopayments-settings-modal__actions">
				<Button variant="secondary" onClick={ onClose }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button variant="primary" onClick={ onConfirm }>
					{ __( 'Enable', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	</Modal>
);

const ManualCaptureConfirmationModal = ( {
	onClose,
	onConfirm,
}: {
	onClose: () => void;
	onConfirm: () => void;
} ) => (
	<Modal
		title={ __( 'Enable manual capture', 'woocommerce' ) }
		onRequestClose={ onClose }
		className="woopayments-settings-modal"
	>
		<p>
			<strong>
				{ __(
					'Payments must be captured on the order details screen within 7 days of authorization',
					'woocommerce'
				) }
			</strong>
			{ __(
				', otherwise the authorization and order will be canceled.',
				'woocommerce'
			) }
			<br />
			<ExternalLink href={ MANUAL_CAPTURE_DOC_URL }>
				{ __( 'Learn more about manual capture', 'woocommerce' ) }
			</ExternalLink>
		</p>
		<Notice status="info" isDismissible={ false }>
			{ __(
				"Manual capture is available for card payments only. Payment methods that don't support it will be disabled.",
				'woocommerce'
			) }
		</Notice>
		<div className="woopayments-settings-modal__actions">
			<Button variant="tertiary" onClick={ onClose }>
				{ __( 'Cancel', 'woocommerce' ) }
			</Button>
			<Button variant="primary" onClick={ onConfirm }>
				{ __( 'Enable manual capture', 'woocommerce' ) }
			</Button>
		</div>
	</Modal>
);

const GeneralSettingsSection = () => {
	const [ isWCPayEnabled, setIsWCPayEnabled ] =
		useIsWCPayEnabled() as BooleanSetting;
	const [ isTestModeEnabled, setIsTestModeEnabled ] =
		useTestMode() as BooleanSetting;
	const isTestModeOnboarding = Boolean( useTestModeOnboarding() );
	const isDevModeEnabled = Boolean( useDevMode() );
	const [ isTestModeModalVisible, setTestModeModalVisible ] =
		useState( false );

	return (
		<>
			<SettingsSection
				id="general"
				title={ __( 'General', 'woocommerce' ) }
				description={
					<p>
						{ sprintf(
							/* translators: %s: Payment provider name. */
							__(
								'Enable or disable %s and choose the account mode used for transactions.',
								'woocommerce'
							),
							PROVIDER_NAME
						) }
					</p>
				}
			>
				<AccountModeNotice isDevModeEnabled={ isDevModeEnabled } />
				<CheckboxControl
					checked={ isWCPayEnabled }
					label={ sprintf(
						/* translators: %s: Payment provider name. */
						__( 'Enable %s', 'woocommerce' ),
						PROVIDER_NAME
					) }
					onChange={ ( value ) =>
						setIsWCPayEnabled( Boolean( value ) )
					}
					__nextHasNoMarginBottom
				/>
				{ ! isTestModeOnboarding && (
					<CheckboxControl
						checked={ isDevModeEnabled || isTestModeEnabled }
						disabled={ isDevModeEnabled }
						help={
							isDevModeEnabled
								? __(
										'Test mode is active because your store is running in a development or staging environment.',
										'woocommerce'
								  )
								: __(
										'Use test card numbers to simulate transactions before processing live payments.',
										'woocommerce'
								  )
						}
						label={
							isDevModeEnabled
								? __(
										'Enable test mode (enabled by development mode)',
										'woocommerce'
								  )
								: __( 'Enable test mode', 'woocommerce' )
						}
						onChange={ ( value ) => {
							if ( value ) {
								setTestModeModalVisible( true );
								return;
							}

							setIsTestModeEnabled( false );
						} }
						__nextHasNoMarginBottom
					/>
				) }
			</SettingsSection>
			{ isTestModeModalVisible && (
				<TestModeConfirmationModal
					onClose={ () => setTestModeModalVisible( false ) }
					onConfirm={ () => {
						setIsTestModeEnabled( true );
						setTestModeModalVisible( false );
					} }
				/>
			) }
		</>
	);
};

const PaymentMethodsSettingsSection = () => {
	const settings = asSettingsRecord( useGetSettings() );
	const availablePaymentMethodIds = asStringArray(
		useGetAvailablePaymentMethodIds()
	).filter(
		( methodId ) =>
			! STANDARD_PAYMENT_METHOD_EXCLUDED_IDS.includes( methodId )
	);
	const statuses = asSettingsRecord( useGetPaymentMethodStatuses() );
	const accountFees = asAccountFees( useGetAccountFees() );
	const duplicatedPaymentMethodIds = asDuplicatePaymentMethodNotices(
		useGetDuplicatedPaymentMethodIds()
	);
	const pmPromotions = asPmPromotions( settings.pm_promotions );
	const [
		dismissedDuplicatePaymentMethodNotices,
		setDismissedDuplicatePaymentMethodNotices,
	] = useDismissedDuplicatePaymentMethodNotices() as [
		DuplicatePaymentMethodNotices,
		typeof updateDismissedDuplicatePaymentMethodNotices
	];
	const standardPaymentMethodIds = availablePaymentMethodIds.filter(
		( methodId ) => ! BNPL_METHOD_IDS.includes( methodId )
	);
	const [ enabledMethodIds ] =
		useEnabledPaymentMethodIds() as StringArraySetting;
	const [ , addPaymentMethod ] = getSelectedPaymentMethodSetting() as [
		string[],
		( id: string ) => void
	];
	const [ , removePaymentMethod ] = getUnselectedPaymentMethodSetting() as [
		string[],
		( id: string ) => void
	];
	const [ isManualCaptureEnabled ] = useManualCapture() as BooleanSetting;
	const accountCountry = asString( settings.account_country );
	const storeCurrency = asString( settings.store_currency );
	const isMultiCurrencyEnabled = asBoolean(
		settings.is_multi_currency_enabled,
		true
	);
	const onDismissDuplicateNotice = (
		notices: Record< string, string[] >
	) => {
		setDismissedDuplicatePaymentMethodNotices( notices );
		saveOption(
			'wcpay_duplicate_payment_method_notices_dismissed',
			notices
		);
	};

	return (
		<SettingsSection
			id="payment-methods"
			title={ __( 'Payments accepted on checkout', 'woocommerce' ) }
			description={
				<p>
					{ __(
						'Add and edit the payment methods customers can use at checkout.',
						'woocommerce'
					) }
				</p>
			}
		>
			<FieldGroup title={ __( 'Payment methods', 'woocommerce' ) }>
				{ isManualCaptureEnabled && (
					<Notice
						status="warning"
						isDismissible={ false }
						className="woopayments-settings-payment-methods__manual-capture-notice"
					>
						{ __(
							"Manual capture is enabled, so any payment methods that don't support it have been automatically disabled.",
							'woocommerce'
						) }
					</Notice>
				) }
				<WooPaymentsPaymentMethodsList
					methodIds={ standardPaymentMethodIds }
					enabledMethodIds={ enabledMethodIds }
					statuses={
						statuses as Record<
							string,
							PaymentMethodStatus | undefined
						>
					}
					accountFees={ accountFees }
					pmPromotions={ pmPromotions }
					duplicatedPaymentMethodIds={ duplicatedPaymentMethodIds }
					dismissedDuplicatePaymentMethodNotices={
						dismissedDuplicatePaymentMethodNotices
					}
					isManualCaptureEnabled={ Boolean( isManualCaptureEnabled ) }
					isMultiCurrencyEnabled={ isMultiCurrencyEnabled }
					storeCurrency={ storeCurrency }
					accountCountry={ accountCountry }
					onEnable={ addPaymentMethod }
					onDisable={ removePaymentMethod }
					onDismissDuplicateNotice={ onDismissDuplicateNotice }
				/>
			</FieldGroup>
		</SettingsSection>
	);
};

const BuyNowPayLaterSettingsSection = () => {
	const settings = asSettingsRecord( useGetSettings() );
	const availablePaymentMethodIds = asStringArray(
		useGetAvailablePaymentMethodIds()
	);
	const statuses = asSettingsRecord( useGetPaymentMethodStatuses() );
	const accountFees = asAccountFees( useGetAccountFees() );
	const duplicatedPaymentMethodIds = asDuplicatePaymentMethodNotices(
		useGetDuplicatedPaymentMethodIds()
	);
	const pmPromotions = asPmPromotions( settings.pm_promotions );
	const [
		dismissedDuplicatePaymentMethodNotices,
		setDismissedDuplicatePaymentMethodNotices,
	] = useDismissedDuplicatePaymentMethodNotices() as [
		DuplicatePaymentMethodNotices,
		typeof updateDismissedDuplicatePaymentMethodNotices
	];
	const availableBuyNowPayLaterMethodIds = availablePaymentMethodIds.filter(
		( methodId ) => BNPL_METHOD_IDS.includes( methodId )
	);
	const [ enabledMethodIds ] =
		useEnabledPaymentMethodIds() as StringArraySetting;
	const [ , addPaymentMethod ] = getSelectedPaymentMethodSetting() as [
		string[],
		( id: string ) => void
	];
	const [ , removePaymentMethod ] = getUnselectedPaymentMethodSetting() as [
		string[],
		( id: string ) => void
	];
	const [ isManualCaptureEnabled ] = useManualCapture() as BooleanSetting;
	const accountCountry = asString( settings.account_country );
	const storeCurrency = asString( settings.store_currency );
	const isMultiCurrencyEnabled = asBoolean(
		settings.is_multi_currency_enabled,
		true
	);
	const onDismissDuplicateNotice = (
		notices: Record< string, string[] >
	) => {
		setDismissedDuplicatePaymentMethodNotices( notices );
		saveOption(
			'wcpay_duplicate_payment_method_notices_dismissed',
			notices
		);
	};

	return availableBuyNowPayLaterMethodIds.length === 0 ? null : (
		<SettingsSection
			id="buy-now-pay-later-methods"
			title={ __( 'Buy now, pay later', 'woocommerce' ) }
			description={
				<p>
					{ __(
						'Offer flexible payment options when they are available for your account.',
						'woocommerce'
					) }
				</p>
			}
		>
			<FieldGroup title={ __( 'Installment options', 'woocommerce' ) }>
				<WooPaymentsPaymentMethodsList
					methodIds={ availableBuyNowPayLaterMethodIds }
					enabledMethodIds={ enabledMethodIds }
					statuses={
						statuses as Record<
							string,
							PaymentMethodStatus | undefined
						>
					}
					accountFees={ accountFees }
					pmPromotions={ pmPromotions }
					duplicatedPaymentMethodIds={ duplicatedPaymentMethodIds }
					dismissedDuplicatePaymentMethodNotices={
						dismissedDuplicatePaymentMethodNotices
					}
					isManualCaptureEnabled={ Boolean( isManualCaptureEnabled ) }
					isMultiCurrencyEnabled={ isMultiCurrencyEnabled }
					storeCurrency={ storeCurrency }
					accountCountry={ accountCountry }
					onEnable={ addPaymentMethod }
					onDisable={ removePaymentMethod }
					onDismissDuplicateNotice={ onDismissDuplicateNotice }
				/>
			</FieldGroup>
		</SettingsSection>
	);
};

const ExpressCheckoutSettingsSection = () => {
	const settings = asSettingsRecord( useGetSettings() );
	const isWooPayExpressCheckoutAvailableForStore =
		isWooPayExpressCheckoutAvailable( settings );
	const [ isPaymentRequestEnabled, setIsPaymentRequestEnabled ] =
		usePaymentRequestEnabledSettings() as BooleanSetting;
	const [ isWooPayEnabled, setIsWooPayEnabled ] =
		useWooPayEnabledSettings() as BooleanSetting;
	const [ isLinkEnabled, setIsLinkEnabled ] = useLinkEnabledSettings(
		isWooPayExpressCheckoutAvailableForStore && isWooPayEnabled
	) as [ boolean, ( isEnabled: boolean ) => void, boolean ];
	const [ enabledMethodIds ] =
		useEnabledPaymentMethodIds() as StringArraySetting;
	const [ isAmazonPayEnabled, setIsAmazonPayEnabled ] =
		useAmazonPayEnabledSettings() as BooleanSetting;
	const availablePaymentMethodIds = asStringArray(
		useGetAvailablePaymentMethodIds()
	);
	const statuses = asSettingsRecord( useGetPaymentMethodStatuses() );
	const duplicatedPaymentMethodIds = asDuplicatePaymentMethodNotices(
		useGetDuplicatedPaymentMethodIds()
	);
	const [
		dismissedDuplicatePaymentMethodNotices,
		setDismissedDuplicatePaymentMethodNotices,
	] = useDismissedDuplicatePaymentMethodNotices() as [
		DuplicatePaymentMethodNotices,
		typeof updateDismissedDuplicatePaymentMethodNotices
	];
	const isLinkAvailable =
		enabledMethodIds.includes( 'card' ) &&
		availablePaymentMethodIds.includes( 'link' );
	const isAmazonPayAvailable =
		isAmazonPayExpressCheckoutAvailable( settings );
	const amazonPayAvailability = getPaymentMethodAvailability(
		AMAZON_PAY_DEFINITION,
		asSettingsRecord(
			statuses[ AMAZON_PAY_DEFINITION.stripeKey ]
		) as PaymentMethodStatus,
		false
	);
	const showWooPayIncompatibilityNotice = Boolean(
		useWooPayShowIncompatibilityNotice()
	);
	let wooPayNotice = '';

	const onDismissDuplicateNotice = (
		notices: Record< string, string[] >
	) => {
		setDismissedDuplicatePaymentMethodNotices( notices );
		saveOption(
			'wcpay_duplicate_payment_method_notices_dismissed',
			notices
		);
	};

	if ( isLinkEnabled ) {
		wooPayNotice = __(
			'To enable WooPay, you must first disable Link by Stripe.',
			'woocommerce'
		);
	} else if ( showWooPayIncompatibilityNotice ) {
		wooPayNotice = __(
			'One or more of your extensions are incompatible with WooPay.',
			'woocommerce'
		);
	}

	const getCustomizeUrl = ( methodId: CustomizableExpressCheckoutMethod ) =>
		getSettingsPaymentsProviderRouteUrl(
			`/woopayments/settings/express-checkout/${ methodId }?from=woopayments-settings`
		);

	const expressRows: ExpressCheckoutOverviewRow[] = [];

	if ( isWooPayExpressCheckoutAvailableForStore ) {
		expressRows.push( {
			id: 'woopay',
			title: __( 'WooPay', 'woocommerce' ),
			checked: isWooPayEnabled,
			disabled: isLinkEnabled,
			onChange: setIsWooPayEnabled,
			description: isWooPayEnabled
				? __(
						'Boost conversion and customer loyalty by offering a single click, secure way to pay.',
						'woocommerce'
				  )
				: createInterpolateElement(
						__(
							'Boost conversion and customer loyalty by offering a single click, secure way to pay. In order to use <wooPayLink>WooPay</wooPayLink>, you must agree to our <termsLink>WooCommerce Terms of Service</termsLink> and <privacyLink>Privacy Policy</privacyLink>. <trackingLink>Click here</trackingLink> to learn more about the data you will be sharing and opt-out options.',
							'woocommerce'
						),
						{
							wooPayLink: createTermsLink(
								'https://woocommerce.com/document/woopay-merchant-documentation/'
							),
							termsLink: createTermsLink(
								'https://wordpress.com/tos/'
							),
							privacyLink: createTermsLink(
								'https://automattic.com/privacy/'
							),
							trackingLink: createTermsLink(
								'https://woocommerce.com/usage-tracking/'
							),
						}
				  ),
			notice: wooPayNotice,
		} );
	}

	expressRows.push( {
		id: 'payment_request',
		title: __( 'Apple Pay / Google Pay', 'woocommerce' ),
		checked: isPaymentRequestEnabled,
		disabled: false,
		onChange: setIsPaymentRequestEnabled,
		description: isPaymentRequestEnabled
			? __(
					'Allow customers to make payments using Apple Pay and Google Pay.',
					'woocommerce'
			  )
			: createInterpolateElement(
					__(
						"Allow customers to make payments using Apple Pay and Google Pay. By enabling this feature, you agree to <appleStripeLink>Stripe</appleStripeLink> and <appleLink>Apple</appleLink>'s terms of use. By enabling this feature, you agree to <googleStripeLink>Stripe</googleStripeLink>, and <googleLink>Google</googleLink>'s terms of use.",
						'woocommerce'
					),
					{
						appleStripeLink: createTermsLink(
							'https://stripe.com/apple-pay/legal'
						),
						appleLink: createTermsLink(
							'https://developer.apple.com/apple-pay/acceptable-use-guidelines-for-websites/'
						),
						googleStripeLink: createTermsLink(
							'https://stripe.com/apple-pay/legal'
						),
						googleLink: createTermsLink(
							'https://androidpay.developers.google.com/terms/sellertos'
						),
					}
			  ),
		notice: '',
		duplicatePaymentMethodId: 'apple_pay_google_pay',
	} );

	if ( isLinkAvailable ) {
		expressRows.push( {
			id: 'link',
			title: __( 'Link by Stripe', 'woocommerce' ),
			checked: isLinkEnabled,
			disabled:
				isWooPayExpressCheckoutAvailableForStore && isWooPayEnabled,
			onChange: setIsLinkEnabled,
			description: isLinkEnabled
				? __(
						'Let customers use Link for faster checkout.',
						'woocommerce'
				  )
				: createInterpolateElement(
						__(
							'Let customers use Link for faster checkout. By enabling this feature, you agree to the <termsLink>Link by Stripe terms</termsLink>, and <privacyLink>Privacy Policy</privacyLink>.',
							'woocommerce'
						),
						{
							termsLink: createTermsLink(
								'https://link.com/terms'
							),
							privacyLink: createTermsLink(
								'https://link.com/privacy'
							),
						}
				  ),
			notice:
				isWooPayExpressCheckoutAvailableForStore && isWooPayEnabled
					? __(
							'To enable Link by Stripe, you must first disable WooPay.',
							'woocommerce'
					  )
					: '',
			action: (
				<Button
					variant="secondary"
					href="https://woocommerce.com/document/woopayments/payment-methods/link-by-stripe/"
					target="_blank"
					rel="noreferrer"
				>
					{ __( 'Read more', 'woocommerce' ) }
				</Button>
			),
		} );
	}

	if ( isAmazonPayAvailable ) {
		expressRows.push( {
			id: 'amazon_pay',
			title: __( 'Amazon Pay', 'woocommerce' ),
			checked: isAmazonPayEnabled,
			disabled: ! amazonPayAvailability.isActionable,
			onChange: setIsAmazonPayEnabled,
			description: createInterpolateElement(
				__(
					"Allow customers to make payments using Amazon Pay. By activating this feature, you accept <stripeLink>Stripe</stripeLink> and <amazonLink>Amazon</amazonLink>'s terms of use.",
					'woocommerce'
				),
				{
					stripeLink: createTermsLink(
						'https://stripe.com/legal/ssa'
					),
					amazonLink: createTermsLink(
						'https://stripe.com/legal/amazon-pay'
					),
				}
			),
			notice: amazonPayAvailability.notice || '',
			noticeStatus: amazonPayAvailability.noticeStatus,
		} );
	}

	return (
		<SettingsSection
			id="express-checkouts"
			title={ __( 'Express checkouts', 'woocommerce' ) }
			description={
				<>
					<p>
						{ __(
							'Let customers use digital wallets and express payment methods across your store.',
							'woocommerce'
						) }
					</p>
					<ExternalLink href="https://woocommerce.com/document/woopayments/settings-guide/#express-checkouts">
						{ __( 'Learn more', 'woocommerce' ) }
					</ExternalLink>
				</>
			}
		>
			<ul className="woopayments-settings-express-checkout-list">
				{ expressRows.map( ( row ) => (
					<li
						key={ row.id }
						className="woopayments-settings-express-checkout-list__item"
					>
						<div className="woopayments-settings-express-checkout-list__main">
							<CheckboxControl
								id={ getExpressCheckoutCheckboxId( row.id ) }
								checked={ row.checked }
								disabled={ row.disabled }
								label={ row.title }
								onChange={ ( value ) =>
									row.onChange( Boolean( value ) )
								}
								__nextHasNoMarginBottom
							/>
							<div className="woopayments-settings-express-checkout-list__body">
								<h3>{ row.title }</h3>
								<p>{ row.description }</p>
								{ row.notice && (
									<Notice
										status={ row.noticeStatus || 'warning' }
										isDismissible={ false }
									>
										{ row.notice }
									</Notice>
								) }
							</div>
							{ isCustomizableExpressCheckoutMethod( row.id ) && (
								<Button
									variant="secondary"
									href={ getCustomizeUrl( row.id ) }
									aria-label={ sprintf(
										/* translators: %s: Express checkout payment method name. */
										__( 'Customize %s', 'woocommerce' ),
										row.title
									) }
								>
									{ __( 'Customize', 'woocommerce' ) }
								</Button>
							) }
							{ row.action }
						</div>
						{ row.duplicatePaymentMethodId &&
							duplicatedPaymentMethodIds[
								row.duplicatePaymentMethodId
							]?.length &&
							! row.notice && (
								<DuplicatePaymentMethodNotice
									paymentMethodId={
										row.duplicatePaymentMethodId
									}
									gatewayIds={
										duplicatedPaymentMethodIds[
											row.duplicatePaymentMethodId
										] || []
									}
									dismissedNotices={
										dismissedDuplicatePaymentMethodNotices
									}
									onDismiss={ onDismissDuplicateNotice }
									onRestoreFocus={ () => {
										document
											.getElementById(
												getExpressCheckoutCheckboxId(
													row.id
												)
											)
											?.focus();
									} }
								/>
							) }
					</li>
				) ) }
			</ul>
		</SettingsSection>
	);
};

const TransactionsSettingsSection = ( {
	onValidationChange,
}: {
	onValidationChange?: ValidationSetter;
} ) => {
	const settings = asSettingsRecord( useGetSettings() );
	const [ isSavedCardsEnabled, setIsSavedCardsEnabled ] =
		useSavedCards() as BooleanSetting;
	const [ isManualCaptureEnabled, setIsManualCaptureEnabled ] =
		useManualCapture() as BooleanSetting;
	const [ isManualCaptureModalVisible, setManualCaptureModalVisible ] =
		useState( false );
	const [ accountStatementDescriptor, setAccountStatementDescriptor ] =
		useAccountStatementDescriptor() as StringSetting;
	const [
		accountStatementDescriptorKanji,
		setAccountStatementDescriptorKanji,
	] = useAccountStatementDescriptorKanji() as StringSetting;
	const [
		accountStatementDescriptorKana,
		setAccountStatementDescriptorKana,
	] = useAccountStatementDescriptorKana() as StringSetting;
	const [ supportEmail, setSupportEmail ] =
		useAccountBusinessSupportEmail() as StringSetting;
	const [ supportPhone, setSupportPhone ] =
		useAccountBusinessSupportPhone() as StringSetting;
	const [ initialSupportEmail ] = useState( supportEmail );
	const [ initialSupportPhone ] = useState( supportPhone );
	const [ hasSupportEmailBlurred, setHasSupportEmailBlurred ] =
		useState( false );
	const [ hasSupportPhoneChanged, setHasSupportPhoneChanged ] =
		useState( false );
	const [ supportPhoneCountry, setSupportPhoneCountry ] = useState<
		string | undefined
	>();
	const savingError = useGetSavingError();
	const savingErrorRecord = asSettingsRecord( savingError );
	const serverErrorMessage =
		asString( savingErrorRecord.server_error ) ||
		getSavingErrorDetailMessage(
			savingError,
			'account_statement_descriptor'
		);
	const accountCountry = asString( settings.account_country );
	const isTestModeOnboarding = Boolean( useTestModeOnboarding() );
	const supportEmailServerError = getSavingErrorDetailMessage(
		savingError,
		'account_business_support_email'
	);
	const supportPhoneServerError = getSavingErrorDetailMessage(
		savingError,
		'account_business_support_phone'
	);
	const isTestSupportPhoneValid =
		isTestModeOnboarding && supportPhone === '+10000000000';
	const isSupportPhoneEmpty = supportPhone === '';
	const isSupportPhoneFormatValid =
		isSupportPhoneEmpty ||
		isTestSupportPhoneValid ||
		validatePhoneNumber( supportPhone, supportPhoneCountry );
	const supportEmailInvalidFormat =
		supportEmail !== '' && ! isValidEmailAddress( supportEmail );
	const isSupportEmailValid =
		! supportEmailServerError &&
		! ( supportEmail === '' && initialSupportEmail !== '' ) &&
		! supportEmailInvalidFormat;
	const isSupportPhoneValid =
		! supportPhoneServerError &&
		! isSupportPhoneEmpty &&
		isSupportPhoneFormatValid;
	const supportEmailError =
		supportEmailServerError ||
		( supportEmail === '' && initialSupportEmail !== ''
			? __(
					'Support email cannot be empty once it has been set before, please specify.',
					'woocommerce'
			  )
			: '' ) ||
		( hasSupportEmailBlurred && supportEmailInvalidFormat
			? __( 'Please enter a valid email address.', 'woocommerce' )
			: '' );
	const supportPhoneError =
		supportPhoneServerError ||
		( isSupportPhoneEmpty && initialSupportPhone !== ''
			? __(
					'Support phone number cannot be empty once it has been set before, please specify.',
					'woocommerce'
			  )
			: '' ) ||
		( isSupportPhoneEmpty
			? __( 'Support phone number cannot be empty.', 'woocommerce' )
			: '' ) ||
		( hasSupportPhoneChanged && ! isSupportPhoneFormatValid
			? __( 'Please enter a valid phone number.', 'woocommerce' )
			: '' );

	useEffect( () => {
		onValidationChange?.( isSupportEmailValid && isSupportPhoneValid );
	}, [ isSupportEmailValid, isSupportPhoneValid, onValidationChange ] );

	return (
		<SettingsSection
			id="transactions"
			title={ __( 'Transactions', 'woocommerce' ) }
			description={
				<p>
					{ __(
						'Update transaction preferences, customer statements, and support contact details.',
						'woocommerce'
					) }
				</p>
			}
		>
			<FieldGroup
				title={ __( 'Transaction preferences', 'woocommerce' ) }
			>
				<CheckboxControl
					checked={ isSavedCardsEnabled }
					help={ __(
						'When enabled, users will be able to pay with a saved card during checkout. Card details are stored in our platform, not on your store.',
						'woocommerce'
					) }
					label={ __(
						'Enable payments via saved cards',
						'woocommerce'
					) }
					onChange={ ( value ) =>
						setIsSavedCardsEnabled( Boolean( value ) )
					}
					__nextHasNoMarginBottom
				/>
				<CheckboxControl
					checked={ isManualCaptureEnabled }
					help={ __(
						'Authorize charges first and capture funds later.',
						'woocommerce'
					) }
					label={ __(
						'Issue an authorization on checkout and capture later',
						'woocommerce'
					) }
					onChange={ ( value ) => {
						if ( ! value ) {
							setIsManualCaptureEnabled( false );
							return;
						}

						setManualCaptureModalVisible( true );
					} }
					__nextHasNoMarginBottom
				/>
				{ isManualCaptureModalVisible && (
					<ManualCaptureConfirmationModal
						onClose={ () => setManualCaptureModalVisible( false ) }
						onConfirm={ () => {
							setIsManualCaptureEnabled( true );
							setManualCaptureModalVisible( false );
						} }
					/>
				) }
			</FieldGroup>
			<FieldGroup title={ __( 'Customer statements', 'woocommerce' ) }>
				<p>
					{ __(
						"Edit the way your store name appears on your customers' bank statements.",
						'woocommerce'
					) }
				</p>
				{ serverErrorMessage && (
					<Notice status="error" isDismissible={ false }>
						{ serverErrorMessage }
					</Notice>
				) }
				<TextControl
					id={ ACCOUNT_STATEMENT_INPUT_ID }
					label={ __( 'Customer bank statement', 'woocommerce' ) }
					value={ accountStatementDescriptor }
					maxLength={ ACCOUNT_STATEMENT_MAX_LENGTH }
					onChange={ setAccountStatementDescriptor }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
				<p className="woopayments-settings-character-count">
					{ sprintf(
						/* translators: 1: Current character count, 2: Maximum character count. */
						__( '%1$d of %2$d characters', 'woocommerce' ),
						accountStatementDescriptor.length,
						ACCOUNT_STATEMENT_MAX_LENGTH
					) }
				</p>
				{ accountCountry === 'JP' && (
					<div className="woopayments-settings-control-grid">
						<TextControl
							id={ ACCOUNT_STATEMENT_KANJI_INPUT_ID }
							label={ __(
								'Customer bank statement (kanji)',
								'woocommerce'
							) }
							value={ accountStatementDescriptorKanji }
							maxLength={ ACCOUNT_STATEMENT_MAX_LENGTH_KANJI }
							onChange={ setAccountStatementDescriptorKanji }
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
						<TextControl
							id={ ACCOUNT_STATEMENT_KANA_INPUT_ID }
							label={ __(
								'Customer bank statement (kana)',
								'woocommerce'
							) }
							value={ accountStatementDescriptorKana }
							maxLength={ ACCOUNT_STATEMENT_MAX_LENGTH_KANA }
							onChange={ setAccountStatementDescriptorKana }
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
					</div>
				) }
			</FieldGroup>
			<FieldGroup title={ __( 'Customer support', 'woocommerce' ) }>
				<p>
					{ __(
						'Provide contact information where customers can reach you for support.',
						'woocommerce'
					) }
				</p>
				<div className="woopayments-settings-control-grid">
					<div>
						<div id={ SUPPORT_EMAIL_ERROR_ID }>
							{ supportEmailError && (
								<Notice status="error" isDismissible={ false }>
									{ supportEmailError }
								</Notice>
							) }
						</div>
						<TextControl
							id={ SUPPORT_EMAIL_INPUT_ID }
							label={ __( 'Support email', 'woocommerce' ) }
							help={ __(
								'This may be visible on receipts, invoices, and automated emails from your store.',
								'woocommerce'
							) }
							type="email"
							value={ supportEmail }
							onChange={ setSupportEmail }
							onBlur={ () => setHasSupportEmailBlurred( true ) }
							aria-invalid={
								supportEmailError ? true : undefined
							}
							aria-describedby={
								supportEmailError
									? SUPPORT_EMAIL_ERROR_ID
									: undefined
							}
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
					</div>
					<div>
						<div id={ SUPPORT_PHONE_ERROR_ID }>
							{ supportPhoneError && (
								<Notice status="error" isDismissible={ false }>
									{ supportPhoneError }
								</Notice>
							) }
						</div>
						<BaseControl
							label={ __(
								'Support phone number',
								'woocommerce'
							) }
							help={
								<>
									{ __(
										'This may be visible on receipts, invoices, and automated emails from your store.',
										'woocommerce'
									) }
									{ isTestModeOnboarding && (
										<>
											<br />
											{ __(
												'(+1 0000000000 can be used for test accounts)',
												'woocommerce'
											) }
										</>
									) }
								</>
							}
							id={ SUPPORT_PHONE_INPUT_ID }
							__nextHasNoMarginBottom
						>
							<PhoneNumberInput
								id={ SUPPORT_PHONE_INPUT_ID }
								value={ supportPhone }
								onChange={ ( value, e164, country ) => {
									const localDigits = value
										.replace( /^\+\d+\s*/, '' )
										.replace( /\D/g, '' );

									setSupportPhone(
										localDigits === '' ? '' : e164
									);
									setSupportPhoneCountry( country );
									setHasSupportPhoneChanged( true );
								} }
								inputProps={ {
									'aria-invalid': supportPhoneError
										? true
										: undefined,
									'aria-describedby': supportPhoneError
										? SUPPORT_PHONE_ERROR_ID
										: undefined,
									onBlur: () =>
										setHasSupportPhoneChanged( true ),
								} }
							/>
						</BaseControl>
					</div>
				</div>
			</FieldGroup>
		</SettingsSection>
	);
};

const PayoutsSettingsSection = () => {
	const [ interval, setInterval ] =
		useDepositScheduleInterval() as StringSetting;
	const [ weeklyAnchor, setWeeklyAnchor ] =
		useDepositScheduleWeeklyAnchor() as StringSetting;
	const [ monthlyAnchor, setMonthlyAnchor ] =
		useDepositScheduleMonthlyAnchor() as StringSetting;
	const depositDelayDays = useDepositDelayDays();
	const completedWaitingPeriod = Boolean( useCompletedWaitingPeriod() );
	const depositStatus = asString( useDepositStatus() );
	const depositRestrictions = asString( useDepositRestrictions() );
	const accountCountry = asString(
		asSettingsRecord( useGetSettings() ).account_country
	);
	const intervalOptions = [
		...( accountCountry === 'JP'
			? []
			: [ { label: __( 'Daily', 'woocommerce' ), value: 'daily' } ] ),
		{ label: __( 'Weekly', 'woocommerce' ), value: 'weekly' },
		{ label: __( 'Monthly', 'woocommerce' ), value: 'monthly' },
	] as const;
	const payoutInterval =
		accountCountry === 'JP' && interval === 'daily'
			? 'weekly'
			: asPayoutInterval( interval );
	const isScheduleRestricted =
		depositStatus !== 'enabled' ||
		depositRestrictions === 'schedule_restricted';
	const isWaitingPeriodIncomplete = ! completedWaitingPeriod;

	return (
		<SettingsSection
			id="payouts"
			title={ __( 'Payouts', 'woocommerce' ) }
			description={
				<p>
					{ sprintf(
						/* translators: %s: Number of business days. */
						__(
							'Funds are available for payout %s business days after they are received.',
							'woocommerce'
						),
						String( depositDelayDays )
					) }
				</p>
			}
		>
			<FieldGroup
				id="payout-schedule"
				title={ __( 'Payout schedule', 'woocommerce' ) }
			>
				{ isScheduleRestricted && (
					<Notice status="warning" isDismissible={ false }>
						<p>
							{ __(
								'Payout scheduling is currently unavailable for this account.',
								'woocommerce'
							) }
						</p>
						<ExternalLink href="https://woocommerce.com/document/woopayments/payouts/payout-schedule/">
							{ __( 'Learn more', 'woocommerce' ) }
						</ExternalLink>
					</Notice>
				) }
				{ ! isScheduleRestricted && isWaitingPeriodIncomplete && (
					<Notice status="warning" isDismissible={ false }>
						<p>
							{ __(
								'Payout scheduling becomes available after the standard 7-day waiting period for new accounts is complete.',
								'woocommerce'
							) }
						</p>
						<ExternalLink href="https://woocommerce.com/document/woopayments/payouts/payout-schedule/">
							{ __( 'Learn more', 'woocommerce' ) }
						</ExternalLink>
					</Notice>
				) }
				{ ! isScheduleRestricted && ! isWaitingPeriodIncomplete && (
					<div className="woopayments-settings-control-grid">
						<SelectControl
							label={ __( 'Frequency', 'woocommerce' ) }
							value={ payoutInterval }
							options={ intervalOptions }
							onChange={ setInterval }
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
						{ payoutInterval === 'weekly' && (
							<SelectControl
								label={ __( 'Day', 'woocommerce' ) }
								value={ asPayoutWeeklyAnchor( weeklyAnchor ) }
								options={ [
									{
										label: __( 'Monday', 'woocommerce' ),
										value: 'monday',
									},
									{
										label: __( 'Tuesday', 'woocommerce' ),
										value: 'tuesday',
									},
									{
										label: __( 'Wednesday', 'woocommerce' ),
										value: 'wednesday',
									},
									{
										label: __( 'Thursday', 'woocommerce' ),
										value: 'thursday',
									},
									{
										label: __( 'Friday', 'woocommerce' ),
										value: 'friday',
									},
								] }
								onChange={ setWeeklyAnchor }
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
						) }
						{ payoutInterval === 'monthly' && (
							<SelectControl
								label={ __( 'Date', 'woocommerce' ) }
								value={ monthlyAnchor }
								options={ [
									...Array.from(
										{ length: 28 },
										( _value, index ) => ( {
											label: getMonthlyAnchorLabel(
												index + 1
											),
											value: String( index + 1 ),
										} )
									),
									{
										label: getMonthlyAnchorLabel( 31 ),
										value: '31',
									},
								] }
								onChange={ setMonthlyAnchor }
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
						) }
					</div>
				) }
				{ ! isScheduleRestricted && ! isWaitingPeriodIncomplete && (
					<p className="woopayments-settings-muted">
						{ getPayoutScheduleHelpText( payoutInterval ) }
					</p>
				) }
			</FieldGroup>
			<FieldGroup title={ __( 'Payout bank account', 'woocommerce' ) }>
				<PayoutBankAccount />
			</FieldGroup>
		</SettingsSection>
	);
};

const NotificationsSettingsSection = ( {
	onValidationChange,
}: {
	onValidationChange?: ValidationSetter;
} ) => {
	const [ email, setEmail ] =
		useAccountCommunicationsEmail() as StringSetting;
	const [ initialEmail ] = useState( email );
	const [ hasBlurred, setHasBlurred ] = useState( false );
	const [ confirmEmail, setConfirmEmail ] = useState( '' );
	const [ hasConfirmBlurred, setHasConfirmBlurred ] = useState( false );
	const savingError = useGetSavingError();
	const serverError = getSavingErrorDetailMessage(
		savingError,
		'account_communications_email'
	);
	const emailHasChanged = email !== initialEmail;
	const emailsMatch = ! emailHasChanged || email === confirmEmail;
	const isNotificationEmailValid =
		emailsMatch && ( email === '' || isValidEmailAddress( email ) );
	const emailError =
		serverError ||
		( hasBlurred && ! isValidEmailAddress( email )
			? __( 'Please enter a valid email address.', 'woocommerce' )
			: '' );
	const confirmEmailError =
		emailHasChanged && hasConfirmBlurred && ! emailsMatch
			? __(
					'Email addresses do not match. Please re-enter your email address.',
					'woocommerce'
			  )
			: '';

	useEffect( () => {
		onValidationChange?.( isNotificationEmailValid );
	}, [ isNotificationEmailValid, onValidationChange ] );

	return (
		<SettingsSection
			id="notifications"
			title={ __( 'Account notifications', 'woocommerce' ) }
			description={
				<p>
					{ __(
						'Receive important notifications about your WooPayments account.',
						'woocommerce'
					) }
				</p>
			}
		>
			<FieldGroup title={ __( 'Notifications email', 'woocommerce' ) }>
				<p className="woopayments-settings-muted">
					{ __(
						'Provide an email address where you would like to receive communications about your WooPayments account.',
						'woocommerce'
					) }
				</p>
				<Notice
					status="warning"
					isDismissible={ false }
					className="woopayments-settings-notifications-email-warning"
				>
					{ __(
						'Anyone with access to this email address will be treated as the account owner. Please verify the address carefully.',
						'woocommerce'
					) }
				</Notice>
				<div id={ NOTIFICATIONS_EMAIL_ERROR_ID }>
					{ emailError && (
						<Notice status="error" isDismissible={ false }>
							{ emailError }
						</Notice>
					) }
				</div>
				<TextControl
					id={ NOTIFICATIONS_EMAIL_INPUT_ID }
					label={ __( 'Email address', 'woocommerce' ) }
					type="email"
					value={ email }
					onChange={ setEmail }
					onBlur={ () => setHasBlurred( true ) }
					aria-invalid={ emailError ? true : undefined }
					aria-describedby={
						emailError ? NOTIFICATIONS_EMAIL_ERROR_ID : undefined
					}
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
				{ emailHasChanged && (
					<>
						<div id={ NOTIFICATIONS_EMAIL_CONFIRM_ERROR_ID }>
							{ confirmEmailError && (
								<Notice status="error" isDismissible={ false }>
									{ confirmEmailError }
								</Notice>
							) }
						</div>
						<TextControl
							label={ __(
								'Confirm email address',
								'woocommerce'
							) }
							type="email"
							value={ confirmEmail }
							onChange={ setConfirmEmail }
							onBlur={ () => setHasConfirmBlurred( true ) }
							aria-invalid={
								confirmEmailError ? true : undefined
							}
							aria-describedby={
								confirmEmailError
									? NOTIFICATIONS_EMAIL_CONFIRM_ERROR_ID
									: undefined
							}
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
					</>
				) }
			</FieldGroup>
		</SettingsSection>
	);
};

const FraudProtectionSettingsSection = () => {
	return (
		<SettingsSection
			id="fraud-protection"
			title={ __( 'Fraud protection', 'woocommerce' ) }
			description={
				<>
					<p>
						{ __(
							'Help avoid unauthorized transactions and disputes by setting your fraud protection level.',
							'woocommerce'
						) }
					</p>
					<ExternalLink href="https://woocommerce.com/document/woopayments/fraud-and-disputes/fraud-protection/">
						{ __(
							'Learn more about fraud protection',
							'woocommerce'
						) }
					</ExternalLink>
				</>
			}
		>
			<FraudProtectionSettings />
		</SettingsSection>
	);
};

const AdvancedSettingsSection = () => {
	const [ isMultiCurrencyEnabled, setIsMultiCurrencyEnabled ] =
		useMultiCurrency() as BooleanSetting;
	const [ isDebugLogEnabled, setIsDebugLogEnabled ] =
		useDebugLog() as BooleanSetting;
	const isDevModeEnabled = Boolean( useDevMode() );
	const [
		isWCPaySubscriptionsEnabled,
		isWCPaySubscriptionsEligible,
		setIsWCPaySubscriptionsEnabled,
	] = useWCPaySubscriptions() as [ boolean, boolean, BooleanSetter ];

	return (
		<SettingsSection
			id="advanced"
			title={ __( 'Advanced settings', 'woocommerce' ) }
			description={
				<p>
					{ __(
						'Configure payment features that apply to specific store needs.',
						'woocommerce'
					) }
				</p>
			}
		>
			<CheckboxControl
				checked={ isMultiCurrencyEnabled }
				label={ __( 'Enable multi-currency', 'woocommerce' ) }
				help={
					<>
						{ __(
							'Allow customers to shop and pay in multiple currencies.',
							'woocommerce'
						) }{ ' ' }
						<ExternalLink href="https://woocommerce.com/document/woopayments/currencies/multi-currency-setup/">
							{ __( 'Learn more', 'woocommerce' ) }
						</ExternalLink>
					</>
				}
				onChange={ ( value ) =>
					setIsMultiCurrencyEnabled( Boolean( value ) )
				}
				__nextHasNoMarginBottom
			/>
			<CheckboxControl
				checked={ isWCPaySubscriptionsEnabled }
				disabled={
					! isWCPaySubscriptionsEligible ||
					! isWCPaySubscriptionsEnabled
				}
				help={
					isWCPaySubscriptionsEligible ? (
						<>
							{ __(
								'This feature is deprecated. Existing subscription renewals will continue to work, but creating or managing subscriptions is no longer available. Install',
								'woocommerce'
							) }{ ' ' }
							<ExternalLink href="https://woocommerce.com/products/woocommerce-subscriptions/">
								{ __(
									'WooCommerce Subscriptions',
									'woocommerce'
								) }
							</ExternalLink>{ ' ' }
							{ __(
								'to continue managing subscriptions.',
								'woocommerce'
							) }
						</>
					) : (
						__(
							'WooPayments subscriptions are not available for this account.',
							'woocommerce'
						)
					)
				}
				label={ __(
					'Enable Subscriptions with WooPayments',
					'woocommerce'
				) }
				onChange={ ( value ) => {
					if ( value ) {
						return;
					}

					setIsWCPaySubscriptionsEnabled( false );
				} }
				__nextHasNoMarginBottom
			/>
			<CheckboxControl
				checked={ isDevModeEnabled || isDebugLogEnabled }
				disabled={ isDevModeEnabled }
				help={ __(
					'When enabled, payment error logs will be saved to WooCommerce > Status > Logs.',
					'woocommerce'
				) }
				label={
					isDevModeEnabled
						? __(
								'Log error messages (defaulted on for test accounts)',
								'woocommerce'
						  )
						: __( 'Log error messages', 'woocommerce' )
				}
				onChange={ ( value ) =>
					setIsDebugLogEnabled( Boolean( value ) )
				}
				__nextHasNoMarginBottom
			/>
		</SettingsSection>
	);
};

const SaveSettingsSection = ( { disabled }: { disabled?: boolean } ) => {
	const { saveSettings, isSaving, isLoading, isDirty } = useSettings();
	const settings = asSettingsRecord( useGetSettings() );
	const savingError = useGetSavingError();
	const [ statusMessage, setStatusMessage ] = useState( '' );
	const [ initialIsWooPayEnabled, setInitialIsWooPayEnabled ] = useState<
		boolean | null
	>( null );
	const [ isWooPayDisableFeedbackOpen, setWooPayDisableFeedbackOpen ] =
		useState( false );
	const [ shouldFocusSavingError, setShouldFocusSavingError ] =
		useState( false );
	const [ localWooPayLastDisableDate, setLocalWooPayLastDisableDate ] =
		useState( asString( settings.woopay_last_disable_date ) );
	const isDisabled = isSaving || isLoading || disabled || ! isDirty;
	const hasWooPayEnabledSetting = Object.prototype.hasOwnProperty.call(
		settings,
		'is_woopay_enabled'
	);
	const isWooPayEnabled = Boolean( settings.is_woopay_enabled );
	const wooPayLastDisableDate = asString( settings.woopay_last_disable_date );

	useEffect( () => {
		if ( initialIsWooPayEnabled !== null || ! hasWooPayEnabledSetting ) {
			return;
		}

		setInitialIsWooPayEnabled( isWooPayEnabled );
	}, [ hasWooPayEnabledSetting, initialIsWooPayEnabled, isWooPayEnabled ] );

	useEffect( () => {
		setLocalWooPayLastDisableDate( wooPayLastDisableDate );
	}, [ wooPayLastDisableDate ] );

	useEffect( () => {
		if ( ! shouldFocusSavingError ) {
			return;
		}

		focusFirstSavingErrorField( savingError );
		setShouldFocusSavingError( false );
	}, [ savingError, shouldFocusSavingError ] );

	const saveOnClick = async () => {
		if ( isDisabled ) {
			return;
		}

		setStatusMessage( '' );
		const isSuccess = await saveSettings();
		setStatusMessage(
			isSuccess
				? __( 'Settings saved.', 'woocommerce' )
				: __( 'Error saving settings.', 'woocommerce' )
		);

		if ( ! isSuccess ) {
			setShouldFocusSavingError( true );
			return;
		}

		if (
			initialIsWooPayEnabled &&
			! isWooPayEnabled &&
			! isWooPayDisableFeedbackThrottled( localWooPayLastDisableDate )
		) {
			setWooPayDisableFeedbackOpen( true );
			setLocalWooPayLastDisableDate(
				new Date().toISOString().slice( 0, 10 )
			);
		}

		if ( hasWooPayEnabledSetting ) {
			setInitialIsWooPayEnabled( isWooPayEnabled );
		}
	};

	return (
		<div className="woopayments-settings-save-bar">
			<Button
				variant="primary"
				isBusy={ isSaving }
				disabled={ isDisabled }
				accessibleWhenDisabled
				onClick={ saveOnClick }
			>
				{ __( 'Save changes', 'woocommerce' ) }
			</Button>
			<p
				aria-live="polite"
				className="woopayments-settings-save-bar__status"
			>
				{ isDirty
					? __( 'You have unsaved changes.', 'woocommerce' )
					: statusMessage ||
					  __( 'Settings are up to date.', 'woocommerce' ) }
			</p>
			{ isWooPayDisableFeedbackOpen && (
				<WooPayDisableFeedback
					onRequestClose={ () =>
						setWooPayDisableFeedbackOpen( false )
					}
				/>
			) }
		</div>
	);
};

export const WooPaymentsSettingsPage = () => {
	getWooPaymentsSettingsBootstrap();

	const { isLoading, isSaving } = useSettings();
	const [ isTransactionInputsValid, setTransactionInputsValid ] =
		useState( true );
	const [ isNotificationEmailValid, setNotificationEmailValid ] =
		useState( true );
	const settings = asSettingsRecord( useGetSettings() );
	const [ enabledPaymentMethodIds ] =
		useEnabledPaymentMethodIds() as StringArraySetting;
	const [ isCardPresentEligible ] =
		useCardPresentEligible() as BooleanSetting;
	const [ vatDetailsModalState, setVatDetailsModalState ] =
		useState< VatDetailsModalState >( {
			isOpen: false,
			country: '',
			hasSubmittedVatData: false,
		} );
	const hasHandledVatDetailsDeepLink = useRef( false );
	const hasSettings = Object.keys( settings ).length > 0;

	useEffect( () => {
		if (
			hasHandledVatDetailsDeepLink.current ||
			! isVatDetailsModalDeepLinkActive()
		) {
			return;
		}

		hasHandledVatDetailsDeepLink.current = true;
		let isMounted = true;

		getWooPaymentsAccountSettings()
			.then( ( response ) => {
				if ( ! isMounted ) {
					return;
				}

				const documents = response.documents;
				if ( ! documents?.enabled ) {
					dispatch( 'core/notices' ).createErrorNotice(
						__(
							'Tax details collection is not available for your account.',
							'woocommerce'
						)
					);
					return;
				}

				if ( documents.has_submitted_vat_data ) {
					dispatch( 'core/notices' ).createInfoNotice(
						__(
							'Tax details are already submitted.',
							'woocommerce'
						)
					);
					return;
				}

				setVatDetailsModalState( {
					isOpen: true,
					country: documents.country || '',
					hasSubmittedVatData: false,
				} );
			} )
			.catch( () => {
				if ( ! isMounted ) {
					return;
				}

				dispatch( 'core/notices' ).createErrorNotice(
					__(
						'Tax details collection is not available for your account.',
						'woocommerce'
					)
				);
			} );

		return () => {
			isMounted = false;
		};
	}, [] );

	const closeVatDetailsModal = () => {
		setVatDetailsModalState( ( current ) => ( {
			...current,
			isOpen: false,
		} ) );
		removeVatDetailsModalQueryParam();
	};

	const completeVatDetailsModal = ( details: WooPaymentsVatDetails ) => {
		setVatDetailsModalState( ( current ) => ( {
			...current,
			isOpen: false,
			hasSubmittedVatData: true,
		} ) );
		dispatch( 'core/notices' ).createInfoNotice(
			__( 'Tax details updated', 'woocommerce' )
		);
		removeVatDetailsModalQueryParam();

		return details;
	};

	return (
		<section
			className="woopayments-settings-page"
			aria-labelledby={ HEADING_ID }
		>
			<header className="woopayments-settings-page__header">
				<h1 id={ HEADING_ID }>
					{ sprintf(
						/* translators: %s: Payment provider name. */
						__( '%s settings', 'woocommerce' ),
						PROVIDER_NAME
					) }
				</h1>
				<p>
					{ __(
						'Manage WooPayments payment methods, express checkouts, payouts, notifications, fraud protection, and advanced settings.',
						'woocommerce'
					) }
				</p>
			</header>

			{ isLoading && ! hasSettings ? (
				<p
					className="woopayments-settings-page__loading"
					aria-live="polite"
				>
					<Spinner />
					{ __( 'Loading WooPayments settings…', 'woocommerce' ) }
				</p>
			) : (
				<SettingsBusyState isBusy={ isSaving }>
					<SpotlightPromotion />
					<GeneralSettingsSection />
					<PaymentMethodsSettingsSection />
					<BuyNowPayLaterSettingsSection />
					<ExpressCheckoutSettingsSection />
					<TransactionsSettingsSection
						onValidationChange={ setTransactionInputsValid }
					/>
					<PayoutsSettingsSection />
					<NotificationsSettingsSection
						onValidationChange={ setNotificationEmailValid }
					/>
					<FraudProtectionSettingsSection />
					<AdvancedSettingsSection />
					{ isCardPresentEligible && (
						<p className="woopayments-settings-muted">
							{ sprintf(
								/* translators: %d: Number of enabled payment methods. */
								__(
									'%d payment methods are currently enabled, including in-person eligible methods.',
									'woocommerce'
								),
								enabledPaymentMethodIds.length
							) }
						</p>
					) }
					<SaveSettingsSection
						disabled={
							isSaving ||
							! isTransactionInputsValid ||
							! isNotificationEmailValid
						}
					/>
					{ vatDetailsModalState.isOpen &&
						! vatDetailsModalState.hasSubmittedVatData && (
							<Suspense fallback={ null }>
								<WooPaymentsVatModal
									country={ vatDetailsModalState.country }
									onClose={ closeVatDetailsModal }
									onCompleted={ completeVatDetailsModal }
								/>
							</Suspense>
						) }
				</SettingsBusyState>
			) }
		</section>
	);
};

export default WooPaymentsSettingsPage;
