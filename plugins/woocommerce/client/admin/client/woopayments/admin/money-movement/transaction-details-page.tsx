/**
 * External dependencies
 */
import {
	Button,
	DropdownMenu,
	MenuGroup,
	MenuItem,
	Modal,
	RadioControl,
} from '@wordpress/components';
import { dispatch } from '@wordpress/data';
import {
	createInterpolateElement,
	useCallback,
	useEffect,
	useRef,
	useState,
} from '@wordpress/element';
import { moreVertical } from '@wordpress/icons';
import { __, sprintf } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
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
	refundWooPaymentsCharge,
} from './data';
import type {
	WooPaymentsAuthorization,
	WooPaymentsCharge,
	WooPaymentsDispute,
	WooPaymentsPaymentIntent,
	WooPaymentsTimelineEvent,
	WooPaymentsTransaction,
} from './types';
import { formatAmount, getErrorMessage } from './utils';
import { WooPaymentsTransactionDisputeDetails } from './transaction-dispute-details';
import {
	hasPaymentOrderContext,
	WooPaymentsMissingOrderNotice,
	WooPaymentsPaymentIdentifiersSection,
	WooPaymentsPaymentMethodDetailsSection,
	WooPaymentsPaymentSummarySection,
} from './transaction-detail-sections';
import { WooPaymentsCardReaderFeeDetails } from './transaction-card-reader-fee-details';
import { LiveStatusMessage, StatusMessage } from './table';
import { WooPaymentsTransactionTimeline } from './transaction-timeline';
import { WooPaymentsTestModeNotice } from '../test-mode-notice';
import '../style.scss';

type AuthorizationAction = 'capture' | 'cancel';
type RefundReason =
	| 'duplicate'
	| 'fraudulent'
	| 'requested_by_customer'
	| 'other'
	| null;
type PendingAuthorizationAction = {
	action: AuthorizationAction;
	paymentIntentId: string;
	routeKey: string;
} | null;
type PendingRefundAction = {
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

const DETAIL_ACTION_FOCUS_SELECTOR =
	'.woocommerce-woopayments-money-movement__authorization-actions, .woocommerce-woopayments-money-movement__authorization-notice, .woocommerce-woopayments-money-movement__refund-actions, .woocommerce-woopayments-money-movement__refund-modal';

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

const getPaymentIntentId = (
	transaction: WooPaymentsTransaction,
	fallbackId: string
) =>
	transaction.payment_intent_id ||
	( fallbackId.startsWith( 'pi_' ) ? fallbackId : '' );

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

const getTransactionOrderId = ( transaction: WooPaymentsTransaction ) => {
	const orderId = Number( transaction.order?.id );

	if ( Number.isFinite( orderId ) && orderId > 0 ) {
		return orderId;
	}

	const orderUrl =
		typeof transaction.order?.url === 'string' ? transaction.order.url : '';

	if ( ! orderUrl ) {
		return 0;
	}

	try {
		const url = new URL( orderUrl, 'http://example.com' );
		const orderIdFromUrl = Number(
			url.searchParams.get( 'id' ) || url.searchParams.get( 'post' )
		);

		return Number.isFinite( orderIdFromUrl ) && orderIdFromUrl > 0
			? orderIdFromUrl
			: 0;
	} catch {
		return 0;
	}
};

const getTransactionOrderUrl = ( transaction: WooPaymentsTransaction ) =>
	typeof transaction.order?.url === 'string' ? transaction.order.url : '';

const isDisputeInquiry = ( dispute: WooPaymentsDispute ) =>
	typeof dispute.status === 'string' &&
	dispute.status.startsWith( 'warning' );

const isDisputeAwaitingResponse = ( dispute: WooPaymentsDispute ) =>
	dispute.status === 'needs_response' ||
	dispute.status === 'warning_needs_response';

const isDisputeRefundable = ( dispute?: WooPaymentsDispute ) => {
	if ( ! dispute?.status ) {
		return true;
	}

	return isDisputeInquiry( dispute ) || dispute.status === 'won';
};

const isTransactionPartiallyRefunded = (
	transaction: WooPaymentsTransaction
) =>
	typeof transaction.amount_refunded === 'number' &&
	transaction.amount_refunded > 0;

const isTransactionRefundEligible = (
	transaction: WooPaymentsTransaction,
	refundOrderId: number
) => {
	if (
		! refundOrderId ||
		transaction.captured !== true ||
		transaction.refunded
	) {
		return false;
	}

	return isDisputeRefundable( transaction.dispute );
};

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
		billing_details: charge.billing_details,
		customer_name: charge.billing_details?.name,
		customer_email: charge.billing_details?.email,
		metadata: charge.metadata,
		sales_channel: charge.sales_channel,
		order: charge.order,
		payment_method: charge.payment_method,
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
		metadata: {
			...( intent.metadata || {} ),
			...( transaction.metadata || {} ),
		},
		payment_intent_id: intent.id,
		order: transaction.order || intent.order,
		dispute: transaction.dispute || intent.dispute,
		sales_channel:
			transaction.sales_channel || intent.sales_channel || undefined,
		status: transaction.status || intent.status,
	};
};

