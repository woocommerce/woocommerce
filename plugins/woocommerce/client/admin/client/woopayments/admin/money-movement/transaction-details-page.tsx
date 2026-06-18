/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useLocation, useNavigate } from 'react-router-dom';

/**
 * Internal dependencies
 */
import {
	getWooPaymentsCharge,
	getWooPaymentsPaymentIntent,
	getWooPaymentsTransaction,
} from './data';
import type {
	WooPaymentsCharge,
	WooPaymentsPaymentIntent,
	WooPaymentsTransaction,
} from './types';
import {
	formatAmount,
	formatDate,
	formatLabel,
	getErrorMessage,
} from './utils';
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

const getIntentCharge = ( intent: WooPaymentsPaymentIntent ) =>
	intent.charge || intent.charges?.data?.[ 0 ] || {};

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
		status: transaction.status || intent.status,
	};
};

export const WooPaymentsTransactionDetailsPage = () => {
	const [ transaction, setTransaction ] =
		useState< WooPaymentsTransaction | null >( null );
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

				if ( isMounted ) {
					setTransaction( nextTransaction );
					setErrorMessage( null );
				}
			} catch ( error ) {
				if ( isMounted ) {
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
	} else if ( isLoading ) {
		liveStatusMessage = loadingMessage;
	}

	return (
		<section
			className="woocommerce-woopayments-money-movement"
			aria-busy={ isLoading }
		>
			<h2>{ __( 'Transaction details', 'woocommerce' ) }</h2>
			<LiveStatusMessage isError={ !! errorMessage }>
				{ liveStatusMessage }
			</LiveStatusMessage>
			{ isLoading && <StatusMessage>{ loadingMessage }</StatusMessage> }
			{ errorMessage && (
				<StatusMessage isError>{ errorMessage }</StatusMessage>
			) }
			{ transaction && ! errorMessage && (
				<dl className="woocommerce-woopayments-money-movement__details">
					<div>
						<dt>{ __( 'Transaction ID', 'woocommerce' ) }</dt>
						<dd>
							{ transaction.id ||
								transaction.transaction_id ||
								transactionId ||
								id }
						</dd>
					</div>
					<div>
						<dt>{ __( 'Type', 'woocommerce' ) }</dt>
						<dd>{ formatLabel( transaction.type ) }</dd>
					</div>
					<div>
						<dt>{ __( 'Date', 'woocommerce' ) }</dt>
						<dd>
							{ formatDate(
								transaction.date || transaction.created
							) }
						</dd>
					</div>
					<div>
						<dt>{ __( 'Amount', 'woocommerce' ) }</dt>
						<dd>
							{ formatAmount(
								transaction.amount,
								transaction.currency
							) }
						</dd>
					</div>
				</dl>
			) }
		</section>
	);
};
