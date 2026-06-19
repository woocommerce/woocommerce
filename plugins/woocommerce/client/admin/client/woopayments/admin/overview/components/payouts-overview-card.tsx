/**
 * External dependencies
 */
import { ExternalLink } from '@wordpress/components';
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
	getMonthlyAnchorLabel,
	getPayoutStatusClassName,
	getSelectedBalanceCurrency,
} from '../utils';
import { getSettingsPaymentsProviderRouteUrl } from '../../utils';

const PAYOUT_SCHEDULE_DOCS_URL =
	'https://woocommerce.com/document/woopayments/payouts/payout-schedule/';
const SUSPENDED_PAYOUTS_DOCS_URL =
	'https://woocommerce.com/document/woopayments/payouts/why-payouts-suspended/';
const NEW_ACCOUNT_WAITING_PERIOD_DOCS_URL =
	'https://woocommerce.com/document/woopayments/payouts/payout-schedule/#new-accounts';
const NEGATIVE_BALANCE_DOCS_URL =
	'https://woocommerce.com/document/woopayments/fees/account-showing-negative-balance/';
const MINIMUM_PAYOUT_DOCS_URL =
	'https://woocommerce.com/document/woopayments/payouts/payout-schedule/#minimum-payout-amounts';
const PENDING_FUNDS_DOCS_URL =
	'https://woocommerce.com/document/woopayments/payouts/payout-schedule/#pending-funds';

const formatScheduleAnchor = ( value: string ) =>
	value.replace( /^\w/, ( match ) => match.toUpperCase() );

const getScheduleText = ( overview: WooPaymentsDepositsOverview ) => {
	const schedule = overview.account.deposits_schedule;
	const interval = schedule?.interval;

	if ( ! interval || interval === 'manual' ) {
		return null;
	}

	if ( interval === 'daily' ) {
		return __(
			'Available funds are automatically dispatched every day.',
			'woocommerce'
		);
	}

	if ( interval === 'weekly' && schedule.weekly_anchor ) {
		return sprintf(
			/* translators: %s: Day of the week. */
			__(
				'Available funds are automatically dispatched every %s.',
				'woocommerce'
			),
			formatScheduleAnchor( schedule.weekly_anchor )
		);
	}

	if ( interval === 'monthly' && schedule.monthly_anchor ) {
		if ( schedule.monthly_anchor === 31 ) {
			return __(
				'Available funds are automatically dispatched on the last day of every month.',
				'woocommerce'
			);
		}

		return sprintf(
			/* translators: %s: Day of the month. */
			__(
				'Available funds are automatically dispatched on the %s of every month.',
				'woocommerce'
			),
			getMonthlyAnchorLabel( schedule.monthly_anchor )
		);
	}

	return null;
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
				'Payouts are currently paused because a recent payout failed. Please',
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
			{ accountLinkWithSource ? '.' : '' }
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
					<td>
						<a
							href={ getSettingsPaymentsProviderRouteUrl(
								`/woopayments/payouts/details?id=${ encodeURIComponent(
									payout.id
								) }`
							) }
							aria-label={ sprintf(
								/* translators: %s: Payout ID. */
								__( 'View payout %s details', 'woocommerce' ),
								payout.id
							) }
						>
							{ formatPayoutDate( payout ) }
						</a>
					</td>
					<td>
						<span
							className={ `woocommerce-woopayments-overview__status-chip ${ getPayoutStatusClassName(
								payout.status
							) }` }
						>
							{ formatPayoutStatus( payout.status ) }
						</span>
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
);

export const PayoutsOverviewCard = ( {
	isLoading,
	errorMessage,
	overview,
	recentPayouts,
	selectedCurrency,
}: {
	isLoading: boolean;
	errorMessage: string | null;
	overview: WooPaymentsDepositsOverview | null;
	recentPayouts: WooPaymentsDeposit[];
	selectedCurrency?: string;
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

	const currency = getSelectedBalanceCurrency( overview, selectedCurrency );
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
	const scheduleText = getScheduleText( overview );
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
	const canChangePayoutSchedule =
		! isPayoutsSuspended && hasCompletedWaitingPeriod;
	const scheduleSettingsUrl = `${ getSettingsPaymentsProviderRouteUrl(
		'/woopayments/settings'
	) }#payout-schedule`;

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

			{ scheduleText && (
				<div className="woocommerce-woopayments-overview__schedule">
					<p>{ scheduleText }</p>
					<p className="woocommerce-woopayments-overview__help">
						{ __(
							'The timing and amount of your payouts may vary due to several factors. Check out our',
							'woocommerce'
						) }{ ' ' }
						<ExternalLink href={ PAYOUT_SCHEDULE_DOCS_URL }>
							{ __( 'payout schedule guide', 'woocommerce' ) }
						</ExternalLink>{ ' ' }
						{ __( 'for details.', 'woocommerce' ) }
					</p>
				</div>
			) }

			<div className="woocommerce-woopayments-overview__notices">
				{ isPayoutsSuspended && (
					<PayoutNotice>
						{ __(
							'Your payouts are temporarily suspended.',
							'woocommerce'
						) }{ ' ' }
						<ExternalLink href={ SUSPENDED_PAYOUTS_DOCS_URL }>
							{ __( 'Learn more', 'woocommerce' ) }
						</ExternalLink>
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
								) }{ ' ' }
								<ExternalLink
									href={ NEW_ACCOUNT_WAITING_PERIOD_DOCS_URL }
								>
									{ __( 'Learn more', 'woocommerce' ) }
								</ExternalLink>
							</PayoutNotice>
						) }
						{ hasNegativeBalance && (
							<PayoutNotice>
								{ sprintf(
									/* translators: %s: WooPayments */
									__(
										'Payouts may be interrupted while your %s balance remains negative.',
										'woocommerce'
									),
									'WooPayments'
								) }{ ' ' }
								<ExternalLink
									href={ NEGATIVE_BALANCE_DOCS_URL }
								>
									{ __( 'Why?', 'woocommerce' ) }
								</ExternalLink>
							</PayoutNotice>
						) }
						{ isBelowMinimumPayout && (
							<PayoutNotice>
								{ sprintf(
									/* translators: %s: formatted minimum payout amount. */
									__(
										'Payouts are paused while your available funds balance remains below %s.',
										'woocommerce'
									),
									formatWooPaymentsAmount(
										minimumPayoutAmount,
										currency
									)
								) }{ ' ' }
								<ExternalLink href={ MINIMUM_PAYOUT_DOCS_URL }>
									{ __( 'Learn more', 'woocommerce' ) }
								</ExternalLink>
							</PayoutNotice>
						) }
						{ availableFunds === 0 &&
							pendingFunds > 0 &&
							hasCompletedWaitingPeriod && (
								<PayoutNotice>
									{ __(
										'You have no funds available.',
										'woocommerce'
									) }{ ' ' }
									<ExternalLink
										href={ PENDING_FUNDS_DOCS_URL }
									>
										{ __( 'Why?', 'woocommerce' ) }
									</ExternalLink>
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
				{ canChangePayoutSchedule && (
					<p className="woocommerce-woopayments-overview__footer-actions">
						<a
							className="button button-link"
							href={ scheduleSettingsUrl }
							onClick={ () =>
								recordEvent(
									'wcpay_overview_deposits_change_schedule_click'
								)
							}
						>
							{ __( 'Change payout schedule', 'woocommerce' ) }
						</a>
					</p>
				) }
			</div>
		</section>
	);
};
