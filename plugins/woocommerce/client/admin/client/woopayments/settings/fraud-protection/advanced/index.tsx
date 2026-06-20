/**
 * External dependencies
 */
import {
	Button,
	Card,
	CheckboxControl,
	ExternalLink,
	Notice,
	RadioControl,
	TextControl,
} from '@wordpress/components';
import { dispatch } from '@wordpress/data';
import {
	createInterpolateElement,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { __, sprintf } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import type { KeyboardEvent, ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { getSettingsPaymentsProviderRouteUrl } from '../../../admin/utils';
import {
	useAdvancedFraudProtectionSettings,
	useCurrentProtectionLevel,
	useGetSettings,
	useSettings,
} from '../../data/hooks';
import { ProtectionLevel, Rules } from './constants';
import {
	FraudPreventionSettings,
	isOrderItemsThresholdSetting,
	isPurchasePriceThresholdSetting,
	ProtectionSettingsUI,
} from './types';
import {
	isSellingToAvsSupportedLocations,
	readRuleset,
	writeRuleset,
} from './utils';
import './style.scss';

type SettingsRecord = Record< string, unknown >;
type StringSetting = [ string, ( value: string ) => void ];
type AdvancedFraudProtectionSetting = [ unknown, ( value: unknown[] ) => void ];
type WindowWithWooSettings = Window & {
	wcSettings?: {
		adminUrl?: string;
		countries?: Record< string, string >;
	};
};

const asSettingsRecord = ( value: unknown ): SettingsRecord =>
	value && typeof value === 'object' ? ( value as SettingsRecord ) : {};

const asString = ( value: unknown, fallback = '' ) =>
	typeof value === 'string' ? value : fallback;

const asStringArray = ( value: unknown ) =>
	Array.isArray( value )
		? value.filter( ( item ): item is string => typeof item === 'string' )
		: [];

const asBoolean = ( value: unknown, fallback = false ) =>
	typeof value === 'boolean' ? value : fallback;

const CVC_VERIFICATION_DOC_URL =
	'https://woocommerce.com/document/woopayments/fraud-and-disputes/fraud-protection/#advanced-configuration';
const IP_ADDRESS_DOC_URL = 'https://simple.wikipedia.org/wiki/IP_address';
const COMMON_CURRENCY_SYMBOLS: Record< string, string > = {
	AUD: '$',
	CAD: '$',
	EUR: '€',
	GBP: '£',
	JPY: '¥',
	NZD: '$',
	USD: '$',
};

const ADVANCED_RULE_CARD_VIEW_EVENTS: Record< string, string > = {
	'avs-mismatch-card':
		'wcpay_fraud_protection_advanced_settings_card_avs_mismatch_viewed',
	'cvc-verification-card':
		'wcpay_fraud_protection_advanced_settings_card_cvc_verification_viewed',
	'international-ip-address-card':
		'wcpay_fraud_protection_advanced_settings_card_international_ip_address_card_viewed',
	'ip-address-mismatch-card':
		'wcpay_fraud_protection_advanced_settings_card_ip_address_mismatch_card_viewed',
	'address-mismatch-card':
		'wcpay_fraud_protection_advanced_settings_card_address_mismatch_viewed',
	'purchase-price-threshold-card':
		'wcpay_fraud_protection_advanced_settings_card_price_threshold_viewed',
	'order-items-threshold-card':
		'wcpay_fraud_protection_advanced_settings_card_items_threshold_viewed',
};

const getFraudProtectionEnvironment = ( settings: SettingsRecord ) => {
	const fraudProtection = asSettingsRecord( settings.fraud_protection );
	const allowedCountries = asSettingsRecord(
		settings.fraud_protection_allowed_countries
	);

	return {
		storeCurrency:
			asString( settings.store_currency ) ||
			asString( settings.account_domestic_currency ) ||
			'USD',
		isReviewFeatureActive: asBoolean(
			settings.is_fraud_protection_review_feature_active,
			false
		),
		allowedCountriesType: asString( allowedCountries.type, 'all' ),
		settingCountries: asStringArray( allowedCountries.countries ),
		isAvsFailureDeclineEnabled: asBoolean(
			fraudProtection.decline_on_avs_failure,
			true
		),
		isCvcFailureDeclineEnabled: asBoolean(
			fraudProtection.decline_on_cvc_failure,
			true
		),
	};
};

type FraudProtectionEnvironment = ReturnType<
	typeof getFraudProtectionEnvironment
>;

const getWooCommerceGeneralSettingsUrl = () => {
	const adminUrl =
		( window as WindowWithWooSettings ).wcSettings?.adminUrl || '';
	const separator = adminUrl.endsWith( '/' ) || adminUrl === '' ? '' : '/';

	return `${ adminUrl }${ separator }admin.php?page=wc-settings&tab=general`;
};

const getCountryNames = ( countryCodes: string[] ) => {
	const countryNames =
		( window as WindowWithWooSettings ).wcSettings?.countries || {};

	return countryCodes.map( ( countryCode ) => {
		return decodeEntities( countryNames[ countryCode ] || countryCode );
	} );
};

const getCurrencySymbol = ( currency: string ) => {
	const normalizedCurrency = currency.toUpperCase();

	if ( COMMON_CURRENCY_SYMBOLS[ normalizedCurrency ] ) {
		return COMMON_CURRENCY_SYMBOLS[ normalizedCurrency ];
	}

	try {
		return (
			new Intl.NumberFormat( undefined, {
				style: 'currency',
				currency: normalizedCurrency,
			} )
				.formatToParts( 0 )
				.find( ( part ) => part.type === 'currency' )?.value ||
			normalizedCurrency
		);
	} catch {
		return normalizedCurrency;
	}
};

const hasEnabledRule = ( settings: ProtectionSettingsUI ) =>
	Object.values( settings ).some( ( setting ) => setting.enabled );

const getFloatValue = ( value: string | number | null ) => {
	const parsed = parseFloat( `${ value ?? '' }` );

	return isNaN( parsed ) ? 0 : parsed;
};

const getIntegerValue = ( value: string | number | null ) => {
	const parsed = parseInt( `${ value ?? '' }`, 10 );

	return isNaN( parsed ) ? 0 : parsed;
};

const validateThresholds = ( settings: ProtectionSettingsUI ) => {
	const orderItems = settings[ Rules.RULE_ORDER_ITEMS_THRESHOLD ];
	const purchasePrice = settings[ Rules.RULE_PURCHASE_PRICE_THRESHOLD ];

	if ( orderItems?.enabled && isOrderItemsThresholdSetting( orderItems ) ) {
		const minItems = getIntegerValue( orderItems.min_items );
		const maxItems = getIntegerValue( orderItems.max_items );

		if ( ! minItems && ! maxItems ) {
			return __(
				'An item range must be set for the "Order Item Threshold" filter.',
				'woocommerce'
			);
		}

		if ( minItems && maxItems && minItems > maxItems ) {
			return __(
				'Maximum item count must be greater than the minimum item count on the "Order Item Threshold" rule.',
				'woocommerce'
			);
		}
	}

	if (
		purchasePrice?.enabled &&
		isPurchasePriceThresholdSetting( purchasePrice )
	) {
		const minAmount = getFloatValue( purchasePrice.min_amount );
		const maxAmount = getFloatValue( purchasePrice.max_amount );

		if ( ! minAmount && ! maxAmount ) {
			return __(
				'A price range must be set for the "Purchase Price threshold" filter.',
				'woocommerce'
			);
		}

		if ( minAmount && maxAmount && minAmount > maxAmount ) {
			return __(
				'Maximum purchase price must be greater than the minimum purchase price.',
				'woocommerce'
			);
		}
	}

	return null;
};

const LoadingRuleCard = ( { id }: { id: string } ) => (
	<Card
		id={ id }
		className="woopayments-fraud-protection-rule woopayments-fraud-protection-rule--loading"
		aria-hidden="true"
	>
		<div className="woopayments-fraud-protection-rule__loading-title" />
		<div className="woopayments-fraud-protection-rule__loading-line" />
		<div className="woopayments-fraud-protection-rule__loading-line woopayments-fraud-protection-rule__loading-line--short" />
	</Card>
);

const AllowedCountriesNotice = ( {
	environment,
	settings,
}: {
	environment: FraudProtectionEnvironment;
	settings: ProtectionSettingsUI;
} ) => {
	if ( environment.allowedCountriesType === 'all' ) {
		return null;
	}

	const countryNames = getCountryNames( environment.settingCountries );

	if ( countryNames.length === 0 ) {
		return null;
	}

	const internationalIpSetting =
		settings[ Rules.RULE_INTERNATIONAL_IP_ADDRESS ];
	const shouldBlock =
		internationalIpSetting?.block ?? ! environment.isReviewFeatureActive;
	const countries = countryNames.join( ', ' );
	let message;

	if ( environment.allowedCountriesType === 'specific' ) {
		message = shouldBlock
			? __(
					'Orders from outside of the following countries will be blocked by the filter:',
					'woocommerce'
			  )
			: __(
					'Orders from outside of the following countries will be screened by the filter:',
					'woocommerce'
			  );
	} else {
		message = shouldBlock
			? __(
					'Orders from the following countries will be blocked by the filter:',
					'woocommerce'
			  )
			: __(
					'Orders from the following countries will be screened by the filter:',
					'woocommerce'
			  );
	}

	return (
		<Notice status="info" isDismissible={ false }>
			{ message } <strong>{ countries }</strong>
		</Notice>
	);
};

const getThresholdNotice = ( {
	maximum,
	minimum,
	rangeMessage,
	orderMessage,
}: {
	maximum: number;
	minimum: number;
	rangeMessage: string;
	orderMessage: string;
} ) => {
	if ( ! minimum && ! maximum ) {
		return (
			<Notice status="warning" isDismissible={ false }>
				{ rangeMessage }
			</Notice>
		);
	}

	if ( minimum && maximum && minimum > maximum ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ orderMessage }
			</Notice>
		);
	}

	return null;
};

