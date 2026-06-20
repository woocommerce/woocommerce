/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import type {
	WooPaymentsBillingDetails,
	WooPaymentsPaymentMethodDetails,
	WooPaymentsPaymentOrder,
	WooPaymentsTransaction,
} from './types';
import {
	formatAmount,
	formatDateTime,
	formatLabel,
	getChargeChannelLabel,
} from './utils';

type CardDetails = NonNullable< WooPaymentsPaymentMethodDetails[ 'card' ] >;
type CountryMap = Record< string, string >;

const hasDisplayValue = ( value: unknown ) =>
	value !== undefined && value !== null && value !== '';

const DetailRow = ( { label, value }: { label: string; value: ReactNode } ) => (
	<div>
		<dt>{ label }</dt>
		<dd>{ value }</dd>
	</div>
);

const Dash = () => (
	<span aria-label={ __( 'Unavailable', 'woocommerce' ) }>-</span>
);

const LinkedValue = ( {
	href,
	children,
}: {
	href?: string | null;
	children: ReactNode;
} ) => ( href ? <a href={ href }>{ children }</a> : <>{ children }</> );

const getOrderNumber = ( order?: WooPaymentsPaymentOrder ) =>
	order?.number || order?.id || '';

export const hasPaymentOrderContext = ( transaction: WooPaymentsTransaction ) =>
	!! (
		transaction.order &&
		( transaction.order.id ||
			transaction.order.number ||
			transaction.order.url )
	);

