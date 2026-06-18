/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
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
import { dispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getWooPaymentsSettingsBootstrap } from './bootstrap';
import { WooPaymentsPaymentMethodsList } from './payment-methods-list';
import {
	useAccountBusinessSupportEmail,
	useAccountBusinessSupportPhone,
	useAccountCommunicationsEmail,
	useAccountDomesticCurrency,
	useAccountStatementDescriptor,
	useAccountStatementDescriptorKana,
	useAccountStatementDescriptorKanji,
	useAdvancedFraudProtectionSettings,
	useAmazonPayEnabledSettings,
	useAmazonPayLocations,
	useCardPresentEligible,
	useCompletedWaitingPeriod,
	useCurrentProtectionLevel,
	useDebugLog,
	useDepositDelayDays,
	useDepositRestrictions,
	useDepositScheduleInterval,
	useDepositScheduleMonthlyAnchor,
	useDepositScheduleWeeklyAnchor,
	useDepositStatus,
	useDevMode,
	useEnabledPaymentMethodIds,
	useExpressCheckoutInPaymentMethodsEnabledSettings,
	useGetAvailablePaymentMethodIds,
	useGetPaymentMethodStatuses,
	useGetSavingError,
	useGetSettings,
	useIsWCPayEnabled,
	useLinkEnabledSettings,
	useManualCapture,
	useMultiCurrency,
	usePaymentRequestButtonBorderRadius,
	usePaymentRequestButtonSize,
	usePaymentRequestButtonTheme,
	usePaymentRequestButtonType,
	usePaymentRequestEnabledSettings,
	usePaymentRequestLocations,
	useSavedCards,
	useSelectedPaymentMethod as getSelectedPaymentMethodSetting,
	useSettings,
	useTestMode,
	useTestModeOnboarding,
	useUnselectedPaymentMethod as getUnselectedPaymentMethodSetting,
	useWooPayCustomMessage,
	useWooPayEnabledSettings,
	useWooPayGlobalThemeSupportEnabledSettings,
	useWooPayLocations,
	useWooPayShowIncompatibilityNotice,
	useWooPayStoreLogo,
	useWCPaySubscriptions,
} from './data/hooks';
import './data/store';
import './style.scss';

const PROVIDER_NAME = 'WooPayments';
const HEADING_ID = 'woopayments-settings-page-heading';
const ACCOUNT_STATEMENT_MAX_LENGTH = 22;
const ACCOUNT_STATEMENT_MAX_LENGTH_KANJI = 17;
const ACCOUNT_STATEMENT_MAX_LENGTH_KANA = 22;
const MAX_LOGO_FILE_SIZE = 510000;

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
type FraudProtectionLevel = 'basic' | 'standard' | 'advanced';
type PaymentMethodStatus = {
	status?: string;
	requirements?: unknown[];
};
type PaymentRequestButtonType = 'default' | 'buy' | 'donate' | 'book';
type PaymentRequestButtonSize = 'small' | 'medium' | 'large';
type PaymentRequestButtonTheme = 'dark' | 'light' | 'light-outline';

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

const asFraudProtectionLevel = ( value: string ): FraudProtectionLevel =>
	[ 'basic', 'standard', 'advanced' ].includes( value )
		? ( value as FraudProtectionLevel )
		: 'basic';

const asSettingsRecord = ( value: unknown ): SettingsRecord =>
	value && typeof value === 'object' ? ( value as SettingsRecord ) : {};

const getLiteralValue = < T extends string >(
	value: string,
	allowedValues: readonly T[],
	fallback: T
): T => ( allowedValues.includes( value as T ) ? ( value as T ) : fallback );

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
					isManualCaptureEnabled={ Boolean( isManualCaptureEnabled ) }
					accountCountry={ accountCountry }
					onEnable={ addPaymentMethod }
					onDisable={ removePaymentMethod }
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
					isManualCaptureEnabled={ Boolean( isManualCaptureEnabled ) }
					accountCountry={ accountCountry }
					onEnable={ addPaymentMethod }
					onDisable={ removePaymentMethod }
				/>
			</FieldGroup>
		</SettingsSection>
	);
};

