/**
 * External dependencies
 */
import {
	Button,
	CheckboxControl,
	ExternalLink,
	Icon,
	Modal,
	Notice,
} from '@wordpress/components';
import { RawHTML, useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { info as infoIcon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import {
	CARD_BRANDS,
	getPaymentMethodDefinition,
	WooPaymentsPaymentMethodDefinition,
} from './payment-method-definitions';
import type { PmPromotion } from '../promotions/types';

type PaymentMethodStatus = {
	status?: string;
	requirements?: unknown[];
};

type FeeAmount = {
	percentage_rate?: number;
	fixed_rate?: number;
	currency?: string;
	discount?: number;
	end_time?: string | null;
	volume_allowance?: number | null;
	volume_currency?: string | null;
	current_volume?: number | null;
};

type FeeStructure = {
	base?: FeeAmount;
	additional?: FeeAmount;
	fx?: FeeAmount;
	discount?: FeeAmount[];
};

type DuplicatePaymentMethodNotices = Record< string, string[] | undefined >;

type PaymentMethodsListProps = {
	methodIds: string[];
	enabledMethodIds: string[];
	statuses: Record< string, PaymentMethodStatus | undefined >;
	accountFees?: Record< string, FeeStructure | undefined >;
	pmPromotions?: PmPromotion[];
	duplicatedPaymentMethodIds?: DuplicatePaymentMethodNotices;
	dismissedDuplicatePaymentMethodNotices?: DuplicatePaymentMethodNotices;
	isManualCaptureEnabled: boolean;
	accountCountry?: string;
	onEnable: ( methodId: string ) => void;
	onDisable: ( methodId: string ) => void;
	onDismissDuplicateNotice?: ( notices: Record< string, string[] > ) => void;
};

type Availability = {
	isActionable: boolean;
	chip?: string;
	chipType?: 'warning' | 'error';
	notice?: React.ReactNode;
	noticeStatus?: 'info' | 'warning' | 'error';
};

const REQUIREMENTS_LABELS: Record< string, string > = {
	'business_profile.mcc': __( 'Business category', 'woocommerce' ),
	'business_profile.url': __( 'Business website', 'woocommerce' ),
	'business_profile.product_description': __(
		'Product description',
		'woocommerce'
	),
	'business_profile.support_phone': __(
		'Support phone number',
		'woocommerce'
	),
	'business_profile.support_email': __(
		'Support email address',
		'woocommerce'
	),
	external_account: __( 'Bank account', 'woocommerce' ),
};
const ZERO_DECIMAL_CURRENCY_CODES = new Set( [
	'BIF',
	'CLP',
	'DJF',
	'GNF',
	'JPY',
	'KMF',
	'KRW',
	'MGA',
	'PYG',
	'RWF',
	'UGX',
	'VND',
	'VUV',
	'XAF',
	'XOF',
	'XPF',
] );

const getStatus = (
	definition: WooPaymentsPaymentMethodDefinition,
	statuses: Record< string, PaymentMethodStatus | undefined >
): PaymentMethodStatus => statuses[ definition.stripeKey ] || {};

const getRequirementLabels = ( requirements: unknown[] ) =>
	requirements
		.filter(
			( requirement ): requirement is string =>
				typeof requirement === 'string'
		)
		.map(
			( requirement ) => REQUIREMENTS_LABELS[ requirement ] || requirement
		);

const PaymentMethodIcon = ( {
	definition,
}: {
	definition: WooPaymentsPaymentMethodDefinition;
} ) => {
	if ( definition.iconUrl ) {
		return (
			<img
				className="woopayments-settings-payment-method-item__icon"
				src={ definition.iconUrl }
				alt={ sprintf(
					/* translators: %s: Payment method label. */
					__( '%s logo', 'woocommerce' ),
					definition.label
				) }
			/>
		);
	}

	return (
		<span
			className="woopayments-settings-payment-method-item__icon woopayments-settings-payment-method-item__icon--fallback"
			aria-hidden="true"
		>
			{ definition.label.charAt( 0 ).toUpperCase() }
		</span>
	);
};

const CardBrandLogos = () => (
	<div
		className="woopayments-settings-payment-method-item__card-brands"
		aria-label={ __( 'Supported card brands', 'woocommerce' ) }
	>
		{ CARD_BRANDS.map( ( brand ) => (
			<img key={ brand.id } src={ brand.iconUrl } alt={ brand.label } />
		) ) }
	</div>
);

const formatFeePercentage = ( rate?: number, includeZero = false ) => {
	if (
		typeof rate !== 'number' ||
		rate < 0 ||
		( ! includeZero && rate === 0 )
	) {
		return '';
	}

	return Number( ( rate * 100 ).toFixed( 3 ) ).toLocaleString( undefined, {
		maximumFractionDigits: 3,
	} );
};

const formatFeeCurrency = (
	amount?: number,
	currency = 'USD',
	includeZero = false
) => {
	if (
		typeof amount !== 'number' ||
		amount < 0 ||
		( ! includeZero && amount === 0 )
	) {
		return '';
	}

	const currencyCode = currency.toUpperCase();
	const isZeroDecimalCurrency =
		ZERO_DECIMAL_CURRENCY_CODES.has( currencyCode );

	try {
		return new Intl.NumberFormat( undefined, {
			style: 'currency',
			currency: currencyCode,
			currencyDisplay: 'narrowSymbol',
		} ).format( isZeroDecimalCurrency ? amount : amount / 100 );
	} catch {
		return `${ currencyCode } ${ ( isZeroDecimalCurrency
			? amount
			: amount / 100
		).toLocaleString( undefined, {
			maximumFractionDigits: isZeroDecimalCurrency ? 0 : 2,
			minimumFractionDigits: isZeroDecimalCurrency ? 0 : 2,
		} ) }`;
	}
};

const formatFeeAmount = ( fee?: FeeAmount, multiplier = 1 ) => {
	if ( ! fee ) {
		return '';
	}

	const percentage = formatFeePercentage(
		typeof fee.percentage_rate === 'number'
			? fee.percentage_rate * multiplier
			: undefined
	);
	const fixed = formatFeeCurrency(
		typeof fee.fixed_rate === 'number'
			? fee.fixed_rate * multiplier
			: undefined,
		fee.currency
	);

	if ( percentage && fixed ) {
		return sprintf(
			/* translators: %1$s: Percentage fee, %2$s: fixed fee, %3$s: percent symbol. */
			__( '%1$s%3$s + %2$s', 'woocommerce' ),
			percentage,
			fixed,
			'%'
		);
	}

	return percentage ? `${ percentage }%` : fixed;
};

const formatMethodPillFeeAmount = ( fee?: FeeAmount ) => {
	if ( ! fee ) {
		return '';
	}

	const percentage = formatFeePercentage( fee.percentage_rate, true );
	const fixed = formatFeeCurrency( fee.fixed_rate, fee.currency, true );

	if ( percentage && fixed ) {
		return sprintf(
			/* translators: %1$s: Percentage fee, %2$s: fixed fee, %3$s: percent symbol. */
			__( '%1$s%3$s + %2$s', 'woocommerce' ),
			percentage,
			fixed,
			'%'
		);
	}

	return percentage ? `${ percentage }%` : fixed;
};

const getDiscountFee = ( feeStructure?: FeeStructure ) =>
	feeStructure?.discount?.[ 0 ];

const getDiscountMultiplier = ( feeStructure?: FeeStructure ) => {
	const discount = getDiscountFee( feeStructure )?.discount;

	return typeof discount === 'number' && discount > 0 ? 1 - discount : 1;
};

const getCurrentBaseFee = (
	feeStructure?: FeeStructure
): FeeAmount | undefined => {
	const discount = getDiscountFee( feeStructure );

	if ( ! discount ) {
		return feeStructure?.base;
	}

	if ( typeof discount.discount === 'number' && discount.discount > 0 ) {
		return {
			percentage_rate:
				typeof feeStructure?.base?.percentage_rate === 'number'
					? feeStructure.base.percentage_rate *
					  getDiscountMultiplier( feeStructure )
					: undefined,
			fixed_rate:
				typeof feeStructure?.base?.fixed_rate === 'number'
					? feeStructure.base.fixed_rate *
					  getDiscountMultiplier( feeStructure )
					: undefined,
			currency: feeStructure?.base?.currency,
		};
	}

	return discount;
};

const formatMethodFeesDescription = ( feeStructure?: FeeStructure ) => {
	const currentBaseFee = getCurrentBaseFee( feeStructure );
	const feeAmount = formatMethodPillFeeAmount( currentBaseFee );

	if ( ! feeAmount ) {
		return '';
	}

	return sprintf(
		/* translators: %s: Payment method fee amount. */
		__( 'From %s', 'woocommerce' ),
		feeAmount
	);
};

const formatDiscountDate = ( dateValue: string ) => {
	const normalizedValue = dateValue.includes( 'T' )
		? dateValue
		: dateValue.replace( ' ', 'T' );
	const date = new Date( normalizedValue );

	if ( Number.isNaN( date.getTime() ) ) {
		return dateValue;
	}

	return new Intl.DateTimeFormat( undefined, {
		month: 'short',
		day: 'numeric',
		year: 'numeric',
	} ).format( date );
};

const getDiscountBadgeText = ( discountFee?: FeeAmount ) => {
	if (
		typeof discountFee?.discount !== 'number' ||
		discountFee.discount <= 0
	) {
		return '';
	}

	if ( discountFee.end_time ) {
		return sprintf(
			/* translators: %1$s: Discount percentage, %2$s: percent symbol, %3$s: expiration date. */
			__( '%1$s%2$s off fees through %3$s', 'woocommerce' ),
			formatFeePercentage( discountFee.discount ),
			'%',
			formatDiscountDate( discountFee.end_time )
		);
	}

	return sprintf(
		/* translators: %1$s: Discount percentage, %2$s: percent symbol. */
		__( '%1$s%2$s off fees', 'woocommerce' ),
		formatFeePercentage( discountFee.discount ),
		'%'
	);
};

const getDiscountTooltipText = ( discountFee?: FeeAmount ) => {
	if (
		typeof discountFee?.discount !== 'number' ||
		discountFee.discount <= 0
	) {
		return '';
	}

	const discountPercentage = formatFeePercentage( discountFee.discount );
	const currency =
		discountFee.volume_currency || discountFee.currency || 'USD';

	if ( discountFee.volume_allowance && discountFee.end_time ) {
		return sprintf(
			/* translators: %1$s: Discount percentage, %2$s: percent symbol, %3$s: total payment volume, %4$s: expiration date. */
			__(
				'You are saving %1$s%2$s on processing fees for the first %3$s of total payment volume or through %4$s.',
				'woocommerce'
			),
			discountPercentage,
			'%',
			formatFeeCurrency( discountFee.volume_allowance, currency ),
			formatDiscountDate( discountFee.end_time )
		);
	}

	if ( discountFee.volume_allowance ) {
		return sprintf(
			/* translators: %1$s: Discount percentage, %2$s: percent symbol, %3$s: total payment volume. */
			__(
				'You are saving %1$s%2$s on processing fees for the first %3$s of total payment volume.',
				'woocommerce'
			),
			discountPercentage,
			'%',
			formatFeeCurrency( discountFee.volume_allowance, currency )
		);
	}

	if ( discountFee.end_time ) {
		return sprintf(
			/* translators: %1$s: Discount percentage, %2$s: percent symbol, %3$s: expiration date. */
			__(
				'You are saving %1$s%2$s on processing fees through %3$s.',
				'woocommerce'
			),
			discountPercentage,
			'%',
			formatDiscountDate( discountFee.end_time )
		);
	}

	return sprintf(
		/* translators: %1$s: Discount percentage, %2$s: percent symbol. */
		__( 'You are saving %1$s%2$s on processing fees.', 'woocommerce' ),
		discountPercentage,
		'%'
	);
};

const FeeDetails = ( {
	feeStructure,
	tooltipId,
}: {
	feeStructure?: FeeStructure;
	tooltipId: string;
} ) => {
	const [ isTooltipOpen, setIsTooltipOpen ] = useState( false );
	const feeDescription = formatMethodFeesDescription( feeStructure );
	const baseFee = feeStructure?.base;
	if ( ! feeDescription || ! baseFee ) {
		return null;
	}

	const discountMultiplier = getDiscountMultiplier( feeStructure );
	const baseFeeDescription = formatFeeAmount( baseFee, discountMultiplier );
	const additionalFee = formatFeeAmount(
		feeStructure?.additional,
		discountMultiplier
	);
	const fxFee = formatFeeAmount( feeStructure?.fx );
	const totalFee = {
		percentage_rate:
			( baseFee.percentage_rate || 0 ) * discountMultiplier +
			( feeStructure?.additional?.percentage_rate || 0 ) *
				discountMultiplier +
			( feeStructure?.fx?.percentage_rate || 0 ),
		fixed_rate:
			( baseFee.fixed_rate || 0 ) * discountMultiplier +
			( feeStructure?.additional?.fixed_rate || 0 ) * discountMultiplier +
			( feeStructure?.fx?.fixed_rate || 0 ),
		currency: baseFee.currency,
	};

	return (
		<span
			className="woopayments-settings-payment-method-item__fee-wrapper"
			onBlur={ ( event ) => {
				const nextFocusedElement = event.relatedTarget;
				if (
					! nextFocusedElement ||
					! event.currentTarget.contains( nextFocusedElement as Node )
				) {
					setIsTooltipOpen( false );
				}
			} }
			onMouseEnter={ () => setIsTooltipOpen( true ) }
			onMouseLeave={ () => setIsTooltipOpen( false ) }
		>
			<button
				type="button"
				className="woopayments-settings-payment-method-item__fee-pill"
				aria-label={ sprintf(
					/* translators: %s: Payment method fee amount. */
					__( '%s fee details', 'woocommerce' ),
					feeDescription
				) }
				aria-expanded={ isTooltipOpen }
				aria-controls={ tooltipId }
				aria-describedby={ isTooltipOpen ? tooltipId : undefined }
				onClick={ () => setIsTooltipOpen( true ) }
				onFocus={ () => setIsTooltipOpen( true ) }
				onKeyDown={ ( event ) => {
					if ( event.key === 'Escape' ) {
						event.stopPropagation();
						setIsTooltipOpen( false );
					}
				} }
			>
				{ feeDescription }
			</button>
			{ isTooltipOpen && (
				<span
					id={ tooltipId }
					role="tooltip"
					className="woopayments-settings-payment-method-item__fees-tooltip"
				>
					<span>
						<span>{ __( 'Base fee', 'woocommerce' ) }</span>
						<span>{ baseFeeDescription }</span>
					</span>
					{ additionalFee && (
						<span>
							<span>
								{ __(
									'International payment method fee',
									'woocommerce'
								) }
							</span>
							<span>{ additionalFee }</span>
						</span>
					) }
					{ fxFee && (
						<span>
							<span>
								{ __(
									'Currency conversion fee',
									'woocommerce'
								) }
							</span>
							<span>{ fxFee }</span>
						</span>
					) }
					<span>
						<span>
							{ __( 'Total per transaction', 'woocommerce' ) }
						</span>
						<strong>{ formatFeeAmount( totalFee ) }</strong>
					</span>
				</span>
			) }
		</span>
	);
};

const DiscountBadge = ( {
	feeStructure,
	descriptionId,
}: {
	feeStructure?: FeeStructure;
	descriptionId: string;
} ) => {
	const discountFee = feeStructure?.discount?.[ 0 ];
	const badgeText = getDiscountBadgeText( discountFee );

	if ( ! badgeText ) {
		return null;
	}

	const tooltipText = getDiscountTooltipText( discountFee );

	return (
		<>
			<span
				className="woopayments-settings-payment-method-item__discount-badge"
				aria-describedby={ tooltipText ? descriptionId : undefined }
			>
				{ badgeText }
			</span>
			{ tooltipText && (
				<span id={ descriptionId } className="screen-reader-text">
					{ tooltipText }
				</span>
			) }
		</>
	);
};

const PmPromotionBadge = ( {
	promotion,
	tooltipId,
}: {
	promotion?: PmPromotion;
	tooltipId: string;
} ) => {
	const [ isTooltipOpen, setIsTooltipOpen ] = useState( false );
	const triggerRef = useRef< HTMLButtonElement >( null );
	const wrapperRef = useRef< HTMLDivElement >( null );
	const shouldSuppressNextFocusOpenRef = useRef( false );

	const restoreFocusToTrigger = () => {
		shouldSuppressNextFocusOpenRef.current = true;
		triggerRef.current?.focus();

		const ownerWindow = triggerRef.current?.ownerDocument.defaultView;
		ownerWindow?.setTimeout( () => {
			shouldSuppressNextFocusOpenRef.current = false;
		}, 0 );
	};

	const handleTriggerFocus = () => {
		if ( shouldSuppressNextFocusOpenRef.current ) {
			return;
		}

		setIsTooltipOpen( true );
	};

	useEffect( () => {
		if ( ! isTooltipOpen || ! wrapperRef.current ) {
			return;
		}

		const wrapper = wrapperRef.current;
		const ownerDocument = wrapper.ownerDocument;
		const handleKeyDown = ( event: KeyboardEvent ) => {
			if (
				event.key === 'Escape' &&
				ownerDocument.activeElement &&
				wrapper.contains( ownerDocument.activeElement )
			) {
				event.stopPropagation();
				setIsTooltipOpen( false );
				restoreFocusToTrigger();
			}
		};
		const handleFocusIn = ( event: FocusEvent ) => {
			if (
				event.target instanceof Node &&
				! wrapper.contains( event.target )
			) {
				setIsTooltipOpen( false );
			}
		};

		ownerDocument.addEventListener( 'keydown', handleKeyDown, true );
		ownerDocument.addEventListener( 'focusin', handleFocusIn );

		return () => {
			ownerDocument.removeEventListener( 'keydown', handleKeyDown, true );
			ownerDocument.removeEventListener( 'focusin', handleFocusIn );
		};
	}, [ isTooltipOpen ] );
	const handleEscape = ( event: React.KeyboardEvent< HTMLElement > ) => {
		if ( event.key !== 'Escape' ) {
			return;
		}

		event.stopPropagation();
		setIsTooltipOpen( false );
		restoreFocusToTrigger();
	};

	if ( ! promotion ) {
		return null;
	}

	const badgeType = promotion.badge_type || 'success';
	const hasTooltip = Boolean( promotion.description || promotion.tc_url );
	const label = sprintf(
		/* translators: %s: Promotion title. */
		__( '%s promotion details', 'woocommerce' ),
		promotion.title
	);
	const labelId = `${ tooltipId }-label`;

	if ( ! hasTooltip ) {
		return (
			<span
				className={ `woopayments-settings-payment-method-item__promotion-badge is-${ badgeType }` }
			>
				{ promotion.title }
			</span>
		);
	}

	return (
		<div
			ref={ wrapperRef }
			className="woopayments-settings-payment-method-item__promotion-wrapper"
		>
			<button
				ref={ triggerRef }
				type="button"
				className={ `woopayments-settings-payment-method-item__promotion-badge is-${ badgeType }` }
				aria-label={ label }
				aria-haspopup="dialog"
				aria-expanded={ isTooltipOpen }
				aria-controls={ tooltipId }
				onClick={ () => setIsTooltipOpen( true ) }
				onFocus={ handleTriggerFocus }
				onKeyDown={ handleEscape }
			>
				{ promotion.title }
				<Icon
					className="woopayments-settings-payment-method-item__promotion-icon"
					icon={ infoIcon }
					size={ 14 }
				/>
			</button>
			{ isTooltipOpen && (
				<div
					id={ tooltipId }
					role="dialog"
					aria-labelledby={ labelId }
					className="woopayments-settings-payment-method-item__promotion-tooltip"
				>
					<span id={ labelId } className="screen-reader-text">
						{ label }
					</span>
					{ promotion.description && (
						<RawHTML>{ promotion.description }</RawHTML>
					) }
					{ promotion.tc_url && (
						<ExternalLink
							href={ promotion.tc_url }
							onKeyDown={ handleEscape }
						>
							{ promotion.tc_label ||
								__( 'See terms', 'woocommerce' ) }
						</ExternalLink>
					) }
				</div>
			) }
		</div>
	);
};

export const DuplicatePaymentMethodNotice = ( {
	paymentMethodId,
	gatewayIds,
	dismissedNotices,
	onDismiss,
	onRestoreFocus,
}: {
	paymentMethodId: string;
	gatewayIds: string[];
	dismissedNotices: DuplicatePaymentMethodNotices;
	onDismiss?: ( notices: Record< string, string[] > ) => void;
	onRestoreFocus: () => void;
} ) => {
	const noticeRef = useRef< HTMLDivElement >( null );
	const dismissedGatewayIds = dismissedNotices[ paymentMethodId ] || [];
	const isDismissedForEveryGateway = gatewayIds.every( ( gatewayId ) =>
		dismissedGatewayIds.includes( gatewayId )
	);

	if ( isDismissedForEveryGateway ) {
		return null;
	}

	const dismissedNoticeEntries = Object.entries( dismissedNotices ).filter(
		( entry ): entry is [ string, string[] ] => Array.isArray( entry[ 1 ] )
	);

	return (
		<div ref={ noticeRef }>
			<Notice
				status="warning"
				isDismissible={ Boolean( onDismiss ) }
				onRemove={ () => {
					if ( ! onDismiss ) {
						return;
					}

					const activeElement =
						noticeRef.current?.ownerDocument.activeElement;
					const shouldRestoreFocus = activeElement
						? noticeRef.current?.contains( activeElement )
						: false;
					onDismiss( {
						...Object.fromEntries( dismissedNoticeEntries ),
						[ paymentMethodId ]: Array.from(
							new Set( [ ...dismissedGatewayIds, ...gatewayIds ] )
						),
					} );
					if ( shouldRestoreFocus ) {
						onRestoreFocus();
					}
				} }
				className="woopayments-settings-payment-method-item__duplicate-notice"
			>
				<span>
					{ __(
						'This payment method is enabled by other extensions.',
						'woocommerce'
					) }{ ' ' }
					<a href="admin.php?page=wc-settings&tab=checkout">
						{ __( 'Review extensions', 'woocommerce' ) }
					</a>{ ' ' }
					{ __(
						'to improve the shopper experience.',
						'woocommerce'
					) }
				</span>
			</Notice>
		</div>
	);
};

export const getPaymentMethodAvailability = (
	definition: WooPaymentsPaymentMethodDefinition,
	status: PaymentMethodStatus,
	isManualCaptureEnabled: boolean
): Availability => {
	switch ( status.status ) {
		case 'inactive':
			return {
				isActionable: false,
				chip: __( 'More information needed', 'woocommerce' ),
				notice: (
					<>
						{ __(
							'More information is needed to finish setting up this payment method.',
							'woocommerce'
						) }{ ' ' }
						<ExternalLink href="https://woocommerce.com/document/woopayments/payment-methods/additional-payment-methods/#method-cant-be-enabled">
							{ __( 'Learn more', 'woocommerce' ) }
						</ExternalLink>
					</>
				),
				noticeStatus: 'warning',
			};
		case 'pending':
			return {
				isActionable: false,
				chip: __( 'Approval pending', 'woocommerce' ),
				notice: __(
					"This payment method is pending approval. It won't be available at checkout until it's approved.",
					'woocommerce'
				),
				noticeStatus: 'warning',
			};
		case 'pending_verification':
			return {
				isActionable: false,
				chip: __( 'Pending verification', 'woocommerce' ),
				notice: sprintf(
					/* translators: %s: Payment method label. */
					__(
						"%s won't be available at checkout yet. To finish setting it up, review the required steps in Payments overview.",
						'woocommerce'
					),
					definition.label
				),
				noticeStatus: 'warning',
			};
		case 'rejected':
			return {
				isActionable: false,
				chip: __( 'Rejected', 'woocommerce' ),
				chipType: 'error',
				notice: sprintf(
					/* translators: %s: Payment method label. */
					__(
						'Your application to use %s has been rejected. Need help? Contact support',
						'woocommerce'
					),
					definition.label
				),
				noticeStatus: 'error',
			};
	}

	if ( isManualCaptureEnabled && ! definition.allowsManualCapture ) {
		return {
			isActionable: false,
			chip: __( 'Unavailable with manual capture', 'woocommerce' ),
		};
	}

	return { isActionable: true };
};

const PaymentMethodActivationModal = ( {
	definition,
	requirements,
	onClose,
	onConfirm,
}: {
	definition: WooPaymentsPaymentMethodDefinition;
	requirements: unknown[];
	onClose: () => void;
	onConfirm: () => void;
} ) => {
	const requirementLabels = getRequirementLabels( requirements );

	return (
		<Modal
			title={ sprintf(
				/* translators: %s: Payment method label. */
				__( 'One more step to enable %s', 'woocommerce' ),
				definition.label
			) }
			onRequestClose={ onClose }
			shouldCloseOnClickOutside={ false }
			className="woopayments-settings-payment-method-activation-modal"
		>
			<div className="woopayments-settings-payment-method-activation-modal__body">
				<PaymentMethodIcon definition={ definition } />
				{ requirementLabels.length > 0 ? (
					<>
						<p>
							{ sprintf(
								/* translators: %s: Payment method label. */
								__(
									'You need to provide more information to enable %s on your checkout:',
									'woocommerce'
								),
								definition.label
							) }
						</p>
						<ul>
							{ requirementLabels.map( ( requirement ) => (
								<li key={ requirement }>{ requirement }</li>
							) ) }
						</ul>
					</>
				) : (
					<p>
						{ sprintf(
							/* translators: %s: Payment method label. */
							__(
								'You need to provide more information to enable %s on your checkout.',
								'woocommerce'
							),
							definition.label
						) }
					</p>
				) }
				<p>
					{ __(
						'If you choose to continue, our payment partner Stripe will collect the required information.',
						'woocommerce'
					) }
				</p>
			</div>
			<div className="woopayments-settings-modal__actions">
				<Button variant="secondary" onClick={ onClose }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button variant="primary" onClick={ onConfirm }>
					{ __( 'Continue', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
};

const PaymentMethodRow = ( {
	definition,
	enabledMethodIds,
	statuses,
	accountFees,
	pmPromotions,
	duplicatedPaymentMethodIds,
	dismissedDuplicatePaymentMethodNotices,
	isManualCaptureEnabled,
	onEnable,
	onDisable,
	onDismissDuplicateNotice,
}: {
	definition: WooPaymentsPaymentMethodDefinition;
	enabledMethodIds: string[];
	statuses: Record< string, PaymentMethodStatus | undefined >;
	accountFees?: Record< string, FeeStructure | undefined >;
	pmPromotions?: PmPromotion[];
	duplicatedPaymentMethodIds?: DuplicatePaymentMethodNotices;
	dismissedDuplicatePaymentMethodNotices?: DuplicatePaymentMethodNotices;
	isManualCaptureEnabled: boolean;
	onEnable: ( methodId: string ) => void;
	onDisable: ( methodId: string ) => void;
	onDismissDuplicateNotice?: ( notices: Record< string, string[] > ) => void;
} ) => {
	const [ activationMethodId, setActivationMethodId ] = useState<
		string | null
	>( null );
	const rowRef = useRef< HTMLLIElement >( null );
	const isEnabled = enabledMethodIds.includes( definition.id );
	const isLocked = definition.id === 'card' && isEnabled;
	const status = getStatus( definition, statuses );
	const availability = getPaymentMethodAvailability(
		definition,
		status,
		isManualCaptureEnabled
	);
	const requirements = Array.isArray( status.requirements )
		? status.requirements
		: [];
	const statusId = `woopayments-settings-payment-method-${ definition.id }-status`;
	const descriptionId = `woopayments-settings-payment-method-${ definition.id }-description`;
	const feeTooltipId = `woopayments-settings-payment-method-${ definition.id }-fees`;
	const describedBy = availability.chip
		? `${ descriptionId } ${ statusId }`
		: descriptionId;
	const feeStructure = accountFees?.[ definition.id ];
	const duplicateGatewayIds =
		duplicatedPaymentMethodIds?.[ definition.id ] || [];
	const discountDescriptionId = `woopayments-settings-payment-method-${ definition.id }-discount-description`;
	const promotionTooltipId = `woopayments-settings-payment-method-${ definition.id }-promotion-description`;
	const discountBadgeText = getDiscountBadgeText(
		feeStructure?.discount?.[ 0 ]
	);
	const badgePromotion = discountBadgeText
		? undefined
		: pmPromotions?.find(
				( promotion ) =>
					promotion.payment_method === definition.id &&
					promotion.type === 'badge'
		  );
	const restoreFocusToRow = () => {
		const checkbox = rowRef.current?.querySelector< HTMLInputElement >(
			'input[type="checkbox"]:not(:disabled)'
		);
		if ( checkbox ) {
			checkbox.focus();
			return;
		}

		rowRef.current?.focus();
	};

	const onChange = ( shouldEnable: boolean ) => {
		if ( isLocked || ! availability.isActionable ) {
			return;
		}

		if ( shouldEnable ) {
			if ( status.status === 'unrequested' && requirements.length > 0 ) {
				setActivationMethodId( definition.id );
				return;
			}

			onEnable( definition.id );
			return;
		}

		onDisable( definition.id );
	};

	return (
		<li
			ref={ rowRef }
			className="woopayments-settings-payment-method-item"
			tabIndex={ -1 }
		>
			<div className="woopayments-settings-payment-method-item__main">
				<CheckboxControl
					checked={ isEnabled }
					disabled={ isLocked || ! availability.isActionable }
					aria-describedby={ describedBy }
					label={ definition.label }
					onChange={ ( value ) => onChange( Boolean( value ) ) }
					__nextHasNoMarginBottom
				/>
				<PaymentMethodIcon definition={ definition } />
				<div className="woopayments-settings-payment-method-item__body">
					<div className="woopayments-settings-payment-method-item__heading">
						<h4>{ definition.label }</h4>
						{ isLocked && (
							<span className="woopayments-settings-payment-method-item__required">
								{ __( '(Required)', 'woocommerce' ) }
							</span>
						) }
						{ availability.chip && (
							<span
								id={ statusId }
								className={ `woopayments-settings-payment-method-item__chip ${
									availability.chipType === 'error'
										? 'is-error'
										: ''
								}` }
							>
								{ availability.chip }
							</span>
						) }
						<DiscountBadge
							feeStructure={ feeStructure }
							descriptionId={ discountDescriptionId }
						/>
						<PmPromotionBadge
							promotion={ badgePromotion }
							tooltipId={ promotionTooltipId }
						/>
					</div>
					<p id={ descriptionId }>{ definition.description }</p>
					{ definition.id === 'card' && <CardBrandLogos /> }
				</div>
				<div className="woopayments-settings-payment-method-item__actions">
					<FeeDetails
						feeStructure={ feeStructure }
						tooltipId={ feeTooltipId }
					/>
				</div>
			</div>
			{ duplicateGatewayIds.length > 0 && ! availability.notice && (
				<DuplicatePaymentMethodNotice
					paymentMethodId={ definition.id }
					gatewayIds={ duplicateGatewayIds }
					dismissedNotices={
						dismissedDuplicatePaymentMethodNotices || {}
					}
					onDismiss={ onDismissDuplicateNotice }
					onRestoreFocus={ restoreFocusToRow }
				/>
			) }
			{ availability.notice && (
				<Notice
					status={ availability.noticeStatus || 'warning' }
					isDismissible={ false }
					className="woopayments-settings-payment-method-item__notice"
				>
					{ availability.notice }
				</Notice>
			) }
			{ activationMethodId === definition.id && (
				<PaymentMethodActivationModal
					definition={ definition }
					requirements={ requirements }
					onClose={ () => setActivationMethodId( null ) }
					onConfirm={ () => {
						onEnable( definition.id );
						setActivationMethodId( null );
					} }
				/>
			) }
		</li>
	);
};

export const WooPaymentsPaymentMethodsList = ( {
	methodIds,
	enabledMethodIds,
	statuses,
	accountFees,
	pmPromotions,
	duplicatedPaymentMethodIds,
	dismissedDuplicatePaymentMethodNotices,
	isManualCaptureEnabled,
	accountCountry,
	onEnable,
	onDisable,
	onDismissDuplicateNotice,
}: PaymentMethodsListProps ) => {
	const definitions = methodIds
		.map( ( methodId ) =>
			getPaymentMethodDefinition( methodId, accountCountry )
		)
		.filter(
			( definition ): definition is WooPaymentsPaymentMethodDefinition =>
				Boolean( definition )
		)
		.sort( ( first, second ) => {
			if ( first.id === 'card' ) {
				return -1;
			}
			if ( second.id === 'card' ) {
				return 1;
			}
			return 0;
		} );

	if ( definitions.length === 0 ) {
		return (
			<p className="woopayments-settings-muted">
				{ __(
					'No additional checkout payment methods are available for this account.',
					'woocommerce'
				) }
			</p>
		);
	}

	return (
		<ul className="woopayments-settings-payment-methods-list">
			{ definitions.map( ( definition ) => (
				<PaymentMethodRow
					key={ definition.id }
					definition={ definition }
					enabledMethodIds={ enabledMethodIds }
					statuses={ statuses }
					accountFees={ accountFees }
					pmPromotions={ pmPromotions }
					duplicatedPaymentMethodIds={ duplicatedPaymentMethodIds }
					dismissedDuplicatePaymentMethodNotices={
						dismissedDuplicatePaymentMethodNotices
					}
					isManualCaptureEnabled={ isManualCaptureEnabled }
					onEnable={ onEnable }
					onDisable={ onDisable }
					onDismissDuplicateNotice={ onDismissDuplicateNotice }
				/>
			) ) }
		</ul>
	);
};
