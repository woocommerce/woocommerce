/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import type { ReactNode } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';

/**
 * Internal dependencies
 */
import {
	getWooPaymentsCharge,
	getWooPaymentsPaymentIntent,
	getWooPaymentsTimeline,
	getWooPaymentsTransaction,
} from './data';
import type {
	WooPaymentsCharge,
	WooPaymentsPaymentIntent,
	WooPaymentsTimelineEvent,
	WooPaymentsTransaction,
} from './types';
import {
	formatAmount,
	formatDate,
	formatLabel,
	getErrorMessage,
} from './utils';
import { WooPaymentsTransactionDisputeDetails } from './transaction-dispute-details';
import { LiveStatusMessage, StatusMessage } from './table';
import '../style.scss';

const isPaymentIntentId = ( id: string ) => id.startsWith( 'pi_' );

const isChargeId = ( id: string ) =>
	id.startsWith( 'ch_' ) || id.startsWith( 'py_' );

const isTransactionId = ( id: string ) => id.startsWith( 'txn_' );

const getBalanceTransactionId = (
	balanceTransaction?: WooPaymentsCharge[ 'balance_transaction' ]
) => {
	if ( typeof balanceTransaction === 'string' ) {
		return balanceTransaction;
	}

	return balanceTransaction?.id || '';
};

const getBalanceTransactionAmount = (
	balanceTransaction: WooPaymentsCharge[ 'balance_transaction' ],
	key: 'fee' | 'net'
) => {
	if (
		balanceTransaction &&
		typeof balanceTransaction === 'object' &&
		typeof balanceTransaction[ key ] === 'number'
	) {
		return balanceTransaction[ key ];
	}

	return undefined;
};

const getIntentCharge = ( intent: WooPaymentsPaymentIntent ) =>
	intent.charge || intent.charges?.data?.[ 0 ] || {};

const DetailRow = ( { label, value }: { label: string; value: ReactNode } ) => (
	<div>
		<dt>{ label }</dt>
		<dd>{ value }</dd>
	</div>
);

const hasDisplayValue = ( value: unknown ) =>
	value !== undefined && value !== null && value !== '';

const getPaymentIntentId = (
	transaction: WooPaymentsTransaction,
	fallbackId: string
) =>
	transaction.payment_intent_id ||
	( fallbackId.startsWith( 'pi_' ) ? fallbackId : '' );

const getPaymentMethodLabel = ( transaction: WooPaymentsTransaction ) => {
	const method = transaction.payment_method_details;

	if ( method?.type === 'card' && method.card?.brand && method.card.last4 ) {
		return sprintf(
			/* translators: 1: card brand, 2: last four card digits. */
			__( '%1$s ending in %2$s', 'woocommerce' ),
			formatLabel( method.card.brand ),
			method.card.last4
		);
	}

	return method?.type ? formatLabel( method.type ) : '';
};

const getOrderLabel = ( transaction: WooPaymentsTransaction ) => {
	const orderId = transaction.order?.number || transaction.order?.id;

	return orderId
		? sprintf(
				/* translators: %s: order number. */
				__( 'Order #%s', 'woocommerce' ),
				orderId
		  )
		: '';
};

const getTimelineUserName = ( event: WooPaymentsTimelineEvent ) =>
	typeof event.user?.username === 'string' ? event.user.username : '';

const getTimelineMessage = ( event: WooPaymentsTimelineEvent ) => {
	if ( event.message ) {
		return event.message;
	}

	const userName = getTimelineUserName( event );
	if ( event.type === 'fraud_outcome_manual_approve' ) {
		return userName
			? sprintf(
					/* translators: %s: user display name. */
					__( 'Payment was approved by %s', 'woocommerce' ),
					userName
			  )
			: __( 'Payment was approved.', 'woocommerce' );
	}

	if ( event.type === 'fraud_outcome_manual_block' ) {
		return userName
			? sprintf(
					/* translators: %s: user display name. */
					__( 'Payment was blocked by %s', 'woocommerce' ),
					userName
			  )
			: __( 'Payment was blocked.', 'woocommerce' );
	}

	return formatLabel( event.type );
};

const getTimelineDate = ( event: WooPaymentsTimelineEvent ) =>
	event.datetime || event.created;

const getTimelineId = (
	transaction: WooPaymentsTransaction,
	routeId: string
) => {
	const orderId = transaction.order?.id;

	return (
		getPaymentIntentId( transaction, routeId ) || String( orderId || '' )
	);
};