const LocationCheckboxes = ( {
	legend,
	enabledLocations,
	onChange,
}: {
	legend: string;
	enabledLocations: string[];
	onChange: (
		location: 'product' | 'cart' | 'checkout',
		value: boolean
	) => void;
} ) => (
	<fieldset className="woopayments-settings-location-fieldset">
		<legend className="screen-reader-text">{ legend }</legend>
		<div className="woopayments-settings-location-grid">
			{ (
				[
					[ 'product', __( 'Product pages', 'woocommerce' ) ],
					[ 'cart', __( 'Cart', 'woocommerce' ) ],
					[ 'checkout', __( 'Checkout', 'woocommerce' ) ],
				] as const
			 ).map( ( [ location, label ] ) => (
				<CheckboxControl
					key={ location }
					checked={ enabledLocations.includes( location ) }
					label={ label }
					onChange={ ( value ) =>
						onChange( location, Boolean( value ) )
					}
					__nextHasNoMarginBottom
				/>
			) ) }
		</div>
	</fieldset>
);

const ExpressCheckoutSettingsSection = () => {
	const [ isPaymentRequestEnabled, setIsPaymentRequestEnabled ] =
		usePaymentRequestEnabledSettings() as BooleanSetting;
	const [
		isExpressCheckoutInPaymentMethodsEnabled,
		setIsExpressCheckoutInPaymentMethodsEnabled,
	] = useExpressCheckoutInPaymentMethodsEnabledSettings() as BooleanSetting;
	const [ isLinkEnabled, setIsLinkEnabled, isWooPayEnabled ] =
		useLinkEnabledSettings() as [
			boolean,
			( isEnabled: boolean ) => void,
			boolean
		];
	const [ isAmazonPayEnabled, setIsAmazonPayEnabled ] =
		useAmazonPayEnabledSettings() as BooleanSetting;
	const availablePaymentMethodIds = asStringArray(
		useGetAvailablePaymentMethodIds()
	);
	const isLinkAvailable = availablePaymentMethodIds.includes( 'link' );
	const isAmazonPayAvailable =
		availablePaymentMethodIds.includes( 'amazon_pay' );
	const [ paymentRequestButtonType, setPaymentRequestButtonType ] =
		usePaymentRequestButtonType() as StringSetting;
	const [ paymentRequestButtonSize, setPaymentRequestButtonSize ] =
		usePaymentRequestButtonSize() as StringSetting;
	const [ paymentRequestButtonTheme, setPaymentRequestButtonTheme ] =
		usePaymentRequestButtonTheme() as StringSetting;
	const [
		paymentRequestButtonBorderRadius,
		setPaymentRequestButtonBorderRadius,
	] = usePaymentRequestButtonBorderRadius() as [
		number | string,
		( value: number ) => void
	];
	const [ paymentRequestLocations, setPaymentRequestLocation ] =
		usePaymentRequestLocations() as [
			string[],
			(
				location: 'product' | 'cart' | 'checkout',
				value: boolean
			) => void
		];
	const [ amazonPayLocations, setAmazonPayLocation ] =
		useAmazonPayLocations() as [
			string[],
			(
				location: 'product' | 'cart' | 'checkout',
				value: boolean
			) => void
		];
	const buttonType = getLiteralValue< PaymentRequestButtonType >(
		paymentRequestButtonType,
		[ 'default', 'buy', 'donate' ],
		'default'
	);
	const buttonSize = getLiteralValue< PaymentRequestButtonSize >(
		paymentRequestButtonSize,
		[ 'small', 'medium', 'large' ],
		'medium'
	);
	const buttonTheme = getLiteralValue< PaymentRequestButtonTheme >(
		paymentRequestButtonTheme,
		[ 'dark', 'light', 'light-outline' ],
		'dark'
	);

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
			<FieldGroup
				title={ __( 'Apple Pay and Google Pay', 'woocommerce' ) }
			>
				<CheckboxControl
					checked={ isPaymentRequestEnabled }
					label={ __(
						'Enable Apple Pay and Google Pay',
						'woocommerce'
					) }
					onChange={ ( value ) =>
						setIsPaymentRequestEnabled( Boolean( value ) )
					}
					__nextHasNoMarginBottom
				/>
				<CheckboxControl
					checked={ isExpressCheckoutInPaymentMethodsEnabled }
					label={ __(
						'Show express checkout in the payment methods list',
						'woocommerce'
					) }
					onChange={ ( value ) =>
						setIsExpressCheckoutInPaymentMethodsEnabled(
							Boolean( value )
						)
					}
					__nextHasNoMarginBottom
				/>
				<LocationCheckboxes
					legend={ __(
						'Apple Pay and Google Pay locations',
						'woocommerce'
					) }
					enabledLocations={ paymentRequestLocations }
					onChange={ setPaymentRequestLocation }
				/>
				<div className="woopayments-settings-control-grid">
					<SelectControl
						label={ __( 'Button type', 'woocommerce' ) }
						value={ buttonType }
						options={ [
							{
								label: __( 'Default', 'woocommerce' ),
								value: 'default',
							},
							{
								label: __( 'Buy', 'woocommerce' ),
								value: 'buy',
							},
							{
								label: __( 'Donate', 'woocommerce' ),
								value: 'donate',
							},
							{
								label: __( 'Book', 'woocommerce' ),
								value: 'book',
							},
						] }
						onChange={ setPaymentRequestButtonType }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<SelectControl
						label={ __( 'Button size', 'woocommerce' ) }
						value={ buttonSize }
						options={ [
							{
								label: __( 'Small', 'woocommerce' ),
								value: 'small',
							},
							{
								label: __( 'Medium', 'woocommerce' ),
								value: 'medium',
							},
							{
								label: __( 'Large', 'woocommerce' ),
								value: 'large',
							},
						] }
						onChange={ setPaymentRequestButtonSize }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<SelectControl
						label={ __( 'Button theme', 'woocommerce' ) }
						value={ buttonTheme }
						options={ [
							{
								label: __( 'Dark', 'woocommerce' ),
								value: 'dark',
							},
							{
								label: __( 'Light', 'woocommerce' ),
								value: 'light',
							},
							{
								label: __( 'Light outline', 'woocommerce' ),
								value: 'light-outline',
							},
						] }
						onChange={ setPaymentRequestButtonTheme }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<TextControl
						label={ __( 'Button border radius', 'woocommerce' ) }
						type="number"
						value={ String( paymentRequestButtonBorderRadius ) }
						onChange={ ( value ) =>
							setPaymentRequestButtonBorderRadius(
								parseInt( value || '0', 10 )
							)
						}
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</div>
			</FieldGroup>
			{ isLinkAvailable && (
				<FieldGroup title={ __( 'Link by Stripe', 'woocommerce' ) }>
					<CheckboxControl
						checked={ isLinkEnabled }
						disabled={ isWooPayEnabled }
						label={ __( 'Enable Link by Stripe', 'woocommerce' ) }
						help={
							isWooPayEnabled
								? __(
										'Disable WooPay before enabling Link by Stripe.',
										'woocommerce'
								  )
								: __(
										'Let customers use Link for faster checkout.',
										'woocommerce'
								  )
						}
						onChange={ ( value ) =>
							setIsLinkEnabled( Boolean( value ) )
						}
						__nextHasNoMarginBottom
					/>
				</FieldGroup>
			) }
			{ isAmazonPayAvailable && (
				<FieldGroup title={ __( 'Amazon Pay', 'woocommerce' ) }>
					<CheckboxControl
						checked={ isAmazonPayEnabled }
						label={ __( 'Enable Amazon Pay', 'woocommerce' ) }
						onChange={ ( value ) =>
							setIsAmazonPayEnabled( Boolean( value ) )
						}
						__nextHasNoMarginBottom
					/>
					<LocationCheckboxes
						legend={ __( 'Amazon Pay locations', 'woocommerce' ) }
						enabledLocations={ amazonPayLocations }
						onChange={ setAmazonPayLocation }
					/>
				</FieldGroup>
			) }
		</SettingsSection>
	);
};