const RefundModal = ( {
	formattedAmount,
	isOpenInquiry,
	isRefundPending,
	orderUrl,
	reason,
	onChangeReason,
	onClose,
	onRefund,
}: {
	formattedAmount: string;
	isOpenInquiry: boolean;
	isRefundPending: boolean;
	orderUrl: string;
	reason: RefundReason;
	onChangeReason: ( value: RefundReason ) => void;
	onClose: () => void;
	onRefund: () => void;
} ) => (
	<Modal
		className="woocommerce-woopayments-money-movement__refund-modal"
		title={ __( 'Refund transaction', 'woocommerce' ) }
		onRequestClose={ onClose }
	>
		{ isOpenInquiry && (
			<p>
				{ __(
					'Issuing a refund will close the inquiry, returning the amount in question back to the cardholder. No additional fees apply.',
					'woocommerce'
				) }
			</p>
		) }
		<p>
			{ createInterpolateElement(
				sprintf(
					/* translators: %s: formatted refund amount. */
					__(
						'This will issue a full refund of <strong>%s</strong> to the customer.',
						'woocommerce'
					),
					formattedAmount
				),
				{
					strong: <strong />,
				}
			) }
		</p>
		<RadioControl
			className="woocommerce-woopayments-money-movement__refund-reason"
			label={ __( 'Select a reason (optional)', 'woocommerce' ) }
			selected={ reason || undefined }
			options={ [
				{
					label: __( 'Duplicate order', 'woocommerce' ),
					value: 'duplicate',
				},
				{
					label: __( 'Fraudulent', 'woocommerce' ),
					value: 'fraudulent',
				},
				{
					label: __( 'Requested by customer', 'woocommerce' ),
					value: 'requested_by_customer',
				},
				{
					label: __( 'Other', 'woocommerce' ),
					value: 'other',
				},
			] }
			onChange={ ( value ) => onChangeReason( value as RefundReason ) }
		/>
		{ orderUrl && (
			<p className="woocommerce-woopayments-money-movement__refund-partial-link">
				{ createInterpolateElement(
					__(
						'Need to refund part of the order? <link>Go to the order</link>.',
						'woocommerce'
					),
					{
						link: (
							<a href={ orderUrl }>
								{ __( 'Go to the order', 'woocommerce' ) }
							</a>
						),
					}
				) }
			</p>
		) }
		<div className="woocommerce-woopayments-money-movement__refund-modal-actions">
			<Button variant="tertiary" onClick={ onClose }>
				{ __( 'Cancel', 'woocommerce' ) }
			</Button>
			<Button
				variant="primary"
				isBusy={ isRefundPending }
				disabled={ isRefundPending }
				accessibleWhenDisabled
				onClick={ isRefundPending ? undefined : onRefund }
				aria-label={
					isRefundPending
						? __( 'Refunding transaction', 'woocommerce' )
						: undefined
				}
			>
				{ __( 'Refund transaction', 'woocommerce' ) }
			</Button>
		</div>
	</Modal>
);

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
	const [ pendingRefundAction, setPendingRefundAction ] =
		useState< PendingRefundAction >( null );
	const [ isRefundModalOpen, setIsRefundModalOpen ] = useState( false );
	const [ refundReason, setRefundReason ] = useState< RefundReason >( null );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const location = useLocation();
	const navigate = useNavigate();
	const query = new URLSearchParams( location.search );
	const id = query.get( 'id' ) || '';
	const transactionId = query.get( 'transaction_id' ) || '';
	const transactionType = query.get( 'transaction_type' ) || '';
	const isCardReaderFeeRoute = transactionType === 'card_reader_fee';
	const routeKey = `${ location.pathname }${ location.search }`;
	const routeKeyRef = useRef( routeKey );
	const paymentDetailsHeadingRef = useRef< HTMLHeadingElement | null >(
		null
	);
	const refundActionsRef = useRef< HTMLDivElement | null >( null );
	const shouldFocusDetailsHeadingRef = useRef( false );

	useEffect( () => {
		routeKeyRef.current = routeKey;
		setPendingAuthorizationAction( null );
		setPendingRefundAction( null );
		setIsRefundModalOpen( false );
		setRefundReason( null );
	}, [ routeKey ] );

	useEffect( () => {
		if ( ! isCardReaderFeeRoute ) {
			return;
		}

		setTransaction( null );
		setTimelineEvents( [] );
		setTimelineErrorMessage( null );
		setAuthorization( null );
		setAuthorizationErrorMessage( null );
		setErrorMessage( null );
		setIsLoading( false );
	}, [ isCardReaderFeeRoute, routeKey ] );

	useEffect( () => {
		if (
			! shouldFocusDetailsHeadingRef.current ||
			pendingAuthorizationAction ||
			pendingRefundAction
		) {
			return;
		}

		shouldFocusDetailsHeadingRef.current = false;
		const heading = paymentDetailsHeadingRef.current;
		const activeElement = heading?.ownerDocument.activeElement;
		const shouldRestoreFocus =
			activeElement === heading?.ownerDocument.body ||
			( activeElement instanceof HTMLElement &&
				!! activeElement.closest( DETAIL_ACTION_FOCUS_SELECTOR ) );

		if ( shouldRestoreFocus ) {
			heading?.focus();
		}
	}, [
		authorization,
		errorMessage,
		pendingAuthorizationAction,
		pendingRefundAction,
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

		if ( isCardReaderFeeRoute ) {
			return () => {
				isMounted = false;
			};
		}

		loadTransaction( {
			shouldUpdate: () => isMounted,
		} );

		return () => {
			isMounted = false;
		};
	}, [ isCardReaderFeeRoute, loadTransaction ] );

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
	} else if ( transaction && ! hasPaymentOrderContext( transaction ) ) {
		liveStatusMessage = __(
			'Transaction details loaded. This payment is not linked to a WooCommerce order.',
			'woocommerce'
		);
	}

	const paymentIntentId = transaction
		? getPaymentIntentId( transaction, id )
		: '';
	const chargeId = transaction?.charge_id || '';
	const transactionResourceId =
		transaction?.transaction_id || transaction?.id || transactionId || id;
	const hasPaymentDetails = !! ( paymentIntentId || chargeId );
	const hasDetailPaymentSurface = isCardReaderFeeRoute || hasPaymentDetails;
	const orderId = transaction ? getOrderId( transaction, authorization ) : 0;
	const isFraudReview =
		!! transaction && isFraudReviewTransaction( transaction );
	const showAuthorizationActions =
		!! transaction && !! authorization && !! paymentIntentId && orderId > 0;
	const showFraudReviewActions = showAuthorizationActions && isFraudReview;
	const showCaptureNotice = showAuthorizationActions && ! isFraudReview;
	const wcSettings = window.wcSettings as
		| ( typeof window.wcSettings & {
				countries?: Record< string, string >;
		  } )
		| undefined;
	const countries = wcSettings?.countries || {};
	const refundOrderId = transaction
		? getTransactionOrderId( transaction )
		: 0;
	const refundOrderUrl = transaction
		? getTransactionOrderUrl( transaction )
		: '';
	const isRefundEligible =
		!! transaction &&
		isTransactionRefundEligible( transaction, refundOrderId );
	const isPartiallyRefunded =
		!! transaction && isTransactionPartiallyRefunded( transaction );
	const showFullRefundAction = isRefundEligible && ! isPartiallyRefunded;
	const showPartialRefundAction = isRefundEligible && !! refundOrderUrl;
	const showRefundActions = showFullRefundAction || showPartialRefundAction;
	const isOpenRefundInquiry =
		!! transaction?.dispute &&
		isDisputeInquiry( transaction.dispute ) &&
		isDisputeAwaitingResponse( transaction.dispute );
	const pendingAction =
		pendingAuthorizationAction?.routeKey === routeKey &&
		pendingAuthorizationAction?.paymentIntentId === paymentIntentId
			? pendingAuthorizationAction.action
			: null;
	const isAuthorizationActionPending = !! pendingAction;
	const isRefundPending =
		pendingRefundAction?.routeKey === routeKey &&
		pendingRefundAction?.paymentIntentId === paymentIntentId;

	const focusRefundActions = () => {
		const actionButton =
			refundActionsRef.current?.querySelector( 'button' );
		if ( actionButton instanceof HTMLButtonElement ) {
			actionButton.focus();
		}
	};

	const handleRefundModalClose = () => {
		if ( isRefundPending ) {
			return;
		}

		setIsRefundModalOpen( false );
		window.setTimeout( focusRefundActions, 0 );
	};

	const handleRefundModalOpen = () => {
		setRefundReason( null );
		setIsRefundModalOpen( true );
		recordEvent( 'payments_transactions_details_refund_modal_open', {
			payment_intent_id: paymentIntentId,
		} );
	};

	const handlePartialRefund = () => {
		if ( ! refundOrderUrl ) {
			return;
		}

		recordEvent( 'payments_transactions_details_partial_refund', {
			payment_intent_id: paymentIntentId,
			order_id: refundOrderId,
		} );
		window.location.href = refundOrderUrl;
	};

	const handleRefund = async () => {
		if ( isRefundPending ) {
			return;
		}

		if (
			! transaction ||
			! paymentIntentId ||
			! chargeId ||
			! refundOrderId ||
			typeof transaction.amount !== 'number' ||
			transaction.amount <= 0
		) {
			getNotices().createErrorNotice(
				__(
					'Unable to process this refund because the payment details are incomplete.',
					'woocommerce'
				)
			);
			return;
		}

		const refundRouteKey = routeKey;
		const isCurrentRefundRoute = () =>
			routeKeyRef.current === refundRouteKey;

		setPendingRefundAction( {
			paymentIntentId,
			routeKey: refundRouteKey,
		} );

		try {
			recordEvent( 'payments_transactions_details_refund_full', {
				payment_intent_id: paymentIntentId,
			} );

			if ( isOpenRefundInquiry && transaction.dispute ) {
				recordEvent( 'wcpay_dispute_inquiry_refund_click', {
					dispute_id: transaction.dispute.id,
					dispute_status: transaction.dispute.status,
					dispute_reason: transaction.dispute.reason,
					on_page: 'transaction_details',
				} );
			}

			await refundWooPaymentsCharge( {
				chargeId,
				amount: transaction.amount,
				reason: refundReason === 'other' ? null : refundReason,
				orderId: refundOrderId,
			} );
			if ( ! isCurrentRefundRoute() ) {
				return;
			}

			await loadTransaction( {
				setLoading: false,
				shouldUpdate: isCurrentRefundRoute,
			} );
			if ( ! isCurrentRefundRoute() ) {
				return;
			}

			setIsRefundModalOpen( false );
			setRefundReason( null );
			shouldFocusDetailsHeadingRef.current = true;
			getNotices().createSuccessNotice(
				sprintf(
					/* translators: %s: payment intent ID. */
					__( 'Refunded payment #%s.', 'woocommerce' ),
					paymentIntentId
				)
			);
		} catch ( error ) {
			const baseError = sprintf(
				/* translators: %s: payment intent ID. */
				__(
					'There has been an error refunding the payment #%s. Please try again later.',
					'woocommerce'
				),
				paymentIntentId
			);
			const errorDetail = getErrorMessage( error, '' );

			getNotices().createErrorNotice(
				errorDetail ? `${ baseError } ${ errorDetail }` : baseError
			);
		} finally {
			if ( isCurrentRefundRoute() ) {
				setPendingRefundAction( null );
			}
		}
	};

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
			!! activeElement.closest( DETAIL_ACTION_FOCUS_SELECTOR );

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
			<div className="woocommerce-woopayments-money-movement__detail-header">
				<h2 ref={ paymentDetailsHeadingRef } tabIndex={ -1 }>
					{ hasDetailPaymentSurface
						? __( 'Payment details', 'woocommerce' )
						: __( 'Transaction details', 'woocommerce' ) }
				</h2>
				{ showRefundActions && (
					<div
						ref={ refundActionsRef }
						className="woocommerce-woopayments-money-movement__refund-actions"
					>
						<DropdownMenu
							icon={ moreVertical }
							label={ __( 'Transaction actions', 'woocommerce' ) }
							popoverProps={ {
								position: 'bottom left',
							} }
						>
							{ ( { onClose } ) => (
								<MenuGroup>
									{ showFullRefundAction && (
										<MenuItem
											onClick={ () => {
												handleRefundModalOpen();
												onClose();
											} }
										>
											{ __(
												'Refund in full',
												'woocommerce'
											) }
										</MenuItem>
									) }
									{ showPartialRefundAction && (
										<MenuItem
											onClick={ () => {
												handlePartialRefund();
												onClose();
											} }
										>
											{ __(
												'Partial refund',
												'woocommerce'
											) }
										</MenuItem>
									) }
								</MenuGroup>
							) }
						</DropdownMenu>
					</div>
				) }
			</div>
			{ ! isCardReaderFeeRoute && (
				<LiveStatusMessage
					isError={ !! errorMessage || !! authorizationErrorMessage }
				>
					{ liveStatusMessage }
				</LiveStatusMessage>
			) }
			{ isLoading && <StatusMessage>{ loadingMessage }</StatusMessage> }
			{ errorMessage && (
				<StatusMessage isError>{ errorMessage }</StatusMessage>
			) }
			{ hasDetailPaymentSurface && ! errorMessage && (
				<WooPaymentsTestModeNotice
					currentPage="payments"
					isDetailsView
				/>
			) }
			{ isCardReaderFeeRoute && ! errorMessage && (
				<div className="woocommerce-woopayments-money-movement__detail-sections">
					<WooPaymentsCardReaderFeeDetails
						transactionId={ transactionId || id }
					/>
				</div>
			) }
			{ transaction && ! errorMessage && ! isCardReaderFeeRoute && (
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
					<div className="woocommerce-woopayments-money-movement__detail-sections">
						<WooPaymentsPaymentSummarySection
							transaction={ transaction }
						/>
						<WooPaymentsMissingOrderNotice
							transaction={ transaction }
						/>
						<WooPaymentsPaymentIdentifiersSection
							paymentIntentId={ paymentIntentId }
							chargeId={ chargeId }
							transactionResourceId={ transactionResourceId }
							type={ transaction.type }
						/>
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
						<WooPaymentsPaymentMethodDetailsSection
							transaction={ transaction }
							countries={ countries }
						/>
						{ timelineErrorMessage && (
							<StatusMessage isError>
								{ timelineErrorMessage }
							</StatusMessage>
						) }
						<WooPaymentsTransactionTimeline
							events={ timelineEvents }
						/>
					</div>
					{ isRefundModalOpen && (
						<RefundModal
							formattedAmount={ formatAmount(
								transaction.amount,
								transaction.currency
							) }
							isOpenInquiry={ isOpenRefundInquiry }
							isRefundPending={ !! isRefundPending }
							orderUrl={ refundOrderUrl }
							reason={ refundReason }
							onChangeReason={ setRefundReason }
							onClose={ handleRefundModalClose }
							onRefund={ handleRefund }
						/>
					) }
				</>
			) }
		</section>
	);
};
