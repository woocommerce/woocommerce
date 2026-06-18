/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { WooPaymentsDepositsOverview } from '../types';
import {
	formatWooPaymentsAmount,
	getAmountForCurrency,
	getDefaultCurrency,
} from '../utils';

export const AccountBalancesCard = ( {
	isLoading,
	errorMessage,
	overview,
}: {
	isLoading: boolean;
	errorMessage: string | null;
	overview: WooPaymentsDepositsOverview | null;
} ) => {
	const headingId = 'woocommerce-woopayments-balance-heading';
	const statusMessage =
		( isLoading && __( 'Loading balance…', 'woocommerce' ) ) ||
		errorMessage ||
		'';

	if ( ! isLoading && ! errorMessage && ! overview ) {
		return null;
	}

	const currency = overview ? getDefaultCurrency( overview ) : '';
	const available = overview
		? getAmountForCurrency( overview.balance?.available, currency )
		: 0;
	const pending = overview
		? getAmountForCurrency( overview.balance?.pending, currency )
		: 0;
	const total = available + pending;
	const hasBalanceData = ! isLoading && ! errorMessage && !! overview;

	return (
		<section
			className="woocommerce-woopayments-overview-card"
			aria-labelledby={ headingId }
			aria-busy={ isLoading }
		>
			<h2 id={ headingId }>{ __( 'Balance', 'woocommerce' ) }</h2>
			<p
				className={
					hasBalanceData
						? 'screen-reader-text'
						: 'woocommerce-woopayments-overview__status'
				}
				role={ errorMessage ? 'alert' : 'status' }
				aria-live={ errorMessage ? 'assertive' : 'polite' }
			>
				{ statusMessage }
			</p>
			{ hasBalanceData && (
				<dl className="woocommerce-woopayments-overview__balance-grid">
					<div>
						<dt>{ __( 'Available', 'woocommerce' ) }</dt>
						<dd>
							{ formatWooPaymentsAmount( available, currency ) }
						</dd>
					</div>
					<div>
						<dt>{ __( 'Pending', 'woocommerce' ) }</dt>
						<dd>
							{ formatWooPaymentsAmount( pending, currency ) }
						</dd>
					</div>
					<div>
						<dt>{ __( 'Total', 'woocommerce' ) }</dt>
						<dd>{ formatWooPaymentsAmount( total, currency ) }</dd>
					</div>
				</dl>
			) }
		</section>
	);
};