const getRecordReferenceLabel = (
	recordType: string,
	recordNumber: string | number
) => {
	const normalizedNumber = String( recordNumber ).replace( /^#/, '' );

	return sprintf(
		/* translators: 1: record type, such as "Order"; 2: record number. */
		__( '%1$s #%2$s', 'woocommerce' ),
		recordType,
		normalizedNumber
	);
};

const getCustomerName = ( transaction: WooPaymentsTransaction ) =>
	transaction.order?.customer_name ||
	transaction.customer_name ||
	transaction.billing_details?.name ||
	'';

const getCustomerEmail = ( transaction: WooPaymentsTransaction ) =>
	transaction.order?.customer_email ||
	transaction.customer_email ||
	transaction.billing_details?.email ||
	'';

const CustomerSummary = ( {
	transaction,
}: {
	transaction: WooPaymentsTransaction;
} ) => {
	const customerName = getCustomerName( transaction );
	const customerEmail = getCustomerEmail( transaction );

	if ( ! customerName && ! customerEmail ) {
		return <Dash />;
	}

	return (
		<span className="woocommerce-woopayments-money-movement__stacked-value">
			{ customerName && (
				<LinkedValue href={ transaction.order?.customer_url }>
					{ customerName }
				</LinkedValue>
			) }
			{ customerEmail && <span>{ customerEmail }</span> }
		</span>
	);
};

const OrderSummary = ( {
	order,
	recordType = __( 'Order', 'woocommerce' ),
}: {
	order?: WooPaymentsPaymentOrder;
	recordType?: string;
} ) => {
	const orderNumber = getOrderNumber( order );

	if ( ! orderNumber ) {
		return <Dash />;
	}

	return (
		<LinkedValue href={ order?.url }>
			{ getRecordReferenceLabel( recordType, orderNumber ) }
		</LinkedValue>
	);
};

const SubscriptionsSummary = ( {
	subscriptions,
}: {
	subscriptions?: WooPaymentsPaymentOrder[];
} ) => {
	if ( ! subscriptions?.length ) {
		return null;
	}

	return (
		<>
			{ subscriptions.map( ( subscription, index ) => (
				<span key={ `${ subscription.url || '' }-${ index }` }>
					<OrderSummary
						order={ subscription }
						recordType={ __( 'Subscription', 'woocommerce' ) }
					/>
					{ index < subscriptions.length - 1 ? ', ' : '' }
				</span>
			) ) }
		</>
	);
};

const getPaymentMethodCardDetails = (
	method: WooPaymentsPaymentMethodDetails
): CardDetails | undefined => {
	const methodType = method.type;
	const typedDetails =
		methodType &&
		method[ methodType ] &&
		typeof method[ methodType ] === 'object'
			? ( method[ methodType ] as CardDetails )
			: undefined;

	return typedDetails || method.card;
};

export const getPaymentMethodLabel = (
	transaction: WooPaymentsTransaction
) => {
	const method = transaction.payment_method_details;
	const card = method ? getPaymentMethodCardDetails( method ) : undefined;

	if ( method?.type === 'card' && card?.brand && card.last4 ) {
		return sprintf(
			/* translators: 1: card brand, 2: last four card digits. */
			__( '%1$s ending in %2$s', 'woocommerce' ),
			formatLabel( card.brand ),
			card.last4
		);
	}

	if (
		( method?.type === 'card_present' ||
			method?.type === 'interac_present' ) &&
		card?.last4
	) {
		return sprintf(
			/* translators: %s: last four card digits. */
			__( 'Card ending in %s', 'woocommerce' ),
			card.last4
		);
	}

	return method?.type ? formatLabel( method.type ) : '';
};

const isCardPaymentMethodType = ( type?: string ) =>
	type === 'card' || type === 'card_present' || type === 'interac_present';

const getPaymentMethodTypeLabel = ( card?: CardDetails ) => {
	const brand = card?.network || card?.brand;
	const funding = card?.funding;

	if ( ! brand && ! funding ) {
		return <Dash />;
	}

	const brandLabel = brand
		? formatLabel( brand )
		: __( 'Card', 'woocommerce' );

	if ( ! funding ) {
		return brandLabel;
	}

	return sprintf(
		/* translators: 1: card brand, 2: card funding type. */
		__( '%1$s %2$s card', 'woocommerce' ),
		brandLabel,
		String( funding ).toLowerCase()
	);
};

const getCountryName = ( countryCode?: string, countries: CountryMap = {} ) => {
	if ( ! countryCode ) {
		return '';
	}

	return countries[ countryCode ] || countryCode;
};

const getCheckLabel = ( check?: string ) => {
	if ( check === 'pass' ) {
		return __( 'Passed', 'woocommerce' );
	}

	if ( check === 'fail' ) {
		return __( 'Failed', 'woocommerce' );
	}

	return __( 'Unavailable', 'woocommerce' );
};

const stripTags = ( value: string ) => value.replace( /<[^>]*>/g, '' );

const getAddressLines = (
	billingDetails?: WooPaymentsBillingDetails,
	countries: CountryMap = {}
) => {
	const address = billingDetails?.address;
	if ( address ) {
		const lines = [
			[ address.line1, address.line2 ].filter( Boolean ).join( ', ' ),
			[ address.city, address.state, address.postal_code ]
				.filter( Boolean )
				.join( ', ' ),
			getCountryName( address.country, countries ),
		].filter( Boolean );

		if ( lines.length ) {
			return lines;
		}
	}

	if ( ! billingDetails?.formatted_address ) {
		return [];
	}

	return billingDetails.formatted_address
		.split( /<br\s*\/?>/i )
		.map( stripTags )
		.map( ( line ) => line.trim() )
		.filter( Boolean );
};

const StackedLines = ( { lines }: { lines: ReactNode[] } ) => {
	if ( ! lines.length ) {
		return <Dash />;
	}

	return (
		<span className="woocommerce-woopayments-money-movement__stacked-value">
			{ lines.map( ( line, index ) => (
				<span key={ index }>{ line }</span>
			) ) }
		</span>
	);
};

const getTransactionFee = ( transaction: WooPaymentsTransaction ) =>
	transaction.fee ?? transaction.application_fee_amount;

export const WooPaymentsPaymentSummarySection = ( {
	transaction,
}: {
	transaction: WooPaymentsTransaction;
} ) => {
	const paymentMethodLabel = getPaymentMethodLabel( transaction );
	const fee = getTransactionFee( transaction );
	const hasRefundedAmount =
		typeof transaction.amount_refunded === 'number' &&
		transaction.amount_refunded > 0;

	return (
		<section
			className="woocommerce-woopayments-overview-card woocommerce-woopayments-money-movement__summary-card"
			aria-labelledby="woocommerce-woopayments-payment-summary-heading"
		>
			<div className="woocommerce-woopayments-money-movement__summary-header">
				<div>
					<h3 id="woocommerce-woopayments-payment-summary-heading">
						{ __( 'Summary', 'woocommerce' ) }
					</h3>
					<p className="woocommerce-woopayments-money-movement__summary-amount">
						{ formatAmount(
							transaction.amount,
							transaction.currency
						) }
						{ transaction.currency && (
							<span className="woocommerce-woopayments-money-movement__summary-currency">
								{ transaction.currency.toUpperCase() }
							</span>
						) }
					</p>
				</div>
				{ hasDisplayValue( transaction.status ) && (
					<span className="woocommerce-woopayments-money-movement__status-chip">
						{ formatLabel( transaction.status ) }
					</span>
				) }
			</div>
			<div className="woocommerce-woopayments-money-movement__summary-breakdown">
				{ hasRefundedAmount && (
					<span>
						{ sprintf(
							/* translators: %s: formatted refunded amount. */
							__( 'Refunded: %s', 'woocommerce' ),
							formatAmount(
								-Math.abs(
									Number( transaction.amount_refunded )
								),
								transaction.currency
							)
						) }
					</span>
				) }
				{ hasDisplayValue( fee ) && (
					<span>
						{ sprintf(
							/* translators: %s: formatted fee amount. */
							__( 'Fees: %s', 'woocommerce' ),
							formatAmount(
								-Math.abs( Number( fee ) ),
								transaction.currency
							)
						) }
					</span>
				) }
				{ hasDisplayValue( transaction.net ) && (
					<span>
						{ sprintf(
							/* translators: %s: formatted net amount. */
							__( 'Net: %s', 'woocommerce' ),
							formatAmount(
								transaction.net,
								transaction.currency
							)
						) }
					</span>
				) }
			</div>
			<dl className="woocommerce-woopayments-money-movement__summary-list">
				<DetailRow
					label={ __( 'Date', 'woocommerce' ) }
					value={ formatDateTime(
						transaction.date || transaction.created
					) }
				/>
				<DetailRow
					label={ __( 'Sales channel', 'woocommerce' ) }
					value={ getChargeChannelLabel(
						transaction.payment_method_details?.type,
						transaction.metadata || {},
						transaction.sales_channel
					) }
				/>
				<DetailRow
					label={ __( 'Customer', 'woocommerce' ) }
					value={ <CustomerSummary transaction={ transaction } /> }
				/>
				<DetailRow
					label={ __( 'Order', 'woocommerce' ) }
					value={ <OrderSummary order={ transaction.order } /> }
				/>
				{ transaction.order?.subscriptions?.length ? (
					<DetailRow
						label={ __( 'Subscription', 'woocommerce' ) }
						value={
							<SubscriptionsSummary
								subscriptions={
									transaction.order.subscriptions
								}
							/>
						}
					/>
				) : null }
				{ paymentMethodLabel && (
					<DetailRow
						label={ __( 'Payment method', 'woocommerce' ) }
						value={ paymentMethodLabel }
					/>
				) }
				{ transaction.outcome?.risk_level && (
					<DetailRow
						label={ __( 'Risk evaluation', 'woocommerce' ) }
						value={ formatLabel( transaction.outcome.risk_level ) }
					/>
				) }
				{ hasDisplayValue( fee ) && (
					<DetailRow
						label={ __( 'Fee', 'woocommerce' ) }
						value={ formatAmount(
							Number( fee ),
							transaction.currency
						) }
					/>
				) }
				{ hasDisplayValue( transaction.net ) && (
					<DetailRow
						label={ __( 'Net amount', 'woocommerce' ) }
						value={ formatAmount(
							transaction.net,
							transaction.currency
						) }
					/>
				) }
			</dl>
		</section>
	);
};

export const WooPaymentsMissingOrderNotice = ( {
	transaction,
}: {
	transaction: WooPaymentsTransaction;
} ) => {
	if ( hasPaymentOrderContext( transaction ) ) {
		return null;
	}

	return (
		<section className="woocommerce-woopayments-money-movement__notice-card">
			<p>
				{ __(
					'This payment is not linked to a WooCommerce order.',
					'woocommerce'
				) }
			</p>
		</section>
	);
};

export const WooPaymentsPaymentIdentifiersSection = ( {
	paymentIntentId,
	chargeId,
	transactionResourceId,
	type,
}: {
	paymentIntentId: string;
	chargeId: string;
	transactionResourceId: string;
	type?: string;
} ) => (
	<section
		className="woocommerce-woopayments-overview-card"
		aria-labelledby="woocommerce-woopayments-payment-identifiers-heading"
	>
		<h3 id="woocommerce-woopayments-payment-identifiers-heading">
			{ __( 'Identifiers', 'woocommerce' ) }
		</h3>
		<dl className="woocommerce-woopayments-money-movement__details woocommerce-woopayments-money-movement__details--plain">
			{ paymentIntentId && (
				<DetailRow
					label={ __( 'Payment ID', 'woocommerce' ) }
					value={ paymentIntentId }
				/>
			) }
			{ chargeId && (
				<DetailRow
					label={ __( 'Charge ID', 'woocommerce' ) }
					value={ chargeId }
				/>
			) }
			<DetailRow
				label={ __( 'Transaction ID', 'woocommerce' ) }
				value={ transactionResourceId }
			/>
			<DetailRow
				label={ __( 'Type', 'woocommerce' ) }
				value={ formatLabel( type ) }
			/>
		</dl>
	</section>
);

export const WooPaymentsPaymentMethodDetailsSection = ( {
	transaction,
	countries = {},
}: {
	transaction: WooPaymentsTransaction;
	countries?: CountryMap;
} ) => {
	const method = transaction.payment_method_details;
	const card = method ? getPaymentMethodCardDetails( method ) : undefined;

	if ( ! method?.type ) {
		return null;
	}

	if ( ! isCardPaymentMethodType( method.type ) ) {
		return (
			<section
				className="woocommerce-woopayments-overview-card woocommerce-woopayments-money-movement__payment-method-card"
				aria-labelledby="woocommerce-woopayments-payment-method-heading"
			>
				<h3 id="woocommerce-woopayments-payment-method-heading">
					{ __( 'Payment method', 'woocommerce' ) }
				</h3>
				<dl className="woocommerce-woopayments-money-movement__details woocommerce-woopayments-money-movement__details--plain">
					<DetailRow
						label={ __( 'Type', 'woocommerce' ) }
						value={ formatLabel( method.type ) }
					/>
					<DetailRow
						label={ __( 'ID', 'woocommerce' ) }
						value={ transaction.payment_method || <Dash /> }
					/>
					<DetailRow
						label={ __( 'Owner', 'woocommerce' ) }
						value={ getCustomerName( transaction ) || <Dash /> }
					/>
					<DetailRow
						label={ __( 'Owner email', 'woocommerce' ) }
						value={ getCustomerEmail( transaction ) || <Dash /> }
					/>
				</dl>
			</section>
		);
	}

	return (
		<section
			className="woocommerce-woopayments-overview-card woocommerce-woopayments-money-movement__payment-method-card"
			aria-labelledby="woocommerce-woopayments-payment-method-heading"
		>
			<h3 id="woocommerce-woopayments-payment-method-heading">
				{ __( 'Payment method', 'woocommerce' ) }
			</h3>
			<dl className="woocommerce-woopayments-money-movement__payment-method-grid">
				<DetailRow
					label={ __( 'Number', 'woocommerce' ) }
					value={ card?.last4 ? `•••• ${ card.last4 }` : <Dash /> }
				/>
				<DetailRow
					label={ __( 'Expires', 'woocommerce' ) }
					value={
						card?.exp_month && card.exp_year ? (
							`${ card.exp_month } / ${ card.exp_year }`
						) : (
							<Dash />
						)
					}
				/>
				<DetailRow
					label={ __( 'Type', 'woocommerce' ) }
					value={ getPaymentMethodTypeLabel( card ) }
				/>
				<DetailRow
					label={ __( 'ID', 'woocommerce' ) }
					value={ transaction.payment_method || <Dash /> }
				/>
				<DetailRow
					label={ __( 'Owner', 'woocommerce' ) }
					value={ getCustomerName( transaction ) || <Dash /> }
				/>
				<DetailRow
					label={ __( 'Owner email', 'woocommerce' ) }
					value={ getCustomerEmail( transaction ) || <Dash /> }
				/>
				<DetailRow
					label={ __( 'Address', 'woocommerce' ) }
					value={
						<StackedLines
							lines={ getAddressLines(
								transaction.billing_details,
								countries
							) }
						/>
					}
				/>
				<DetailRow
					label={ __( 'Origin', 'woocommerce' ) }
					value={
						getCountryName( card?.country, countries ) || <Dash />
					}
				/>
				<DetailRow
					label={ __( 'CVC check', 'woocommerce' ) }
					value={ getCheckLabel( card?.checks?.cvc_check ) }
				/>
				<DetailRow
					label={ __( 'Street check', 'woocommerce' ) }
					value={ getCheckLabel( card?.checks?.address_line1_check ) }
				/>
				<DetailRow
					label={ __( 'Postal code check', 'woocommerce' ) }
					value={ getCheckLabel(
						card?.checks?.address_postal_code_check
					) }
				/>
			</dl>
		</section>
	);
};
