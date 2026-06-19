/**
 * External dependencies
 */
import {
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
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getSettingsPaymentsProviderRouteUrl } from '../admin/utils';
import { getWooPaymentsSettingsBootstrap } from './bootstrap';
import {
	saveOption,
	updateDismissedDuplicatePaymentMethodNotices,
} from './data/actions';
import { FraudProtectionSettings } from './fraud-protection';
import { WooPaymentsPaymentMethodsList } from './payment-methods-list';
import {
	useAccountBusinessSupportEmail,
	useAccountBusinessSupportPhone,
	useAccountCommunicationsEmail,
	useAccountDomesticCurrency,
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
import './data/store';
import './style.scss';

const PROVIDER_NAME = 'WooPayments';
const HEADING_ID = 'woopayments-settings-page-heading';
const ACCOUNT_STATEMENT_MAX_LENGTH = 22;
const ACCOUNT_STATEMENT_MAX_LENGTH_KANJI = 17;
const ACCOUNT_STATEMENT_MAX_LENGTH_KANA = 22;
type SettingsRecord = Record< string, unknown >;
type StringSetter = ( value: string ) => void;
type BooleanSetter = ( value: boolean ) => void;
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
	description: string;
	notice: string;
};

const BNPL_METHOD_IDS = [ 'affirm', 'afterpay_clearpay', 'klarna' ];
const EXPRESS_METHOD_IDS = [ 'payment_request', 'woopay', 'amazon_pay' ];
const STANDARD_PAYMENT_METHOD_EXCLUDED_IDS = [
	...EXPRESS_METHOD_IDS,
	'apple_pay',
	'google_pay',
	'link',
];
const asString = ( value: unknown, fallback = '' ) =>
	typeof value === 'string' ? value : fallback;

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

const isCustomizableExpressCheckoutMethod = (
	methodId: ExpressCheckoutOverviewMethod
): methodId is CustomizableExpressCheckoutMethod => methodId !== 'link';

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
	title,
	children,
}: {
	title: string;
	children: React.ReactNode;
} ) => (
	<div className="woopayments-settings-field-group">
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
					duplicatedPaymentMethodIds={ duplicatedPaymentMethodIds }
					dismissedDuplicatePaymentMethodNotices={
						dismissedDuplicatePaymentMethodNotices
					}
					isManualCaptureEnabled={ Boolean( isManualCaptureEnabled ) }
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
					duplicatedPaymentMethodIds={ duplicatedPaymentMethodIds }
					dismissedDuplicatePaymentMethodNotices={
						dismissedDuplicatePaymentMethodNotices
					}
					isManualCaptureEnabled={ Boolean( isManualCaptureEnabled ) }
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
	const [ isPaymentRequestEnabled, setIsPaymentRequestEnabled ] =
		usePaymentRequestEnabledSettings() as BooleanSetting;
	const [ isWooPayEnabled, setIsWooPayEnabled ] =
		useWooPayEnabledSettings() as BooleanSetting;
	const [ isLinkEnabled, setIsLinkEnabled ] = useLinkEnabledSettings() as [
		boolean,
		( isEnabled: boolean ) => void,
		boolean
	];
	const [ enabledMethodIds ] =
		useEnabledPaymentMethodIds() as StringArraySetting;
	const [ isAmazonPayEnabled, setIsAmazonPayEnabled ] =
		useAmazonPayEnabledSettings() as BooleanSetting;
	const availablePaymentMethodIds = asStringArray(
		useGetAvailablePaymentMethodIds()
	);
	const isLinkAvailable =
		enabledMethodIds.includes( 'card' ) &&
		availablePaymentMethodIds.includes( 'link' );
	const isAmazonPayAvailable =
		availablePaymentMethodIds.includes( 'amazon_pay' );
	const showWooPayIncompatibilityNotice = Boolean(
		useWooPayShowIncompatibilityNotice()
	);
	let wooPayNotice = '';

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

	const expressRows: ExpressCheckoutOverviewRow[] = [
		{
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
				: __(
						'Boost conversion and customer loyalty by offering a single click, secure way to pay. In order to use WooPay, you must agree to our WooCommerce Terms of Service and Privacy Policy.',
						'woocommerce'
				  ),
			notice: wooPayNotice,
		},
		{
			id: 'payment_request',
			title: __( 'Apple Pay / Google Pay', 'woocommerce' ),
			checked: isPaymentRequestEnabled,
			disabled: false,
			onChange: setIsPaymentRequestEnabled,
			description: __(
				'Allow customers to make payments using Apple Pay and Google Pay.',
				'woocommerce'
			),
			notice: '',
		},
	];

	if ( isLinkAvailable ) {
		expressRows.push( {
			id: 'link',
			title: __( 'Link by Stripe', 'woocommerce' ),
			checked: isLinkEnabled,
			disabled: isWooPayEnabled,
			onChange: setIsLinkEnabled,
			description: __(
				'Let customers use Link for faster checkout.',
				'woocommerce'
			),
			notice: isWooPayEnabled
				? __(
						'To enable Link by Stripe, you must first disable WooPay.',
						'woocommerce'
				  )
				: '',
		} );
	}

	if ( isAmazonPayAvailable ) {
		expressRows.push( {
			id: 'amazon_pay',
			title: __( 'Amazon Pay', 'woocommerce' ),
			checked: isAmazonPayEnabled,
			disabled: false,
			onChange: setIsAmazonPayEnabled,
			description: __(
				'Allow customers to make payments using Amazon Pay.',
				'woocommerce'
			),
			notice: '',
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
										status="warning"
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
						</div>
					</li>
				) ) }
			</ul>
		</SettingsSection>
	);
};