const AmountThresholdInput = ( {
	currency,
	currencySymbol,
	id,
	label,
	onChange,
	value,
}: {
	currency: string;
	currencySymbol: string;
	id: string;
	label: string;
	onChange: ( value: string ) => void;
	value: string;
} ) => {
	const helpId = `${ id }__help`;
	const currencyDescriptionId = `${ id }__currency`;

	return (
		<div className="woopayments-fraud-protection-rule__amount-control">
			<label
				className="woopayments-fraud-protection-rule__threshold-label"
				htmlFor={ id }
			>
				{ label }
			</label>
			<div className="woopayments-fraud-protection-rule__currency-input">
				<span
					className="woopayments-fraud-protection-rule__currency-prefix"
					aria-hidden="true"
				>
					{ currencySymbol }
				</span>
				<input
					id={ id }
					className="components-text-control__input is-next-40px-default-size"
					type="number"
					value={ value }
					placeholder="0.00"
					aria-describedby={ `${ helpId } ${ currencyDescriptionId }` }
					onChange={ ( event ) => onChange( event.target.value ) }
				/>
			</div>
			<p id={ helpId } className="components-base-control__help">
				{ __( 'Leave blank for no limit', 'woocommerce' ) }
			</p>
			<span id={ currencyDescriptionId } className="screen-reader-text">
				{ sprintf(
					/* translators: %s: Store currency code, such as USD. */
					__( 'Amount is in %s.', 'woocommerce' ),
					currency.toUpperCase()
				) }
			</span>
		</div>
	);
};

