/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { recordEvent } from '@woocommerce/tracks';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import type { WooPaymentsDeposit, WooPaymentsDepositsOverview } from '../types';
import {
	formatPayoutDate,
	formatPayoutStatus,
	formatWooPaymentsAmount,
	getAmountForCurrency,
	getDefaultCurrency,
	getSettingsPaymentsProviderRouteUrl,
} from '../utils';

const formatScheduleAnchor = ( value: string ) =>
	value.replace( /^\w/, ( match ) => match.toUpperCase() );

const getScheduleText = ( overview: WooPaymentsDepositsOverview ) => {
	const schedule = overview.account.deposits_schedule;
	const interval = schedule?.interval;

	if ( ! interval || interval === 'manual' ) {
		return __( 'Payouts are set to manual.', 'woocommerce' );
	}

	if ( interval === 'weekly' && schedule.weekly_anchor ) {
		return sprintf(
			/* translators: %s: day of the week. */
			__( 'Payouts are scheduled weekly on %s.', 'woocommerce' ),
			formatScheduleAnchor( schedule.weekly_anchor )
		);
	}

	if ( interval === 'monthly' && schedule.monthly_anchor ) {
		return sprintf(
			/* translators: %s: day of the month. */
			__( 'Payouts are scheduled monthly on day %s.', 'woocommerce' ),
			String( schedule.monthly_anchor )
		);
	}

	return sprintf(
		/* translators: %s: payout schedule interval. */
		__( 'Payouts are scheduled %s.', 'woocommerce' ),
		interval
	);
};

const PayoutNotice = ( { children }: { children: ReactNode } ) => (
	<p className="woocommerce-woopayments-overview__notice" role="status">
		{ children }
	</p>
);

const FailedPayoutNotice = ( { accountLink }: { accountLink?: string } ) => {
	const accountLinkWithSource = accountLink
		? addQueryArgs( accountLink, {
				from: 'WCPAY_PAYOUTS',
				source: 'wcpay-payout-failure-notice',
		  } )
		: '';

	return (
		<PayoutNotice>
			{ __(
				'Payouts are currently paused because a recent payout failed.',
				'woocommerce'
			) }{ ' ' }
			{ accountLinkWithSource ? (
				<a
					href={ accountLinkWithSource }
					onClick={ () =>
						recordEvent( 'wcpay_account_details_link_clicked', {
							from: 'WCPAY_PAYOUTS',
							source: 'wcpay-payout-failure-notice',
						} )
					}
				>
					{ __( 'update your bank account details', 'woocommerce' ) }
				</a>
			) : (
				__( 'Update your bank account details.', 'woocommerce' )
			) }
		</PayoutNotice>
	);
};

