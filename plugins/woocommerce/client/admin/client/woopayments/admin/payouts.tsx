/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getWooPaymentsDeposits } from './overview/data';
import type { WooPaymentsDeposit } from './overview/types';
import {
	formatPayoutDate,
	formatPayoutStatus,
	formatWooPaymentsAmount,
	getSettingsPaymentsProviderRouteUrl,
} from './overview/utils';
import './style.scss';

const getErrorMessage = ( error: unknown ) => {
	if ( error instanceof Error && error.message ) {
		return error.message;
	}

	if (
		error &&
		typeof error === 'object' &&
		'message' in error &&
		typeof error.message === 'string'
	) {
		return error.message;
	}

	return __( 'Unable to load WooPayments payout history.', 'woocommerce' );
};

export const WooPaymentsPayouts = () => {
	const [ payouts, setPayouts ] = useState< WooPaymentsDeposit[] >( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );

	useEffect( () => {
		let isMounted = true;

		const loadPayouts = async () => {
			setIsLoading( true );

			try {
				const response = await getWooPaymentsDeposits( {
					page: 1,
					pagesize: 25,
					sort: 'date',
					direction: 'desc',
				} );

				if ( ! isMounted ) {
					return;
				}

				setPayouts( response.data );
				setErrorMessage( null );
			} catch ( error ) {
				if ( isMounted ) {
					setErrorMessage( getErrorMessage( error ) );
				}
			} finally {
				if ( isMounted ) {
					setIsLoading( false );
				}
			}
		};

		loadPayouts();

		return () => {
			isMounted = false;
		};
	}, [] );

	const hasLoadedPayouts =
		! isLoading && ! errorMessage && payouts.length > 0;
	const statusMessage =
		( isLoading && __( 'Loading payouts…', 'woocommerce' ) ) ||
		errorMessage ||
		( ! isLoading &&
			! errorMessage &&
			payouts.length === 0 &&
			__( 'No payouts found.', 'woocommerce' ) ) ||
		( hasLoadedPayouts && __( 'Payout history loaded.', 'woocommerce' ) ) ||
		'';

	return (
		<div className="woocommerce-woopayments-payouts">
			<section
				className="woocommerce-woopayments-overview-card"
				aria-busy={ isLoading }
			>
				<h2>{ __( 'Payout history', 'woocommerce' ) }</h2>
				<p
					className={
						hasLoadedPayouts
							? 'screen-reader-text'
							: 'woocommerce-woopayments-overview__status'
					}
					role={ errorMessage ? 'alert' : 'status' }
					aria-live={ errorMessage ? 'assertive' : 'polite' }
				>
					{ statusMessage }
				</p>
				{ hasLoadedPayouts && (
					<table className="woocommerce-woopayments-overview__payouts-table">
						<thead>
							<tr>
								<th scope="col">
									{ __( 'Dispatch date', 'woocommerce' ) }
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
							{ payouts.map( ( payout ) => (
								<tr key={ payout.id }>
									<td>
										<a
											href={ getSettingsPaymentsProviderRouteUrl(
												`/woopayments/payouts/details?id=${ encodeURIComponent(
													payout.id
												) }`
											) }
										>
											{ formatPayoutDate( payout ) }
											<span className="screen-reader-text">
												{ sprintf(
													/* translators: %s: payout ID. */
													__(
														' - view payout details for %s',
														'woocommerce'
													),
													payout.id
												) }
											</span>
										</a>
									</td>
									<td>
										{ formatPayoutStatus( payout.status ) }
									</td>
									<td>
										{ formatWooPaymentsAmount(
											payout.amount,
											payout.currency
										) }
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				) }
			</section>
		</div>
	);
};

export default WooPaymentsPayouts;