const RuleDescription = ( { children }: { children: ReactNode } ) => (
	<div className="woopayments-fraud-protection-rule__description">
		<strong>
			{ __( 'How does this filter protect me?', 'woocommerce' ) }
		</strong>
		<p>{ children }</p>
	</div>
);

const RuleCard = ( {
	id,
	title,
	children,
}: {
	id: string;
	title: string;
	children: ReactNode;
} ) => (
	<Card id={ id } className="woopayments-fraud-protection-rule">
		<h3>{ title }</h3>
		{ children }
	</Card>
);

const RuleToggle = ( {
	setting,
	ruleTitle,
	label,
	description,
	settings,
	onSettingsChange,
	isReviewFeatureActive,
	children,
}: {
	setting: string;
	ruleTitle: string;
	label: string;
	description: ReactNode;
	settings: ProtectionSettingsUI;
	onSettingsChange: ( settings: ProtectionSettingsUI ) => void;
	isReviewFeatureActive: boolean;
	children?: ReactNode;
} ) => {
	const settingUI = settings[ setting ];
	const filterAction = settingUI?.block ? 'block' : 'review';
	const updateSetting = ( patch: Partial< FraudPreventionSettings > ) => {
		onSettingsChange( {
			...settings,
			[ setting ]: {
				...settingUI,
				...patch,
			},
		} );
	};

	if ( ! settingUI ) {
		return null;
	}

	return (
		<div className="woopayments-fraud-protection-rule__toggle">
			<CheckboxControl
				checked={ settingUI.enabled }
				label={ label }
				help={ description }
				onChange={ ( value ) =>
					updateSetting( { enabled: Boolean( value ) } )
				}
				__nextHasNoMarginBottom
			/>
			{ settingUI.enabled && (
				<>
					{ children }
					{ isReviewFeatureActive && (
						<RadioControl
							label={
								<>
									<span aria-hidden="true">
										{ __( 'Filter action', 'woocommerce' ) }
									</span>
									<span className="screen-reader-text">
										{ sprintf(
											/* translators: %s: Fraud protection rule name. */
											__(
												'Filter action for %s',
												'woocommerce'
											),
											ruleTitle
										) }
									</span>
								</>
							}
							selected={ filterAction }
							options={ [
								{
									label: __(
										'Authorize and hold for review',
										'woocommerce'
									),
									value: 'review',
								},
								{
									label: __( 'Block Payment', 'woocommerce' ),
									value: 'block',
								},
							] }
							onChange={ ( value ) =>
								updateSetting( {
									block: value === 'block',
								} )
							}
						/>
					) }
				</>
			) }
		</div>
	);
};