const WooPayLogoUpload = ( {
	logoId,
	setLogoId,
}: {
	logoId: string;
	setLogoId: StringSetter;
} ) => {
	const [ isUploading, setIsUploading ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );
	const [ uploadedFileName, setUploadedFileName ] = useState( '' );
	const [ uploadStatusMessage, setUploadStatusMessage ] = useState( '' );

	const handleUpload = async (
		event: React.ChangeEvent< HTMLInputElement >
	) => {
		const file = event.target.files?.[ 0 ];

		if ( ! file ) {
			return;
		}

		if ( file.size > MAX_LOGO_FILE_SIZE ) {
			const message = __(
				'The selected logo exceeds the maximum file size.',
				'woocommerce'
			);
			setError( message );
			dispatch( 'core/notices' ).createErrorNotice( message );
			event.target.value = '';
			return;
		}

		const body = new FormData();
		body.append( 'file', file );
		body.append( 'purpose', 'business_logo' );

		setIsUploading( true );
		setError( null );
		setUploadStatusMessage( '' );

		try {
			const uploadedFile = ( await apiFetch( {
				path: '/wc/v3/payments/file',
				method: 'post',
				body,
			} ) ) as { id?: string };

			setLogoId( uploadedFile.id || '' );
			setUploadedFileName( file.name );
			setUploadStatusMessage(
				sprintf(
					/* translators: %s: Uploaded file name. */
					__( 'Logo uploaded: %s', 'woocommerce' ),
					file.name
				)
			);
		} catch ( uploadError ) {
			const message =
				uploadError instanceof Error && uploadError.message
					? uploadError.message
					: __( 'Error uploading logo.', 'woocommerce' );

			setError( message );
			setLogoId( '' );
			setUploadedFileName( '' );
			setUploadStatusMessage( '' );
			dispatch( 'core/notices' ).createErrorNotice( message );
		} finally {
			setIsUploading( false );
			event.target.value = '';
		}
	};

	return (
		<div className="woopayments-settings-logo-upload">
			<label htmlFor="woopayments-settings-woopay-logo">
				{ __( 'Custom logo', 'woocommerce' ) }
			</label>
			<input
				id="woopayments-settings-woopay-logo"
				type="file"
				accept="image/png,image/jpeg,image/gif,image/webp"
				disabled={ isUploading }
				onChange={ handleUpload }
			/>
			<p className="woopayments-settings-muted">
				{ __(
					'Upload a custom logo for the WooPay checkout experience.',
					'woocommerce'
				) }
			</p>
			{ isUploading && (
				<p
					aria-live="polite"
					className="woopayments-settings-inline-status"
				>
					<Spinner />
					{ __( 'Uploading logo…', 'woocommerce' ) }
				</p>
			) }
			{ logoId && ! isUploading && (
				<p
					aria-live="polite"
					className="woopayments-settings-inline-status"
				>
					{ sprintf(
						/* translators: 1: Uploaded file name, 2: Uploaded file ID. */
						__( 'Current logo file: %1$s (%2$s)', 'woocommerce' ),
						uploadedFileName ||
							__( 'Uploaded file', 'woocommerce' ),
						logoId
					) }
					<Button
						variant="link"
						onClick={ () => {
							setLogoId( '' );
							setUploadedFileName( '' );
							setUploadStatusMessage( '' );
						} }
					>
						{ __( 'Remove', 'woocommerce' ) }
					</Button>
				</p>
			) }
			{ uploadStatusMessage && ! isUploading && (
				<span className="screen-reader-text" aria-live="polite">
					{ uploadStatusMessage }
				</span>
			) }
			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }
		</div>
	);
};