const normalizeCharge = (
	charge: WooPaymentsCharge,
	fallbackId: string,
	transactionId: string
): WooPaymentsTransaction => {
	const balanceTransactionId = getBalanceTransactionId(
		charge.balance_transaction
	);

	return {
		id: transactionId || balanceTransactionId || charge.id || fallbackId,
		transaction_id: transactionId || balanceTransactionId,
		charge_id: charge.id,
		type: charge.type || 'charge',
		amount: charge.amount,
		currency: charge.currency,
		created: charge.created,
		date: charge.date,
		payment_intent_id: charge.payment_intent,
		customer_name: charge.billing_details?.name,
		customer_email: charge.billing_details?.email,
		order: charge.order,
		payment_method_details: charge.payment_method_details,
		outcome: charge.outcome,
		dispute: charge.dispute,
		balance_transaction: charge.balance_transaction,
		application_fee_amount: charge.application_fee_amount,
		amount_refunded: charge.amount_refunded,
		refunded: charge.refunded,
		captured: charge.captured,
		fee: getBalanceTransactionAmount( charge.balance_transaction, 'fee' ),
		net: getBalanceTransactionAmount( charge.balance_transaction, 'net' ),
		status: charge.status,
	};
};

const normalizePaymentIntent = (
	intent: WooPaymentsPaymentIntent,
	fallbackId: string,
	transactionId: string
): WooPaymentsTransaction => {
	const transaction = normalizeCharge(
		getIntentCharge( intent ),
		fallbackId,
		transactionId
	);

	return {
		...transaction,
		id: transaction.id || intent.id || fallbackId,
		type: transaction.type || 'charge',
		amount: transaction.amount ?? intent.amount,
		currency: transaction.currency || intent.currency,
		created: transaction.created || intent.created,
		payment_intent_id: intent.id,
		order: transaction.order || intent.order,
		dispute: transaction.dispute || intent.dispute,
		status: transaction.status || intent.status,
	};
};

