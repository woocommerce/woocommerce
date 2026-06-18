/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { getWooPaymentsDisputes } from './data';
import type { WooPaymentsDispute } from './types';
import { isDisputeActionable } from './dispute-evidence-fields';
import {
	formatAmount,
	formatDate,
	formatLabel,
	getChargeId,
	getDisputeId,
	getErrorMessage,
} from './utils';
import { EmptyState, LiveStatusMessage, StatusMessage } from './table';
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
	let liveStatusMessage = __( 'Disputes loaded.', 'woocommerce' );

	if ( errorMessage ) {
		liveStatusMessage = errorMessage;
	} else if ( isLoading ) {
		liveStatusMessage = __( 'Loading disputes…', 'woocommerce' );
	} else if ( disputes.length === 0 ) {
		liveStatusMessage = __( 'No disputes found.', 'woocommerce' );
	}

	return (
		<section
			className="woocommerce-woopayments-money-movement"
			aria-busy={ isLoading }
		>
			<h2>{ __( 'Disputes', 'woocommerce' ) }</h2>
			<LiveStatusMessage isError={ !! errorMessage }>
				{ liveStatusMessage }
			</LiveStatusMessage>
			{ isLoading && (
				<StatusMessage>
					{ __( 'Loading disputes…', 'woocommerce' ) }
				</StatusMessage>
			) }
			{ errorMessage && <StatusMessage>{ errorMessage }</StatusMessage> }
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
							const id = getDisputeId( dispute );
							const chargeId = getChargeId( dispute );
							const isActionable = isDisputeActionable( dispute );
							const rowHref = isActionable
								? getSettingsPaymentsProviderRouteUrl(
										`/woopayments/disputes/challenge?id=${ encodeURIComponent(
											id
										) }`
								  )
								: getSettingsPaymentsProviderRouteUrl(
										`/woopayments/transactions/details?id=${ encodeURIComponent(
											chargeId || id
										) }`
								  );
							const ariaLabel = isActionable
								? sprintf(
										/* translators: 1: dispute reason, 2: dispute ID. */
										__(
											'Challenge %1$s dispute %2$s',
											'woocommerce'
										),
										formatLabel( dispute.reason ),
										id
								  )
								: sprintf(
										/* translators: 1: dispute reason, 2: dispute ID. */
										__(
											'View transaction details for %1$s dispute %2$s',
											'woocommerce'
										),
										formatLabel( dispute.reason ),
										id
								  );
							const action = isActionable
								? 'challenge'
								: 'view_transaction';

							return (
								<tr key={ id }>
									<td>
										{ formatDate(
											dispute.date || dispute.created
										) }
									</td>
									<td>
										<a
											href={ rowHref }
											aria-label={ ariaLabel }
											onClick={ () =>
												recordEvent(
													'wcpay_disputes_row_action_click',
													{
														action,
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
