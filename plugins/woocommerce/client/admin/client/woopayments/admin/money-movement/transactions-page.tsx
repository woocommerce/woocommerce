/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { getWooPaymentsTransactions } from './data';
import type { WooPaymentsTransaction } from './types';
import {
	formatAmount,
	formatDate,
	formatLabel,
	getResourceId,
	getErrorMessage,
	getTransactionDetailsRoute,
} from './utils';
import { EmptyState, LiveStatusMessage, StatusMessage } from './table';
import { getSettingsPaymentsProviderRouteUrl } from '../utils';
import '../style.scss';

export const WooPaymentsTransactionsPage = () => {
	const [ transactions, setTransactions ] = useState<
		WooPaymentsTransaction[]
	>( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );

	useEffect( () => {
		recordEvent( 'page_view', {
			path: 'payments_transactions',
		} );

		let isMounted = true;

		const loadTransactions = async () => {
			try {
				const response = await getWooPaymentsTransactions( {
					page: 1,
					pagesize: 25,
					sort: 'date',
					direction: 'desc',
				} );

				if ( isMounted ) {
					setTransactions( response.data || [] );
					setErrorMessage( null );
				}
			} catch ( error ) {
				if ( isMounted ) {
					setErrorMessage(
						getErrorMessage(
							error,
							__(
								'Unable to load WooPayments transactions.',
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

		loadTransactions();

		return () => {
			isMounted = false;
		};
	}, [] );

	const hasTransactions =
		! isLoading && ! errorMessage && transactions.length;
	let liveStatusMessage = __( 'Transactions loaded.', 'woocommerce' );

	if ( errorMessage ) {
		liveStatusMessage = errorMessage;
	} else if ( isLoading ) {
		liveStatusMessage = __( 'Loading transactions…', 'woocommerce' );
	} else if ( transactions.length === 0 ) {
		liveStatusMessage = __( 'No transactions found.', 'woocommerce' );
	}

	return (
		<section
			className="woocommerce-woopayments-money-movement"
			aria-busy={ isLoading }
		>
			<h2>{ __( 'Transactions', 'woocommerce' ) }</h2>
			<LiveStatusMessage isError={ !! errorMessage }>
				{ liveStatusMessage }
			</LiveStatusMessage>
			{ isLoading && (
				<StatusMessage>
					{ __( 'Loading transactions…', 'woocommerce' ) }
				</StatusMessage>
			) }
			{ errorMessage && <StatusMessage>{ errorMessage }</StatusMessage> }
			{ ! isLoading && ! errorMessage && transactions.length === 0 && (
				<EmptyState>
					{ __( 'No transactions found.', 'woocommerce' ) }
				</EmptyState>
			) }
			{ !! hasTransactions && (
				<table className="woocommerce-woopayments-money-movement__table">
					<thead>
						<tr>
							<th scope="col">{ __( 'Date', 'woocommerce' ) }</th>
							<th scope="col">{ __( 'Type', 'woocommerce' ) }</th>
							<th scope="col">
								{ __( 'Customer', 'woocommerce' ) }
							</th>
							<th scope="col">
								{ __( 'Amount', 'woocommerce' ) }
							</th>
						</tr>
					</thead>
					<tbody>
						{ transactions.map( ( transaction ) => {
							const id = getResourceId( transaction );

							return (
								<tr key={ id }>
									<td>
										{ formatDate(
											transaction.date ||
												transaction.created
										) }
									</td>
									<td>
										<a
											href={ getSettingsPaymentsProviderRouteUrl(
												getTransactionDetailsRoute(
													transaction
												)
											) }
											aria-label={ sprintf(
												/* translators: 1: transaction type, 2: transaction ID. */
												__(
													'View transaction details for %1$s transaction %2$s',
													'woocommerce'
												),
												formatLabel( transaction.type ),
												id
											) }
										>
											{ formatLabel( transaction.type ) }
										</a>
									</td>
									<td>
										{ transaction.customer_name ||
											transaction.customer_email ||
											'-' }
									</td>
									<td>
										{ formatAmount(
											transaction.amount,
											transaction.currency
										) }
									</td>
								</tr>
							);
						} ) }
					</tbody>
				</table>
			) }
		</section>
	);
};