const TransactionsSettingsSection = () => {
	const settings = asSettingsRecord( useGetSettings() );
	const [ isSavedCardsEnabled, setIsSavedCardsEnabled ] =
		useSavedCards() as BooleanSetting;
	const [ isManualCaptureEnabled, setIsManualCaptureEnabled ] =
		useManualCapture() as BooleanSetting;
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
	const savingError = asSettingsRecord( useGetSavingError() );
	const serverErrorMessage = asString( savingError.server_error );
	const accountCountry = asString( settings.account_country );

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
						'Customers can pay with cards they saved during earlier purchases.',
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
					onChange={ ( value ) =>
						setIsManualCaptureEnabled( Boolean( value ) )
					}
					__nextHasNoMarginBottom
				/>
			</FieldGroup>
			<FieldGroup title={ __( 'Customer statements', 'woocommerce' ) }>
				{ serverErrorMessage && (
					<Notice status="error" isDismissible={ false }>
						{ serverErrorMessage }
					</Notice>
				) }
				<TextControl
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
				<div className="woopayments-settings-control-grid">
					<TextControl
						label={ __( 'Support email', 'woocommerce' ) }
						type="email"
						value={ supportEmail }
						onChange={ setSupportEmail }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<TextControl
						label={ __( 'Support phone', 'woocommerce' ) }
						type="tel"
						value={ supportPhone }
						onChange={ setSupportPhone }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
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
	const domesticCurrency = asString( useAccountDomesticCurrency() );
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
							'Payout scheduling becomes available after the standard waiting period for new accounts is complete.',
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
										label: String( index + 1 ),
										value: String( index + 1 ),
									} )
								),
								{
									label: __(
										'Last day of the month',
										'woocommerce'
									),
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
			<p className="woopayments-settings-muted">
				{ sprintf(
					/* translators: %s: Currency code. */
					__( 'Payout currency: %s', 'woocommerce' ),
					domesticCurrency || '-'
				) }
			</p>
		</SettingsSection>
	);
};

const NotificationsSettingsSection = () => {
	const [ email, setEmail ] =
		useAccountCommunicationsEmail() as StringSetting;

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
			<TextControl
				label={ __( 'Notification email', 'woocommerce' ) }
				type="email"
				value={ email }
				onChange={ setEmail }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
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
				onChange={ ( value ) =>
					setIsMultiCurrencyEnabled( Boolean( value ) )
				}
				__nextHasNoMarginBottom
			/>
			<CheckboxControl
				checked={ isWCPaySubscriptionsEnabled }
				disabled={ ! isWCPaySubscriptionsEligible }
				help={
					isWCPaySubscriptionsEligible
						? __(
								'Process subscription renewals with WooPayments.',
								'woocommerce'
						  )
						: __(
								'WooPayments subscriptions are not available for this account.',
								'woocommerce'
						  )
				}
				label={ __(
					'Enable WooPayments subscriptions',
					'woocommerce'
				) }
				onChange={ ( value ) =>
					setIsWCPaySubscriptionsEnabled( Boolean( value ) )
				}
				__nextHasNoMarginBottom
			/>
			<CheckboxControl
				checked={ isDebugLogEnabled }
				label={ __( 'Enable debug logging', 'woocommerce' ) }
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
	const [ statusMessage, setStatusMessage ] = useState( '' );
	const isDisabled = isSaving || isLoading || disabled || ! isDirty;

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
		</div>
	);
};

export const WooPaymentsSettingsPage = () => {
	getWooPaymentsSettingsBootstrap();

	const { isLoading, isSaving } = useSettings();
	const settings = asSettingsRecord( useGetSettings() );
	const [ enabledPaymentMethodIds ] =
		useEnabledPaymentMethodIds() as StringArraySetting;
	const [ isCardPresentEligible ] =
		useCardPresentEligible() as BooleanSetting;
	const hasSettings = Object.keys( settings ).length > 0;

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
				<>
					<GeneralSettingsSection />
					<PaymentMethodsSettingsSection />
					<BuyNowPayLaterSettingsSection />
					<ExpressCheckoutSettingsSection />
					<TransactionsSettingsSection />
					<PayoutsSettingsSection />
					<NotificationsSettingsSection />
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
					<SaveSettingsSection disabled={ isSaving } />
				</>
			) }
		</section>
	);
};

export default WooPaymentsSettingsPage;