const WooPaySettingsSection = () => {
	const [ isWooPayEnabled, setIsWooPayEnabled ] =
		useWooPayEnabledSettings() as BooleanSetting;
	const [
		isWooPayGlobalThemeSupportEnabled,
		setIsWooPayGlobalThemeSupportEnabled,
	] = useWooPayGlobalThemeSupportEnabledSettings() as BooleanSetting;
	const [ wooPayCustomMessage, setWooPayCustomMessage ] =
		useWooPayCustomMessage() as StringSetting;
	const [ wooPayStoreLogo, setWooPayStoreLogo ] =
		useWooPayStoreLogo() as StringSetting;
	const [ wooPayLocations, setWooPayLocation ] = useWooPayLocations() as [
		string[],
		( location: 'product' | 'cart' | 'checkout', value: boolean ) => void
	];
	const showIncompatibilityNotice = Boolean(
		useWooPayShowIncompatibilityNotice()
	);

	return (
		<SettingsSection
			id="woopay"
			title={ __( 'WooPay', 'woocommerce' ) }
			description={
				<p>
					{ __(
						'Configure WooPay accelerated checkout and the content shown to returning shoppers.',
						'woocommerce'
					) }
				</p>
			}
		>
			{ showIncompatibilityNotice && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'WooPay is not available for one or more active checkout settings.',
						'woocommerce'
					) }
				</Notice>
			) }
			<CheckboxControl
				checked={ isWooPayEnabled }
				label={ __( 'Enable WooPay', 'woocommerce' ) }
				onChange={ ( value ) => setIsWooPayEnabled( Boolean( value ) ) }
				__nextHasNoMarginBottom
			/>
			<LocationCheckboxes
				legend={ __( 'WooPay locations', 'woocommerce' ) }
				enabledLocations={ wooPayLocations }
				onChange={ setWooPayLocation }
			/>
			<CheckboxControl
				checked={ isWooPayGlobalThemeSupportEnabled }
				label={ __(
					'Use your store theme for WooPay appearance',
					'woocommerce'
				) }
				onChange={ ( value ) =>
					setIsWooPayGlobalThemeSupportEnabled( Boolean( value ) )
				}
				__nextHasNoMarginBottom
			/>
			<TextControl
				label={ __( 'Custom message', 'woocommerce' ) }
				value={ wooPayCustomMessage }
				onChange={ setWooPayCustomMessage }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<WooPayLogoUpload
				logoId={ wooPayStoreLogo }
				setLogoId={ setWooPayStoreLogo }
			/>
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
	const [ protectionLevel, setProtectionLevel ] =
		useCurrentProtectionLevel() as StringSetting;
	const [ advancedFraudProtectionSettings ] =
		useAdvancedFraudProtectionSettings() as [
			unknown[],
			( value: unknown[] ) => void
		];
	const advancedRuleCount = Array.isArray( advancedFraudProtectionSettings )
		? advancedFraudProtectionSettings.length
		: 0;

	return (
		<SettingsSection
			id="fraud-protection"
			title={ __( 'Fraud protection', 'woocommerce' ) }
			description={
				<p>
					{ __(
						'Choose the fraud protection level used to screen card transactions.',
						'woocommerce'
					) }
				</p>
			}
		>
			<SelectControl
				label={ __( 'Protection level', 'woocommerce' ) }
				value={ asFraudProtectionLevel( protectionLevel ) }
				options={ [
					{ label: __( 'Basic', 'woocommerce' ), value: 'basic' },
					{
						label: __( 'Standard', 'woocommerce' ),
						value: 'standard',
					},
					{
						label: __( 'Advanced', 'woocommerce' ),
						value: 'advanced',
					},
				] }
				onChange={ setProtectionLevel }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<p className="woopayments-settings-muted">
				{ sprintf(
					/* translators: %d: Number of advanced fraud protection rules. */
					__(
						'%d advanced fraud protection rules are preserved by WooPayments.',
						'woocommerce'
					),
					advancedRuleCount
				) }
			</p>
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

	const saveOnClick = async () => {
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
				disabled={ isSaving || isLoading || disabled || ! isDirty }
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
					<WooPaySettingsSection />
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