const ThresholdControls = ( {
	setting,
	settings,
	onSettingsChange,
	environment,
}: {
	setting: string;
	settings: ProtectionSettingsUI;
	onSettingsChange: ( settings: ProtectionSettingsUI ) => void;
	environment: FraudProtectionEnvironment;
} ) => {
	const settingUI = settings[ setting ];

	if ( ! settingUI ) {
		return null;
	}

	const updateField = ( field: string ) => ( value: string ) => {
		onSettingsChange( {
			...settings,
			[ setting ]: {
				...settingUI,
				[ field ]: value,
			},
		} );
	};

	if ( setting === Rules.RULE_PURCHASE_PRICE_THRESHOLD ) {
		const purchasePriceSetting = isPurchasePriceThresholdSetting(
			settingUI
		)
			? settingUI
			: null;
		const minAmount = getFloatValue(
			purchasePriceSetting?.min_amount ?? ''
		);
		const maxAmount = getFloatValue(
			purchasePriceSetting?.max_amount ?? ''
		);
		const thresholdNotice = getThresholdNotice( {
			minimum: minAmount,
			maximum: maxAmount,
			rangeMessage: __(
				'A price range must be set for this filter to take effect.',
				'woocommerce'
			),
			orderMessage: __(
				'Maximum purchase price must be greater than the minimum purchase price.',
				'woocommerce'
			),
		} );
		const currencySymbol = getCurrencySymbol( environment.storeCurrency );

		return (
			<div className="woopayments-fraud-protection-rule__threshold-details">
				<h4>{ __( 'Limits', 'woocommerce' ) }</h4>
				<div className="woopayments-fraud-protection-rule__thresholds">
					<AmountThresholdInput
						id="woopayments-fraud-protection-minimum-purchase-price"
						label={ __( 'Minimum purchase price', 'woocommerce' ) }
						value={ `${ purchasePriceSetting?.min_amount ?? '' }` }
						currency={ environment.storeCurrency }
						currencySymbol={ currencySymbol }
						onChange={ updateField( 'min_amount' ) }
					/>
					<AmountThresholdInput
						id="woopayments-fraud-protection-maximum-purchase-price"
						label={ __( 'Maximum purchase price', 'woocommerce' ) }
						value={ `${ purchasePriceSetting?.max_amount ?? '' }` }
						currency={ environment.storeCurrency }
						currencySymbol={ currencySymbol }
						onChange={ updateField( 'max_amount' ) }
					/>
				</div>
				{ thresholdNotice }
			</div>
		);
	}

	const orderItemsSetting = isOrderItemsThresholdSetting( settingUI )
		? settingUI
		: null;
	const minItems = getIntegerValue( orderItemsSetting?.min_items ?? '' );
	const maxItems = getIntegerValue( orderItemsSetting?.max_items ?? '' );
	const thresholdNotice = getThresholdNotice( {
		minimum: minItems,
		maximum: maxItems,
		rangeMessage: __(
			'An item range must be set for this filter to take effect.',
			'woocommerce'
		),
		orderMessage: __(
			'Maximum item count must be greater than the minimum item count.',
			'woocommerce'
		),
	} );
	const preventOrderItemsInvalidKey = (
		event: KeyboardEvent< HTMLInputElement >
	) => {
		if ( /^[+\-.,eE]$/.test( event.key ) ) {
			event.preventDefault();
		}
	};

	return (
		<div className="woopayments-fraud-protection-rule__threshold-details">
			<h4>{ __( 'Limits', 'woocommerce' ) }</h4>
			<div className="woopayments-fraud-protection-rule__thresholds">
				<TextControl
					label={ __( 'Minimum items per order', 'woocommerce' ) }
					type="number"
					value={ `${ orderItemsSetting?.min_items ?? '' }` }
					placeholder="0"
					min="1"
					step="1"
					help={ __( 'Leave blank for no limit', 'woocommerce' ) }
					onKeyDown={ preventOrderItemsInvalidKey }
					onChange={ updateField( 'min_items' ) }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
				<TextControl
					label={ __( 'Maximum items per order', 'woocommerce' ) }
					type="number"
					value={ `${ orderItemsSetting?.max_items ?? '' }` }
					placeholder="0"
					min="1"
					step="1"
					help={ __( 'Leave blank for no limit', 'woocommerce' ) }
					onKeyDown={ preventOrderItemsInvalidKey }
					onChange={ updateField( 'max_items' ) }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			</div>
			{ thresholdNotice }
		</div>
	);
};