const RecentPayoutsList = ( {
	payouts,
}: {
	payouts: WooPaymentsDeposit[];
} ) => (
	<table className="woocommerce-woopayments-overview__payouts-table">
		<thead>
			<tr>
				<th scope="col">{ __( 'Dispatch date', 'woocommerce' ) }</th>
				<th scope="col">{ __( 'Status', 'woocommerce' ) }</th>
				<th scope="col">{ __( 'Amount', 'woocommerce' ) }</th>
			</tr>
		</thead>
		<tbody>
			{ payouts.map( ( payout ) => (
				<tr key={ payout.id }>
					<td>{ formatPayoutDate( payout ) }</td>
					<td>{ formatPayoutStatus( payout.status ) }</td>
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
);

export const PayoutsOverviewCard = ( {
	isLoading,
	errorMessage,
	overview,
	recentPayouts,
}: {
	isLoading: boolean;
	errorMessage: string | null;
	overview: WooPaymentsDepositsOverview | null;
	recentPayouts: WooPaymentsDeposit[];
} ) => {
	const headingId = 'woocommerce-woopayments-payouts-heading';
	const historyUrl = getSettingsPaymentsProviderRouteUrl(
		'/woopayments/payouts'
	);
	const hasRecentPayouts = recentPayouts.length > 0;
	const historyStatusMessage =
		errorMessage ||
		( hasRecentPayouts
			? __( 'Payout history loaded.', 'woocommerce' )
			: __( 'No recent payouts.', 'woocommerce' ) );
	const isHistoryStatusVisible = !! errorMessage || ! hasRecentPayouts;
	let historyContent: ReactNode = null;

	if ( ! errorMessage && hasRecentPayouts ) {
		historyContent = (
			<>
				<RecentPayoutsList payouts={ recentPayouts } />
				<p>
					<a
						className="button button-secondary"
						href={ historyUrl }
						onClick={ () =>
							recordEvent(
								'wcpay_overview_deposits_view_history_click'
							)
						}
					>
						{ __( 'View full payout history', 'woocommerce' ) }
					</a>
				</p>
			</>
		);
	}

	if ( isLoading ) {
		return (
			<section
				className="woocommerce-woopayments-overview-card"
				aria-labelledby={ headingId }
				aria-busy
			>
				<div className="woocommerce-woopayments-overview-card__header">
					<h2 id={ headingId }>{ __( 'Payouts', 'woocommerce' ) }</h2>
				</div>
				<div
					key="payout-history"
					className="woocommerce-woopayments-overview__history"
				>
					<h3>{ __( 'Payout history', 'woocommerce' ) }</h3>
					<p
						className="woocommerce-woopayments-overview__status"
						role="status"
						aria-live="polite"
					>
						{ __( 'Loading payouts…', 'woocommerce' ) }
					</p>
				</div>
			</section>
		);
	}

	if ( ! overview ) {
		if ( ! errorMessage && ! hasRecentPayouts ) {
			return null;
		}

		return (
			<section
				className="woocommerce-woopayments-overview-card"
				aria-labelledby={ headingId }
				aria-busy={ false }
			>
				<div className="woocommerce-woopayments-overview-card__header">
					<h2 id={ headingId }>{ __( 'Payouts', 'woocommerce' ) }</h2>
				</div>
				<div
					key="payout-history"
					className="woocommerce-woopayments-overview__history"
				>
					<h3>{ __( 'Payout history', 'woocommerce' ) }</h3>
					<p
						className={
							isHistoryStatusVisible
								? 'woocommerce-woopayments-overview__status'
								: 'screen-reader-text'
						}
						role={ errorMessage ? 'alert' : 'status' }
						aria-live={ errorMessage ? 'assertive' : 'polite' }
					>
						{ historyStatusMessage }
					</p>
					{ historyContent }
				</div>
			</section>
		);
	}

	const currency = getDefaultCurrency( overview );
	const availableFunds = getAmountForCurrency(
		overview.balance?.available,
		currency
	);
	const pendingFunds = getAmountForCurrency(
		overview.balance?.pending,
		currency
	);
	const totalFunds = availableFunds + pendingFunds;
	const hasCompletedWaitingPeriod =
		overview.account.completed_waiting_period ?? true;
	const isPayoutsSuspended =
		overview.account.deposits_blocked ||
		overview.account.deposits_enabled === false ||
		overview.account.deposits_disabled;
	const minimumPayoutAmount =
		overview.account.minimum_scheduled_deposit_amounts?.[
			currency.toLowerCase()
		] ?? 0;
	const isBelowMinimumPayout =
		availableFunds > 0 &&
		minimumPayoutAmount > 0 &&
		availableFunds < minimumPayoutAmount;
	const hasNegativeBalance = totalFunds < 0;
	const hasErroredExternalAccount =
		overview.account.default_external_accounts?.some(
			( externalAccount ) =>
				externalAccount.currency.toLowerCase() ===
					currency.toLowerCase() &&
				externalAccount.status === 'errored'
		) ?? false;
	const accountLink =
		typeof overview.account.account_link === 'string'
			? overview.account.account_link
			: undefined;

	if (
		! hasCompletedWaitingPeriod &&
		availableFunds === 0 &&
		pendingFunds === 0 &&
		! hasRecentPayouts
	) {
		return null;
	}

	return (
		<section
			className="woocommerce-woopayments-overview-card"
			aria-labelledby={ headingId }
			aria-busy={ false }
		>
			<div className="woocommerce-woopayments-overview-card__header">
				<h2 id={ headingId }>{ __( 'Payouts', 'woocommerce' ) }</h2>
				<span className="woocommerce-woopayments-overview-card__amount">
					{ formatWooPaymentsAmount( availableFunds, currency ) }
				</span>
			</div>

			<p>{ getScheduleText( overview ) }</p>

			<div className="woocommerce-woopayments-overview__notices">
				{ isPayoutsSuspended && (
					<PayoutNotice>
						{ __(
							'Payouts are temporarily suspended.',
							'woocommerce'
						) }
					</PayoutNotice>
				) }
				{ hasErroredExternalAccount && (
					<FailedPayoutNotice accountLink={ accountLink } />
				) }
				{ ! isPayoutsSuspended && (
					<>
						{ ! hasCompletedWaitingPeriod && (
							<PayoutNotice>
								{ __(
									'Payout scheduling becomes available after the standard 7-day waiting period for new accounts is complete.',
									'woocommerce'
								) }
							</PayoutNotice>
						) }
						{ hasNegativeBalance && (
							<PayoutNotice>
								{ __(
									'Payouts may be interrupted while your WooPayments balance remains negative.',
									'woocommerce'
								) }
							</PayoutNotice>
						) }
						{ isBelowMinimumPayout && (
							<PayoutNotice>
								{ sprintf(
									/* translators: %s: formatted minimum payout amount. */
									__(
										'Available funds are below the minimum payout amount of %s.',
										'woocommerce'
									),
									formatWooPaymentsAmount(
										minimumPayoutAmount,
										currency
									)
								) }
							</PayoutNotice>
						) }
						{ availableFunds === 0 &&
							pendingFunds > 0 &&
							hasCompletedWaitingPeriod && (
								<PayoutNotice>
									{ __(
										'No funds are available for payout yet.',
										'woocommerce'
									) }
								</PayoutNotice>
							) }
					</>
				) }
			</div>

			<dl className="woocommerce-woopayments-overview__balance-grid">
				<div>
					<dt>{ __( 'Available', 'woocommerce' ) }</dt>
					<dd>
						{ formatWooPaymentsAmount( availableFunds, currency ) }
					</dd>
				</div>
				<div>
					<dt>{ __( 'Pending', 'woocommerce' ) }</dt>
					<dd>
						{ formatWooPaymentsAmount( pendingFunds, currency ) }
					</dd>
				</div>
			</dl>

			<div
				key="payout-history"
				className="woocommerce-woopayments-overview__history"
			>
				<h3>{ __( 'Payout history', 'woocommerce' ) }</h3>
				<p
					className={
						isHistoryStatusVisible
							? 'woocommerce-woopayments-overview__status'
							: 'screen-reader-text'
					}
					role={ errorMessage ? 'alert' : 'status' }
					aria-live={ errorMessage ? 'assertive' : 'polite' }
				>
					{ historyStatusMessage }
				</p>
				{ historyContent }
			</div>
		</section>
	);
};