export const WooPaymentsTransactionDetailsPage = () => {
	const [ transaction, setTransaction ] =
		useState< WooPaymentsTransaction | null >( null );
	const [ timelineEvents, setTimelineEvents ] = useState<
		WooPaymentsTimelineEvent[]
	>( [] );
	const [ timelineErrorMessage, setTimelineErrorMessage ] = useState<
		string | null
	>( null );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const location = useLocation();
	const navigate = useNavigate();
	const query = new URLSearchParams( location.search );
	const id = query.get( 'id' ) || '';
	const transactionId = query.get( 'transaction_id' ) || '';

	useEffect( () => {
		let isMounted = true;

		const loadTransaction = async () => {
			if ( ! id && ! transactionId ) {
				setErrorMessage(
					__( 'A transaction ID is required.', 'woocommerce' )
				);
				setIsLoading( false );
				return;
			}

			try {
				let nextTransaction: WooPaymentsTransaction;
				let nextTimelineEvents: WooPaymentsTimelineEvent[] = [];
				let nextTimelineErrorMessage: string | null = null;

				if ( isPaymentIntentId( id ) ) {
					nextTransaction = normalizePaymentIntent(
						await getWooPaymentsPaymentIntent( id ),
						id,
						transactionId
					);
				} else if ( isChargeId( id ) ) {
					const charge = await getWooPaymentsCharge( id );
					nextTransaction = normalizeCharge(
						charge,
						id,
						transactionId
					);

					if ( charge.payment_intent ) {
						const nextQuery = new URLSearchParams(
							location.search
						);
						nextQuery.set( 'id', charge.payment_intent );
						navigate(
							{
								pathname: location.pathname,
								search: `?${ nextQuery.toString() }`,
							},
							{ replace: true }
						);
					}
				} else {
					nextTransaction = await getWooPaymentsTransaction(
						isTransactionId( id ) ? id : transactionId || id
					);
				}

				const timelineId = getTimelineId( nextTransaction, id );
				if ( timelineId ) {
					try {
						const timeline = await getWooPaymentsTimeline(
							timelineId
						);
						nextTimelineEvents = timeline?.data || [];
					} catch ( timelineError ) {
						nextTimelineErrorMessage = getErrorMessage(
							timelineError,
							__(
								'Unable to load WooPayments payment timeline.',
								'woocommerce'
							)
						);
					}
				}

				if ( isMounted ) {
					setTransaction( nextTransaction );
					setTimelineEvents( nextTimelineEvents );
					setTimelineErrorMessage( nextTimelineErrorMessage );
					setErrorMessage( null );
				}
			} catch ( error ) {
				if ( isMounted ) {
					setTimelineEvents( [] );
					setTimelineErrorMessage( null );
					setErrorMessage(
						getErrorMessage(
							error,
							__(
								'Unable to load WooPayments transaction details.',
								'woocommerce'
							)
						)
					);
				}
			} finally {
				if ( isMounted ) {
					setIsLoading( false );
				}
			}
		};

		loadTransaction();

		return () => {
			isMounted = false;
		};
	}, [ id, location.pathname, location.search, navigate, transactionId ] );

	const loadingMessage = __( 'Loading transaction details…', 'woocommerce' );
	let liveStatusMessage = __( 'Transaction details loaded.', 'woocommerce' );

	if ( errorMessage ) {
		liveStatusMessage = errorMessage;
	} else if ( timelineErrorMessage ) {
		liveStatusMessage = timelineErrorMessage;
	} else if ( isLoading ) {
		liveStatusMessage = loadingMessage;
	}

	const paymentIntentId = transaction
		? getPaymentIntentId( transaction, id )
		: '';
	const chargeId = transaction?.charge_id || '';
	const transactionResourceId =
		transaction?.transaction_id || transaction?.id || transactionId || id;
	const paymentMethodLabel = transaction
		? getPaymentMethodLabel( transaction )
		: '';
	const orderLabel = transaction ? getOrderLabel( transaction ) : '';
	const hasPaymentDetails = !! ( paymentIntentId || chargeId );

	return (
		<section
			className="woocommerce-woopayments-money-movement"
			aria-busy={ isLoading }
		>
			<h2>
				{ hasPaymentDetails
					? __( 'Payment details', 'woocommerce' )
					: __( 'Transaction details', 'woocommerce' ) }
			</h2>
			<LiveStatusMessage isError={ !! errorMessage }>
				{ liveStatusMessage }
			</LiveStatusMessage>
			{ isLoading && <StatusMessage>{ loadingMessage }</StatusMessage> }
			{ errorMessage && (
				<StatusMessage isError>{ errorMessage }</StatusMessage>
			) }
			{ transaction && ! errorMessage && (
				<>
					<dl className="woocommerce-woopayments-money-movement__details">
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
							value={ formatLabel( transaction.type ) }
						/>
						<DetailRow
							label={ __( 'Date', 'woocommerce' ) }
							value={ formatDate(
								transaction.date || transaction.created
							) }
						/>
						{ hasDisplayValue( transaction.status ) && (
							<DetailRow
								label={ __( 'Status', 'woocommerce' ) }
								value={ formatLabel( transaction.status ) }
							/>
						) }
						<DetailRow
							label={ __( 'Amount', 'woocommerce' ) }
							value={ formatAmount(
								transaction.amount,
								transaction.currency
							) }
						/>
						{ hasDisplayValue( transaction.customer_name ) ||
						hasDisplayValue( transaction.customer_email ) ? (
							<DetailRow
								label={ __( 'Customer', 'woocommerce' ) }
								value={
									<span className="woocommerce-woopayments-money-movement__stacked-value">
										{ transaction.customer_name && (
											<span>
												{ transaction.customer_name }
											</span>
										) }
										{ transaction.customer_email && (
											<span>
												{ transaction.customer_email }
											</span>
										) }
									</span>
								}
							/>
						) : null }
						{ orderLabel && (
							<DetailRow
								label={ __( 'Order', 'woocommerce' ) }
								value={ orderLabel }
							/>
						) }
						{ paymentMethodLabel && (
							<DetailRow
								label={ __( 'Payment method', 'woocommerce' ) }
								value={ paymentMethodLabel }
							/>
						) }
						{ transaction.outcome?.risk_level && (
							<DetailRow
								label={ __( 'Risk evaluation', 'woocommerce' ) }
								value={ formatLabel(
									transaction.outcome.risk_level
								) }
							/>
						) }
						{ hasDisplayValue( transaction.fee ) && (
							<DetailRow
								label={ __( 'Fee', 'woocommerce' ) }
								value={ formatAmount(
									transaction.fee,
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
					{ transaction.dispute && (
						<WooPaymentsTransactionDisputeDetails
							transaction={ transaction }
						/>
					) }
					{ timelineErrorMessage && (
						<StatusMessage isError>
							{ timelineErrorMessage }
						</StatusMessage>
					) }
					{ timelineEvents.length > 0 && (
						<section className="woocommerce-woopayments-overview-card">
							<h3>{ __( 'Timeline', 'woocommerce' ) }</h3>
							<ol className="woocommerce-woopayments-money-movement__timeline">
								{ timelineEvents.map( ( event, index ) => (
									<li
										key={ `${ event.type || 'event' }-${
											getTimelineDate( event ) || index
										}` }
									>
										<span>
											{ getTimelineMessage( event ) }
										</span>
										{ getTimelineDate( event ) && (
											<time>
												{ formatDate(
													getTimelineDate( event )
												) }
											</time>
										) }
									</li>
								) ) }
							</ol>
						</section>
					) }
				</>
			) }
		</section>
	);
};
