/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useLocation } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { getWooPaymentsTransaction } from './data';
import type { WooPaymentsTransaction } from './types';
import {
	formatAmount,
	formatDate,
	formatLabel,
	getErrorMessage,
} from './utils';
import { StatusMessage } from './table';
import '../style.scss';

export const WooPaymentsTransactionDetailsPage = () => {
	const [ transaction, setTransaction ] =
		useState< WooPaymentsTransaction | null >( null );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const location = useLocation();
	const query = new URLSearchParams( location.search );
	const transactionId =
		query.get( 'id' ) || query.get( 'transaction_id' ) || '';

	useEffect( () => {
		let isMounted = true;

		const loadTransaction = async () => {
			if ( ! transactionId ) {
				setErrorMessage(
					__( 'A transaction ID is required.', 'woocommerce' )
				);
				setIsLoading( false );
				return;
			}

			try {
				const nextTransaction = await getWooPaymentsTransaction(
					transactionId
				);

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
	}, [ transactionId ] );

	return (
		<section
			className="woocommerce-woopayments-money-movement"
			aria-busy={ isLoading }
		>
			<h2>{ __( 'Transaction details', 'woocommerce' ) }</h2>
			{ isLoading && (
				<StatusMessage>
					{ __( 'Loading transaction details…', 'woocommerce' ) }
				</StatusMessage>
			) }
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
								transactionId }
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
