/**
 * External dependencies
 */
import {
	Button,
	CheckboxControl,
	ExternalLink,
	Modal,
	Notice,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	CARD_BRANDS,
	getPaymentMethodDefinition,
	WooPaymentsPaymentMethodDefinition,
} from './payment-method-definitions';

type PaymentMethodStatus = {
	status?: string;
	requirements?: unknown[];
};

type PaymentMethodsListProps = {
	methodIds: string[];
	enabledMethodIds: string[];
	statuses: Record< string, PaymentMethodStatus | undefined >;
	isManualCaptureEnabled: boolean;
	accountCountry?: string;
	onEnable: ( methodId: string ) => void;
	onDisable: ( methodId: string ) => void;
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

const getAvailability = (
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
	isManualCaptureEnabled,
	onEnable,
	onDisable,
}: {
	definition: WooPaymentsPaymentMethodDefinition;
	enabledMethodIds: string[];
	statuses: Record< string, PaymentMethodStatus | undefined >;
	isManualCaptureEnabled: boolean;
	onEnable: ( methodId: string ) => void;
	onDisable: ( methodId: string ) => void;
} ) => {
	const [ activationMethodId, setActivationMethodId ] = useState<
		string | null
	>( null );
	const isEnabled = enabledMethodIds.includes( definition.id );
	const isLocked = definition.id === 'card' && isEnabled;
	const status = getStatus( definition, statuses );
	const availability = getAvailability(
		definition,
		status,
		isManualCaptureEnabled
	);
	const requirements = Array.isArray( status.requirements )
		? status.requirements
		: [];
	const statusId = `woopayments-settings-payment-method-${ definition.id }-status`;
	const descriptionId = `woopayments-settings-payment-method-${ definition.id }-description`;
	const describedBy = availability.chip
		? `${ descriptionId } ${ statusId }`
		: descriptionId;

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
		<li className="woopayments-settings-payment-method-item">
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
					</div>
					<p id={ descriptionId }>{ definition.description }</p>
					{ definition.id === 'card' && <CardBrandLogos /> }
				</div>
			</div>
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
	isManualCaptureEnabled,
	accountCountry,
	onEnable,
	onDisable,
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
					isManualCaptureEnabled={ isManualCaptureEnabled }
					onEnable={ onEnable }
					onDisable={ onDisable }
				/>
			) ) }
		</ul>
	);
};
