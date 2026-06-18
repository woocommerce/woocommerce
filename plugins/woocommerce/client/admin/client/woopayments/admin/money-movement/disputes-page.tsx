/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { getWooPaymentsDisputes } from './data';
import type { WooPaymentsDispute } from './types';
import {
	formatAmount,
	formatDate,
	formatLabel,
	getChargeId,
	getResourceId,
	getErrorMessage,
} from './utils';
import { EmptyState, StatusMessage } from './table';
import { getSettingsPaymentsProviderRouteUrl } from '../overview/utils';
import '../style.scss';

export const WooPaymentsDisputesPage = () => {
	const [ disputes, setDisputes ] = useState< WooPaymentsDispute[] >( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );

	useEffect( () => {
		recordEvent( 'page_view', {
			path: 'payments_disputes',
		} );

		let isMounted = true;

		const loadDisputes = async () => {
			try {
				const response = await getWooPaymentsDisputes( {
					page: 1,
					pagesize: 25,
					sort: 'created',
					direction: 'desc',
				} );

				if ( isMounted ) {
					setDisputes( response.data || [] );
					setErrorMessage( null );
				}
			} catch ( error ) {
				if ( isMounted ) {
					setErrorMessage(
						getErrorMessage(
							error,
							__(
								'Unable to load WooPayments disputes.',
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

		loadDisputes();

		return () => {
			isMounted = false;
		};
	}, [] );

	const hasDisputes = ! isLoading && ! errorMessage && disputes.length;

	return (
		<section
			className="woocommerce-woopayments-money-movement"
			aria-busy={ isLoading }
		>
			<h2>{ __( 'Disputes', 'woocommerce' ) }</h2>
			{ isLoading && (
				<StatusMessage>
					{ __( 'Loading disputes…', 'woocommerce' ) }
				</StatusMessage>
			) }
			{ errorMessage && (
				<StatusMessage isError>{ errorMessage }</StatusMessage>
			) }
			{ ! isLoading && ! errorMessage && disputes.length === 0 && (
				<EmptyState>
					{ __( 'No disputes found.', 'woocommerce' ) }
				</EmptyState>
			) }
			{ !! hasDisputes && (
				<table className="woocommerce-woopayments-money-movement__table">
					<thead>
						<tr>
							<th scope="col">{ __( 'Date', 'woocommerce' ) }</th>
							<th scope="col">
								{ __( 'Dispute', 'woocommerce' ) }
							</th>
							<th scope="col">
								{ __( 'Status', 'woocommerce' ) }
							</th>
							<th scope="col">
								{ __( 'Amount', 'woocommerce' ) }
							</th>
						</tr>
					</thead>
					<tbody>
						{ disputes.map( ( dispute ) => {
							const id = getResourceId( dispute );
							const chargeId = getChargeId( dispute );

							return (
								<tr key={ id }>
									<td>
										{ formatDate(
											dispute.date || dispute.created
										) }
									</td>
									<td>
										<a
											href={ getSettingsPaymentsProviderRouteUrl(
												`/woopayments/transactions/details?id=${ encodeURIComponent(
													chargeId || id
												) }`
											) }
											onClick={ () =>
												recordEvent(
													'wcpay_disputes_row_action_click',
													{
														action: 'view_transaction',
														dispute_id: id,
													}
												)
											}
										>
											{ formatLabel( dispute.reason ) }
										</a>
									</td>
									<td>{ formatLabel( dispute.status ) }</td>
									<td>
										{ formatAmount(
											dispute.amount,
											dispute.currency
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
