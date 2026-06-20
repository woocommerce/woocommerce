/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { dispatch } from '@wordpress/data';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import type { ReactNode } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';

/**
 * Internal dependencies
 */
import {
	cancelWooPaymentsAuthorization,
	captureWooPaymentsAuthorization,
	getWooPaymentsAuthorization,
	getWooPaymentsCharge,
	getWooPaymentsPaymentIntent,
	getWooPaymentsTimeline,
	getWooPaymentsTransaction,
} from './data';
import type {
	WooPaymentsAuthorization,
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

type AuthorizationAction = 'capture' | 'cancel';
type PendingAuthorizationAction = {
	action: AuthorizationAction;
	paymentIntentId: string;
	routeKey: string;
} | null;
type NoticeDispatch = {
	createSuccessNotice: ( message: string ) => void;
	createErrorNotice: ( message: string ) => void;
};
type LoadTransactionOptions = {
	shouldUpdate?: () => boolean;
	setLoading?: boolean;
};

const AUTHORIZATION_ACTION_FOCUS_SELECTOR =
	'.woocommerce-woopayments-money-movement__authorization-actions, .woocommerce-woopayments-money-movement__authorization-notice';

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

const getNotices = () =>
	dispatch( 'core/notices' ) as unknown as NoticeDispatch;

const getErrorStatus = ( error: unknown ) => {
	if ( ! error || typeof error !== 'object' ) {
		return undefined;
	}

	if ( 'status' in error && typeof error.status === 'number' ) {
		return error.status;
	}

	if ( 'data' in error && error.data && typeof error.data === 'object' ) {
		const data = error.data;
		if ( 'status' in data && typeof data.status === 'number' ) {
			return data.status;
		}
	}

	return undefined;
};

const isNotFoundError = ( error: unknown ) => getErrorStatus( error ) === 404;

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

const getOrderId = (
	transaction: WooPaymentsTransaction,
	authorization: WooPaymentsAuthorization | null
) => {
	const orderId = Number( authorization?.order_id ?? transaction.order?.id );

	return Number.isFinite( orderId ) && orderId > 0 ? orderId : 0;
};

const getOrderFraudMetaBoxType = ( transaction: WooPaymentsTransaction ) => {
	const fraudMetaBoxType = transaction.order?.fraud_meta_box_type;

	return typeof fraudMetaBoxType === 'string' ? fraudMetaBoxType : '';
};

const isFraudReviewTransaction = ( transaction: WooPaymentsTransaction ) =>
	transaction.status === 'requires_capture' &&
	getOrderFraudMetaBoxType( transaction ) === 'review';

const isAuthorizationEligible = (
	transaction: WooPaymentsTransaction,
	paymentIntentId: string
) => {
	if ( ! paymentIntentId || transaction.captured === true ) {
		return false;
	}

	if (
		transaction.refunded ||
		( typeof transaction.amount_refunded === 'number' &&
			transaction.amount_refunded > 0 )
	) {
		return false;
	}

	return (
		transaction.status === 'requires_capture' ||
		transaction.captured === false
	);
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
	const [ authorizationErrorMessage, setAuthorizationErrorMessage ] =
		useState< string | null >( null );
	const [ authorization, setAuthorization ] =
		useState< WooPaymentsAuthorization | null >( null );
	const [ pendingAuthorizationAction, setPendingAuthorizationAction ] =
		useState< PendingAuthorizationAction >( null );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const location = useLocation();
	const navigate = useNavigate();
	const query = new URLSearchParams( location.search );
	const id = query.get( 'id' ) || '';
	const transactionId = query.get( 'transaction_id' ) || '';
	const routeKey = `${ location.pathname }${ location.search }`;
	const routeKeyRef = useRef( routeKey );
	const paymentDetailsHeadingRef = useRef< HTMLHeadingElement | null >(
		null
	);
	const shouldFocusDetailsHeadingRef = useRef( false );

	useEffect( () => {
		routeKeyRef.current = routeKey;
		setPendingAuthorizationAction( null );
	}, [ routeKey ] );

	useEffect( () => {
		if (
			! shouldFocusDetailsHeadingRef.current ||
			pendingAuthorizationAction
		) {
			return;
		}

		shouldFocusDetailsHeadingRef.current = false;
		const heading = paymentDetailsHeadingRef.current;
		const activeElement = heading?.ownerDocument.activeElement;
		const shouldRestoreFocus =
			activeElement === heading?.ownerDocument.body ||
			( activeElement instanceof HTMLElement &&
				!! activeElement.closest(
					AUTHORIZATION_ACTION_FOCUS_SELECTOR
				) );

		if ( shouldRestoreFocus ) {
			heading?.focus();
		}
	}, [
		authorization,
		errorMessage,
		pendingAuthorizationAction,
		transaction,
	] );

	const loadTransaction = useCallback(
		async ( options: LoadTransactionOptions = {} ) => {
			const shouldUpdate = options.shouldUpdate || ( () => true );
			const shouldSetLoading = options.setLoading !== false;

			if ( shouldSetLoading ) {
				setIsLoading( true );
			}

			if ( ! id && ! transactionId ) {
				if ( shouldUpdate() ) {
					setAuthorization( null );
					setAuthorizationErrorMessage( null );
					setErrorMessage(
						__( 'A transaction ID is required.', 'woocommerce' )
					);
					setIsLoading( false );
				}
				return;
			}

			try {
				let nextTransaction: WooPaymentsTransaction;
				let nextTimelineEvents: WooPaymentsTimelineEvent[] = [];
				let nextTimelineErrorMessage: string | null = null;
				let nextAuthorization: WooPaymentsAuthorization | null = null;
				let nextAuthorizationErrorMessage: string | null = null;

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

				const nextPaymentIntentId = getPaymentIntentId(
					nextTransaction,
					id
				);
				if (
					isAuthorizationEligible(
						nextTransaction,
						nextPaymentIntentId
					)
				) {
					try {
						const loadedAuthorization =
							await getWooPaymentsAuthorization(
								nextPaymentIntentId
							);
						nextAuthorization = loadedAuthorization?.captured
							? null
							: loadedAuthorization;
					} catch ( authorizationError ) {
						nextAuthorization = null;
						if ( ! isNotFoundError( authorizationError ) ) {
							nextAuthorizationErrorMessage = getErrorMessage(
								authorizationError,
								__(
									'Unable to load WooPayments authorization details.',
									'woocommerce'
								)
							);
						}
					}
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

				if ( shouldUpdate() ) {
					setTransaction( nextTransaction );
					setAuthorization( nextAuthorization );
					setAuthorizationErrorMessage(
						nextAuthorizationErrorMessage
					);
					setTimelineEvents( nextTimelineEvents );
					setTimelineErrorMessage( nextTimelineErrorMessage );
					setErrorMessage( null );
				}
			} catch ( error ) {
				if ( shouldUpdate() ) {
					setAuthorization( null );
					setAuthorizationErrorMessage( null );
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
				if ( shouldUpdate() && shouldSetLoading ) {
					setIsLoading( false );
				}
			}
		},
		[ id, location.pathname, location.search, navigate, transactionId ]
	);

	useEffect( () => {
		let isMounted = true;

		loadTransaction( {
			shouldUpdate: () => isMounted,
		} );

		return () => {
			isMounted = false;
		};
	}, [ loadTransaction ] );

	const loadingMessage = __( 'Loading transaction details…', 'woocommerce' );
	let liveStatusMessage = __( 'Transaction details loaded.', 'woocommerce' );

	if ( errorMessage ) {
		liveStatusMessage = errorMessage;
	} else if ( authorizationErrorMessage ) {
		liveStatusMessage = authorizationErrorMessage;
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
	const orderId = transaction ? getOrderId( transaction, authorization ) : 0;
	const isFraudReview =
		!! transaction && isFraudReviewTransaction( transaction );
	const showAuthorizationActions =
		!! transaction && !! authorization && !! paymentIntentId && orderId > 0;
	const showFraudReviewActions = showAuthorizationActions && isFraudReview;
	const showCaptureNotice = showAuthorizationActions && ! isFraudReview;
	const pendingAction =
		pendingAuthorizationAction?.routeKey === routeKey &&
		pendingAuthorizationAction?.paymentIntentId === paymentIntentId
			? pendingAuthorizationAction.action
			: null;
	const isAuthorizationActionPending = !! pendingAction;

	const handleAuthorizationAction = async ( action: AuthorizationAction ) => {
		if ( isAuthorizationActionPending ) {
			return;
		}

		if ( ! paymentIntentId || ! orderId ) {
			getNotices().createErrorNotice(
				__(
					'Unable to process this authorization because the order details are incomplete.',
					'woocommerce'
				)
			);
			return;
		}

		const actionRouteKey = routeKey;
		const isCurrentActionRoute = () =>
			routeKeyRef.current === actionRouteKey;
		const activeElement =
			paymentDetailsHeadingRef.current?.ownerDocument.activeElement;
		const focusStartedInActionArea =
			activeElement instanceof HTMLElement &&
			!! activeElement.closest( AUTHORIZATION_ACTION_FOCUS_SELECTOR );

		setPendingAuthorizationAction( {
			action,
			paymentIntentId,
			routeKey: actionRouteKey,
		} );

		try {
			if ( action === 'capture' ) {
				await captureWooPaymentsAuthorization(
					orderId,
					paymentIntentId
				);
				if ( ! isCurrentActionRoute() ) {
					return;
				}
				await loadTransaction( {
					setLoading: false,
					shouldUpdate: isCurrentActionRoute,
				} );
				if ( ! isCurrentActionRoute() ) {
					return;
				}
				shouldFocusDetailsHeadingRef.current = focusStartedInActionArea;
				getNotices().createSuccessNotice(
					sprintf(
						/* translators: %s: order ID. */
						__(
							'Payment for order #%s captured successfully.',
							'woocommerce'
						),
						orderId
					)
				);
			} else {
				await cancelWooPaymentsAuthorization(
					orderId,
					paymentIntentId
				);
				if ( ! isCurrentActionRoute() ) {
					return;
				}
				await loadTransaction( {
					setLoading: false,
					shouldUpdate: isCurrentActionRoute,
				} );
				if ( ! isCurrentActionRoute() ) {
					return;
				}
				shouldFocusDetailsHeadingRef.current = focusStartedInActionArea;
				getNotices().createSuccessNotice(
					sprintf(
						/* translators: %s: order ID. */
						__(
							'Payment for order #%s canceled successfully.',
							'woocommerce'
						),
						orderId
					)
				);
			}
		} catch ( error ) {
			getNotices().createErrorNotice(
				sprintf(
					/* translators: 1: action name, 2: order ID, 3: error message. */
					__(
						'Unable to %1$s authorization for order #%2$s. %3$s',
						'woocommerce'
					),
					action,
					orderId,
					getErrorMessage(
						error,
						__(
							'Please refresh the page and try again.',
							'woocommerce'
						)
					)
				)
			);
		} finally {
			if ( isCurrentActionRoute() ) {
				setPendingAuthorizationAction( null );
			}
		}
	};

	return (
		<section
			className="woocommerce-woopayments-money-movement"
			aria-busy={ isLoading }
		>
			<h2 ref={ paymentDetailsHeadingRef } tabIndex={ -1 }>
				{ hasPaymentDetails
					? __( 'Payment details', 'woocommerce' )
					: __( 'Transaction details', 'woocommerce' ) }
			</h2>
			<LiveStatusMessage
				isError={ !! errorMessage || !! authorizationErrorMessage }
			>
				{ liveStatusMessage }
			</LiveStatusMessage>
			{ isLoading && <StatusMessage>{ loadingMessage }</StatusMessage> }
			{ errorMessage && (
				<StatusMessage isError>{ errorMessage }</StatusMessage>
			) }
			{ transaction && ! errorMessage && (
				<>
					{ authorizationErrorMessage && (
						<StatusMessage isError>
							{ authorizationErrorMessage }
						</StatusMessage>
					) }
					{ showFraudReviewActions && (
						<div className="woocommerce-woopayments-money-movement__authorization-actions">
							<Button
								variant="secondary"
								isDestructive
								isBusy={ pendingAction === 'cancel' }
								disabled={ isAuthorizationActionPending }
								accessibleWhenDisabled
								onClick={
									isAuthorizationActionPending
										? undefined
										: () =>
												handleAuthorizationAction(
													'cancel'
												)
								}
								aria-label={
									pendingAction === 'cancel'
										? sprintf(
												/* translators: %s: order ID. */
												__(
													'Blocking transaction for order #%s',
													'woocommerce'
												),
												orderId
										  )
										: undefined
								}
							>
								{ __( 'Block transaction', 'woocommerce' ) }
							</Button>
							<Button
								variant="primary"
								isBusy={ pendingAction === 'capture' }
								disabled={ isAuthorizationActionPending }
								accessibleWhenDisabled
								onClick={
									isAuthorizationActionPending
										? undefined
										: () =>
												handleAuthorizationAction(
													'capture'
												)
								}
								aria-label={
									pendingAction === 'capture'
										? sprintf(
												/* translators: %s: order ID. */
												__(
													'Approving transaction for order #%s',
													'woocommerce'
												),
												orderId
										  )
										: undefined
								}
							>
								{ __( 'Approve transaction', 'woocommerce' ) }
							</Button>
						</div>
					) }
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
					{ showCaptureNotice && (
						<section className="woocommerce-woopayments-overview-card woocommerce-woopayments-money-movement__authorization-notice">
							<p>
								{ __(
									'You must capture this charge within the next 7 days.',
									'woocommerce'
								) }
							</p>
							<Button
								variant="primary"
								isBusy={ pendingAction === 'capture' }
								disabled={ isAuthorizationActionPending }
								accessibleWhenDisabled
								onClick={
									isAuthorizationActionPending
										? undefined
										: () =>
												handleAuthorizationAction(
													'capture'
												)
								}
								aria-label={
									pendingAction === 'capture'
										? sprintf(
												/* translators: %s: order ID. */
												__(
													'Capturing authorization for order #%s',
													'woocommerce'
												),
												orderId
										  )
										: sprintf(
												/* translators: %s: order ID. */
												__(
													'Capture authorization for order #%s',
													'woocommerce'
												),
												orderId
										  )
								}
							>
								{ __( 'Capture', 'woocommerce' ) }
							</Button>
						</section>
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