const AdvancedFraudSettingsDescription = () => (
	<>
		<h2>{ __( 'Filter configuration', 'woocommerce' ) }</h2>
		<p>
			{ __(
				'Set up advanced fraud filters. Enable at least one filter to activate advanced protection.',
				'woocommerce'
			) }
		</p>
	</>
);

export const FraudProtectionAdvancedSettingsPage = () => {
	const settings = asSettingsRecord( useGetSettings() );
	const environment = useMemo(
		() => getFraudProtectionEnvironment( settings ),
		[ settings ]
	);
	const { isLoading, isSaving, saveSettings } = useSettings();
	const [ currentProtectionLevel, updateProtectionLevel ] =
		useCurrentProtectionLevel() as StringSetting;
	const [
		advancedFraudProtectionSettings,
		updateAdvancedFraudProtectionSettings,
	] = useAdvancedFraudProtectionSettings() as AdvancedFraudProtectionSetting;
	const [ isDirty, setIsDirty ] = useState( false );
	const [ validationError, setValidationError ] = useState< string | null >(
		null
	);
	const validationErrorRef = useRef< HTMLDivElement >( null );
	const [ protectionSettingsUI, setProtectionSettingsUI ] =
		useState< ProtectionSettingsUI >( {} );

	useEffect( () => {
		const ruleset =
			Array.isArray( advancedFraudProtectionSettings ) ||
			typeof advancedFraudProtectionSettings === 'string'
				? advancedFraudProtectionSettings
				: [];

		setProtectionSettingsUI( readRuleset( ruleset, environment ) );
	}, [ advancedFraudProtectionSettings, environment ] );

	useEffect( () => {
		if ( ! isDirty ) {
			return;
		}

		const handleBeforeUnload = ( event: BeforeUnloadEvent ) => {
			const message = __(
				'There are unsaved changes on this page. Are you sure you want to leave and discard the unsaved changes?',
				'woocommerce'
			);
			event.preventDefault();
			event.returnValue = message;

			return message;
		};

		window.addEventListener( 'beforeunload', handleBeforeUnload );

		return () => {
			window.removeEventListener( 'beforeunload', handleBeforeUnload );
		};
	}, [ isDirty ] );

	useEffect( () => {
		if ( ! validationError ) {
			return;
		}

		const focusTimeout = window.setTimeout( () => {
			validationErrorRef.current?.focus();
		}, 0 );

		return () => window.clearTimeout( focusTimeout );
	}, [ validationError ] );

	useEffect( () => {
		if ( isLoading || typeof window.IntersectionObserver === 'undefined' ) {
			return;
		}

		const viewedCardIds = new Set< string >();
		const observer = new window.IntersectionObserver(
			( entries, currentObserver ) => {
				entries.forEach( ( entry ) => {
					const cardId = entry.target.id;
					const eventName = ADVANCED_RULE_CARD_VIEW_EVENTS[ cardId ];

					if (
						! entry.isIntersecting ||
						! eventName ||
						viewedCardIds.has( cardId )
					) {
						return;
					}

					viewedCardIds.add( cardId );
					recordEvent( eventName );
					currentObserver.unobserve( entry.target );
				} );
			}
		);

		Object.keys( ADVANCED_RULE_CARD_VIEW_EVENTS ).forEach( ( cardId ) => {
			const card = document.getElementById( cardId );

			if ( card ) {
				observer.observe( card );
			}
		} );

		return () => observer.disconnect();
	}, [ isLoading ] );

	const updateProtectionSettingsUI = (
		nextSettings: ProtectionSettingsUI
	) => {
		setProtectionSettingsUI( nextSettings );
		setIsDirty( true );
	};

	const handleSaveSettings = () => {
		const nextValidationError = validateThresholds( protectionSettingsUI );

		setValidationError( nextValidationError );

		if ( nextValidationError ) {
			return;
		}

		if ( ! hasEnabledRule( protectionSettingsUI ) ) {
			if ( currentProtectionLevel === ProtectionLevel.BASIC ) {
				dispatch( 'core/notices' ).createErrorNotice(
					__(
						'At least one risk filter needs to be enabled for advanced protection.',
						'woocommerce'
					)
				);
				return;
			}

			updateProtectionLevel( ProtectionLevel.BASIC );
		} else if ( currentProtectionLevel !== ProtectionLevel.ADVANCED ) {
			updateProtectionLevel( ProtectionLevel.ADVANCED );
		}

		const ruleset = writeRuleset( protectionSettingsUI, environment );

		updateAdvancedFraudProtectionSettings( ruleset );
		void Promise.resolve( saveSettings() ).then( ( didSave ) => {
			if ( didSave !== false ) {
				setIsDirty( false );
				recordEvent( 'wcpay_fraud_protection_advanced_settings_saved', {
					settings: JSON.stringify( ruleset ),
				} );
			}
		} );
	};

	if ( isLoading ) {
		return (
			<section
				className="woopayments-fraud-protection-advanced"
				aria-busy="true"
			>
				<a
					className="woopayments-fraud-protection-advanced__back-link"
					href={ getSettingsPaymentsProviderRouteUrl(
						'/woopayments/settings'
					) }
				>
					{ __( 'Back to WooPayments settings', 'woocommerce' ) }
				</a>
				<h1>{ __( 'Advanced fraud protection', 'woocommerce' ) }</h1>
				<div className="woopayments-fraud-protection-advanced__description">
					<AdvancedFraudSettingsDescription />
				</div>
				<div role="status" aria-live="polite">
					{ __( 'Loading fraud protection rules', 'woocommerce' ) }
				</div>
				<div className="woopayments-fraud-protection-advanced__rules">
					{ Object.keys( ADVANCED_RULE_CARD_VIEW_EVENTS ).map(
						( cardId ) => (
							<LoadingRuleCard key={ cardId } id={ cardId } />
						)
					) }
				</div>
			</section>
		);
	}

	const supportsAllCountries = environment.allowedCountriesType === 'all';
	const isSellingToSupportedAvsLocations =
		isSellingToAvsSupportedLocations( environment );
	const hasAdvancedFraudProtectionSettingsError =
		advancedFraudProtectionSettings === 'error';

	return (
		<section
			className="woopayments-fraud-protection-advanced"
			aria-busy={ isSaving ? 'true' : undefined }
		>
			<a
				className="woopayments-fraud-protection-advanced__back-link"
				href={ getSettingsPaymentsProviderRouteUrl(
					'/woopayments/settings'
				) }
			>
				{ __( 'Back to WooPayments settings', 'woocommerce' ) }
			</a>
			<h1>{ __( 'Advanced fraud protection', 'woocommerce' ) }</h1>
			<div className="woopayments-fraud-protection-advanced__description">
				<AdvancedFraudSettingsDescription />
			</div>
			{ validationError && (
				<div
					className="woopayments-fraud-protection-advanced__error"
					ref={ validationErrorRef }
					tabIndex={ -1 }
				>
					<Notice status="error" isDismissible={ false }>
						{ sprintf(
							/* translators: %s: Advanced fraud rule validation error. */
							__( 'Settings were not saved. %s', 'woocommerce' ),
							validationError
						) }
					</Notice>
				</div>
			) }
			{ hasAdvancedFraudProtectionSettingsError ? (
				<Notice status="error" isDismissible={ false }>
					{ __(
						'There was an error retrieving your fraud protection settings. Please refresh the page to try again.',
						'woocommerce'
					) }
				</Notice>
			) : (
				<>
					<div className="woopayments-fraud-protection-advanced__rules">
						<RuleCard
							id="avs-mismatch-card"
							title={ __( 'AVS Mismatch', 'woocommerce' ) }
						>
							{ ! isSellingToSupportedAvsLocations && (
								<Notice
									status="warning"
									isDismissible={ false }
								>
									{ createInterpolateElement(
										__(
											'AVS checks are commonly supported only for cards issued in the United States, Canada, and the United Kingdom. None of your <a>selling locations</a> support AVS, so this filter is unlikely to block any payments.',
											'woocommerce'
										),
										{
											a: (
												// eslint-disable-next-line jsx-a11y/anchor-has-content
												<a
													href={ getWooCommerceGeneralSettingsUrl() }
												/>
											),
										}
									) }
								</Notice>
							) }
							<RuleToggle
								setting={ Rules.RULE_AVS_VERIFICATION }
								ruleTitle={ __(
									'AVS Mismatch',
									'woocommerce'
								) }
								label={ __(
									'Enable AVS Mismatch filter',
									'woocommerce'
								) }
								description={ __(
									'This filter compares the post code submitted by the customer against the post code on file with the card issuer. The payment will be blocked if the two post codes do not match. AVS checks are not supported by every country or card issuer, so this filter will not block all payments with a mismatched post code.',
									'woocommerce'
								) }
								settings={ protectionSettingsUI }
								onSettingsChange={ updateProtectionSettingsUI }
								isReviewFeatureActive={
									environment.isReviewFeatureActive
								}
							/>
							<RuleDescription>
								{ __(
									'Buyers who can provide correct post code on file with the issuing bank are more likely to be the actual account holder.',
									'woocommerce'
								) }
							</RuleDescription>
						</RuleCard>
						<RuleCard
							id="international-ip-address-card"
							title={ __(
								'International IP Address',
								'woocommerce'
							) }
						>
							{ supportsAllCountries ? (
								<Notice
									status="warning"
									isDismissible={ false }
								>
									{ __(
										"This filter is disabled because you're currently selling to all countries.",
										'woocommerce'
									) }
								</Notice>
							) : (
								<RuleToggle
									setting={
										Rules.RULE_INTERNATIONAL_IP_ADDRESS
									}
									ruleTitle={ __(
										'International IP Address',
										'woocommerce'
									) }
									label={ __(
										'Enable International IP Address filter',
										'woocommerce'
									) }
									description={ createInterpolateElement(
										__(
											'This filter screens for <ipAddressLink>IP addresses</ipAddressLink> outside of your <supportedCountriesLink>supported countries</supportedCountriesLink>. When enabled the payment will be blocked.',
											'woocommerce'
										),
										{
											ipAddressLink: (
												<ExternalLink
													href={ IP_ADDRESS_DOC_URL }
												>
													{ null }
												</ExternalLink>
											),
											supportedCountriesLink: (
												// eslint-disable-next-line jsx-a11y/anchor-has-content
												<a
													href={ getWooCommerceGeneralSettingsUrl() }
												/>
											),
										}
									) }
									settings={ protectionSettingsUI }
									onSettingsChange={
										updateProtectionSettingsUI
									}
									isReviewFeatureActive={
										environment.isReviewFeatureActive
									}
								/>
							) }
							{ ! supportsAllCountries && (
								<AllowedCountriesNotice
									environment={ environment }
									settings={ protectionSettingsUI }
								/>
							) }
							<RuleDescription>
								{ __(
									'You should be especially wary when a customer has an international IP address but uses domestic billing and shipping information. Fraudsters often pretend to live in one location, but live and shop from another.',
									'woocommerce'
								) }
							</RuleDescription>
						</RuleCard>
						<RuleCard
							id="ip-address-mismatch-card"
							title={ __( 'IP Address Mismatch', 'woocommerce' ) }
						>
							<RuleToggle
								setting={ Rules.RULE_IP_ADDRESS_MISMATCH }
								ruleTitle={ __(
									'IP Address Mismatch',
									'woocommerce'
								) }
								label={ __(
									'Enable IP Address Mismatch filter',
									'woocommerce'
								) }
								description={ createInterpolateElement(
									__(
										"This filter screens for customer's <a>IP address</a> to see if it is in a different country than indicated in their billing address. When enabled the payment will be blocked.",
										'woocommerce'
									),
									{
										a: (
											<ExternalLink
												href={ IP_ADDRESS_DOC_URL }
											>
												{ null }
											</ExternalLink>
										),
									}
								) }
								settings={ protectionSettingsUI }
								onSettingsChange={ updateProtectionSettingsUI }
								isReviewFeatureActive={
									environment.isReviewFeatureActive
								}
							/>
							<RuleDescription>
								{ __(
									'Fraudulent transactions often use fake addresses to place orders. If the IP address seems to be in one country, but the billing address is in another, that could signal potential fraud.',
									'woocommerce'
								) }
							</RuleDescription>
						</RuleCard>
						<RuleCard
							id="address-mismatch-card"
							title={ __( 'Address Mismatch', 'woocommerce' ) }
						>
							<RuleToggle
								setting={ Rules.RULE_ADDRESS_MISMATCH }
								ruleTitle={ __(
									'Address Mismatch',
									'woocommerce'
								) }
								label={ __(
									'Enable Address Mismatch filter',
									'woocommerce'
								) }
								description={ __(
									'This filter screens for differences between the shipping information and the billing information (country). When enabled the payment will be blocked.',
									'woocommerce'
								) }
								settings={ protectionSettingsUI }
								onSettingsChange={ updateProtectionSettingsUI }
								isReviewFeatureActive={
									environment.isReviewFeatureActive
								}
							/>
							<RuleDescription>
								{ __(
									'There are legitimate reasons for a billing/shipping mismatch with a customer purchase, but a mismatch could also indicate that someone is using a stolen identity to complete a purchase.',
									'woocommerce'
								) }
							</RuleDescription>
						</RuleCard>
						<RuleCard
							id="purchase-price-threshold-card"
							title={ __(
								'Purchase Price Threshold',
								'woocommerce'
							) }
						>
							<RuleToggle
								setting={ Rules.RULE_PURCHASE_PRICE_THRESHOLD }
								ruleTitle={ __(
									'Purchase Price Threshold',
									'woocommerce'
								) }
								label={ __(
									'Enable Purchase Price Threshold filter',
									'woocommerce'
								) }
								description={ __(
									'This filter compares the purchase price of an order to the minimum and maximum purchase amounts that you specify. When enabled the payment will be blocked.',
									'woocommerce'
								) }
								settings={ protectionSettingsUI }
								onSettingsChange={ updateProtectionSettingsUI }
								isReviewFeatureActive={
									environment.isReviewFeatureActive
								}
							>
								<ThresholdControls
									setting={
										Rules.RULE_PURCHASE_PRICE_THRESHOLD
									}
									settings={ protectionSettingsUI }
									onSettingsChange={
										updateProtectionSettingsUI
									}
									environment={ environment }
								/>
							</RuleToggle>
							<RuleDescription>
								{ __(
									'An unusually high purchase amount, compared to the average for your business, can indicate potential fraudulent activity.',
									'woocommerce'
								) }
							</RuleDescription>
						</RuleCard>
						<RuleCard
							id="order-items-threshold-card"
							title={ __(
								'Order Items Threshold',
								'woocommerce'
							) }
						>
							<RuleToggle
								setting={ Rules.RULE_ORDER_ITEMS_THRESHOLD }
								ruleTitle={ __(
									'Order Items Threshold',
									'woocommerce'
								) }
								label={ __(
									'Enable Order Items Threshold filter',
									'woocommerce'
								) }
								description={ __(
									'This filter compares the amount of items in an order to the minimum and maximum counts that you specify. When enabled the payment will be blocked.',
									'woocommerce'
								) }
								settings={ protectionSettingsUI }
								onSettingsChange={ updateProtectionSettingsUI }
								isReviewFeatureActive={
									environment.isReviewFeatureActive
								}
							>
								<ThresholdControls
									setting={ Rules.RULE_ORDER_ITEMS_THRESHOLD }
									settings={ protectionSettingsUI }
									onSettingsChange={
										updateProtectionSettingsUI
									}
									environment={ environment }
								/>
							</RuleToggle>
							<RuleDescription>
								{ __(
									'An unusually high item count, compared to the average for your business, can indicate potential fraudulent activity.',
									'woocommerce'
								) }
							</RuleDescription>
						</RuleCard>
						<RuleCard
							id="cvc-verification-card"
							title={ __( 'CVC Verification', 'woocommerce' ) }
						>
							<Notice status="warning" isDismissible={ false }>
								{ environment.isCvcFailureDeclineEnabled ? (
									<>
										{ __(
											'For security, this filter is enabled and cannot be modified. Payments failing CVC verification will be blocked.',
											'woocommerce'
										) }{ ' ' }
										<ExternalLink
											href={ CVC_VERIFICATION_DOC_URL }
										>
											{ __(
												'Learn more',
												'woocommerce'
											) }
										</ExternalLink>
									</>
								) : (
									__(
										'This filter is disabled, and cannot be modified.',
										'woocommerce'
									)
								) }
							</Notice>
							<RuleDescription>
								{ __(
									'Because the card security code appears only on the card and not on receipts or statements, the card security code provides some assurance that the physical card is in the possession of the buyer.',
									'woocommerce'
								) }
							</RuleDescription>
						</RuleCard>
					</div>
					<div className="woopayments-fraud-protection-advanced__footer">
						<Button
							variant="primary"
							isBusy={ isSaving }
							disabled={ isSaving || ! isDirty }
							onClick={ handleSaveSettings }
						>
							{ __( 'Save changes', 'woocommerce' ) }
						</Button>
					</div>
				</>
			) }
		</section>
	);
};

export default FraudProtectionAdvancedSettingsPage;
