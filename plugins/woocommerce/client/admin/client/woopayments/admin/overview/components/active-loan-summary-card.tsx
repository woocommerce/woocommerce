/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getWooPaymentsCapitalActiveLoanSummary } from '../../capital/data';
import type { WooPaymentsCapitalSummary } from '../../capital/types';
import { formatWooPaymentsAmount } from '../utils';

export const ActiveLoanSummaryCard = ( {
	hasActiveLoan,
}: {
	hasActiveLoan?: boolean;
} ) => {
	const [ summary, setSummary ] =
		useState< WooPaymentsCapitalSummary | null >( null );

	useEffect( () => {
		let isMounted = true;

		if ( ! hasActiveLoan ) {
			setSummary( null );
			return () => {
				isMounted = false;
			};
		}

		getWooPaymentsCapitalActiveLoanSummary()
			.then( ( nextSummary ) => {
				if ( isMounted ) {
					setSummary( nextSummary );
				}
			} )
			.catch( () => {
				if ( isMounted ) {
					setSummary( null );
				}
			} );

		return () => {
			isMounted = false;
		};
	}, [ hasActiveLoan ] );

	if ( ! hasActiveLoan || ! summary?.details ) {
		return null;
	}

	const { details } = summary;
	const totalDue = details.advance_amount + details.fee_amount;

	return (
		<section className="woocommerce-woopayments-overview-card woocommerce-woopayments-active-loan">
			<h2>{ __( 'Active loan overview', 'woocommerce' ) }</h2>
			<dl className="woocommerce-woopayments-active-loan__summary">
				<div>
					<dt>{ __( 'Total repaid', 'woocommerce' ) }</dt>
					<dd>
						{ sprintf(
							/* translators: 1: repaid amount, 2: total owed amount. */
							__( '%1$s of %2$s', 'woocommerce' ),
							formatWooPaymentsAmount(
								details.paid_amount,
								details.currency
							),
							formatWooPaymentsAmount(
								totalDue,
								details.currency
							)
						) }
					</dd>
				</div>
			</dl>
		</section>
	);
};
